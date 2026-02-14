<?php

namespace Modules\AppVideoWizard\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardAsset;
use Modules\AppVideoWizard\Models\VwSetting;
use Modules\AppVideoWizard\Services\SpeechSegment;
use Modules\AppVideoWizard\Services\SpeechSegmentParser;
use Modules\AppVideoWizard\Services\VoicePromptBuilderService;
use Modules\AppVideoWizard\Services\Voice\MultiSpeakerDialogueBuilder;

class VoiceoverService
{
    /**
     * Available OpenAI voices.
     */
    protected array $openaiVoices = [
        'alloy' => ['name' => 'Alloy', 'gender' => 'neutral', 'style' => 'versatile'],
        'echo' => ['name' => 'Echo', 'gender' => 'male', 'style' => 'warm'],
        'fable' => ['name' => 'Fable', 'gender' => 'neutral', 'style' => 'storytelling'],
        'onyx' => ['name' => 'Onyx', 'gender' => 'male', 'style' => 'deep'],
        'nova' => ['name' => 'Nova', 'gender' => 'female', 'style' => 'friendly'],
        'shimmer' => ['name' => 'Shimmer', 'gender' => 'female', 'style' => 'bright'],
    ];

    /**
     * Alias for backwards compatibility.
     */
    protected array $voices = [];

    /**
     * Kokoro TTS service instance.
     */
    protected ?KokoroTtsService $kokoroService = null;

    /**
     * Qwen3 TTS service instance.
     */
    protected ?Qwen3TtsService $qwen3TtsService = null;

    public function __construct()
    {
        $this->voices = $this->openaiVoices;
    }

    /**
     * Get the configured TTS provider.
     */
    protected function getProvider(): string
    {
        $setting = VwSetting::where('slug', 'ai_voiceover_provider')->first();
        return $setting?->value ?? 'openai';
    }

    /**
     * Enhance text with emotional direction using VoicePromptBuilderService (VOC-09/VOC-11).
     *
     * @param string $text Text to enhance
     * @param string|null $emotion Emotion to apply
     * @param string $provider TTS provider (openai, kokoro, elevenlabs)
     * @return array{text: string, instructions: string}
     */
    protected function enhanceTextWithVoiceDirection(string $text, ?string $emotion, string $provider): array
    {
        // Skip if no emotion specified
        if (empty($emotion)) {
            return ['text' => $text, 'instructions' => ''];
        }

        try {
            $segment = new SpeechSegment([
                'text' => $text,
                'emotion' => $emotion,
                'type' => SpeechSegment::TYPE_DIALOGUE,
            ]);

            $promptBuilder = app(VoicePromptBuilderService::class);
            $enhanced = $promptBuilder->buildEnhancedVoicePrompt($segment, [
                'provider' => $provider,
                'includeAmbient' => false,
            ]);

            Log::debug('VoiceoverService: Applied emotional direction (VOC-11)', [
                'emotion' => $emotion,
                'provider' => $provider,
                'hasInstructions' => !empty($enhanced['instructions']),
            ]);

            return [
                'text' => $enhanced['text'],
                'instructions' => $enhanced['instructions'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::warning('VoiceoverService: Failed to apply emotional direction', [
                'emotion' => $emotion,
                'error' => $e->getMessage(),
            ]);
            return ['text' => $text, 'instructions' => ''];
        }
    }

    /**
     * Get the Kokoro TTS service instance.
     */
    protected function getKokoroService(): KokoroTtsService
    {
        if ($this->kokoroService === null) {
            $this->kokoroService = app(KokoroTtsService::class);
        }
        return $this->kokoroService;
    }

    /**
     * Get the Qwen3 TTS service instance.
     */
    protected function getQwen3TtsService(): Qwen3TtsService
    {
        if ($this->qwen3TtsService === null) {
            $this->qwen3TtsService = app(Qwen3TtsService::class);
        }
        return $this->qwen3TtsService;
    }

    /**
     * Check if Kokoro TTS is the active provider and configured.
     */
    public function isKokoroActive(): bool
    {
        return $this->getProvider() === 'kokoro' && $this->getKokoroService()->isConfigured();
    }

    /**
     * Generate voiceover for a scene.
     */
    public function generateSceneVoiceover(WizardProject $project, array $scene, array $options = []): array
    {
        $narration = $scene['narration'] ?? '';
        $voice = $options['voice'] ?? 'nova';
        $speed = $options['speed'] ?? 1.0;
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);
        $forceProvider = $options['provider'] ?? null; // Allow forcing a specific provider
        $emotion = $options['emotion'] ?? null;

        if (empty($narration)) {
            throw new \Exception('No narration text provided');
        }

        // Determine which provider to use
        $provider = $forceProvider ?? $this->getProvider();

        // Apply emotional direction if specified (VOC-11)
        $instructions = '';
        if ($emotion) {
            $enhanced = $this->enhanceTextWithVoiceDirection($narration, $emotion, $provider);
            $narration = $enhanced['text'];
            $instructions = $enhanced['instructions'];
        }

        Log::info('VoiceoverService: Generating voiceover', [
            'project_id' => $project->id,
            'scene_id' => $scene['id'] ?? 'unknown',
            'provider' => $provider,
            'voice' => $voice,
            'emotion' => $emotion,
        ]);

        // Use Kokoro TTS if configured and selected
        if ($provider === 'kokoro' && $this->getKokoroService()->isConfigured()) {
            return $this->generateWithKokoro($project, $scene, $narration, $voice, $speed, array_merge($options, ['instructions' => $instructions]));
        }

        // Use Qwen3 TTS via FAL if configured and selected
        if ($provider === 'qwen3tts' && $this->getQwen3TtsService()->isConfigured()) {
            return $this->generateWithQwen3Tts($project, $scene, $narration, $voice, $speed, array_merge($options, ['instructions' => $instructions]));
        }

        // Fallback to OpenAI TTS
        return $this->generateWithOpenAI($project, $scene, $narration, $voice, $speed, $teamId, array_merge($options, ['instructions' => $instructions]));
    }

    /**
     * Process a multi-speaker shot, generating TTS for each speaker (VOC-06).
     *
     * Generates audio for each speaker sequentially with their assigned voice,
     * tracking timing for audio concatenation.
     *
     * @param WizardProject $project The project
     * @param array $shot Shot data with speakers array
     * @param array $options Additional options (sceneIndex, etc.)
     * @return array Result with speakers array (each with audioUrl, duration, startTime) and totalDuration
     */
    public function processMultiSpeakerShot(WizardProject $project, array $shot, array $options = []): array
    {
        $speakers = $this->getSpeakersFromShot($shot);

        if (empty($speakers)) {
            Log::warning('processMultiSpeakerShot called with no speakers (VOC-06)', [
                'shotId' => $shot['id'] ?? 'unknown',
            ]);
            return [
                'success' => false,
                'error' => 'No speakers in shot',
                'speakers' => [],
                'totalDuration' => 0,
            ];
        }

        $audioSegments = [];
        $currentTime = 0;
        $successCount = 0;

        $silentSpeakerIndices = [];

        foreach ($speakers as $index => $speaker) {
            $speakerText = trim($speaker['text'] ?? '');

            // Track empty-text speakers for silent WAV generation (processed after TTS)
            if (empty($speakerText)) {
                Log::debug('Deferring silent WAV for non-speaking character in processMultiSpeakerShot', [
                    'speaker' => $speaker['name'] ?? 'unknown',
                    'order' => $index,
                ]);
                $silentSpeakerIndices[] = $index;
                $audioSegments[$index] = null; // Placeholder to maintain order
                continue;
            }

            try {
                // Generate TTS for this speaker using their voice
                $result = $this->generateSceneVoiceover($project, [
                    'id' => ($shot['id'] ?? 'shot') . '-speaker-' . $index,
                    'narration' => $speakerText,
                ], [
                    'voice' => $speaker['voiceId'] ?? 'echo',
                    'sceneIndex' => $options['sceneIndex'] ?? null,
                ]);

                if (isset($result['url']) || isset($result['audioUrl'])) {
                    $audioUrl = $result['url'] ?? $result['audioUrl'];
                    $duration = $result['duration'] ?? $this->estimateDuration($speakerText);

                    $audioSegments[$index] = [
                        'name' => $speaker['name'],
                        'voiceId' => $speaker['voiceId'],
                        'text' => $speakerText,
                        'order' => $index,
                        'startTime' => $currentTime,
                        'duration' => $duration,
                        'audioUrl' => $audioUrl,
                    ];

                    $currentTime += $duration;
                    $successCount++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to generate TTS for speaker in multi-speaker shot (VOC-06)', [
                    'speaker' => $speaker['name'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Generate silent WAV for non-speaking faces, matching the speaking character's duration
        if (!empty($silentSpeakerIndices)) {
            $silentDuration = max(1.0, $currentTime);
            foreach ($silentSpeakerIndices as $idx) {
                $speaker = $speakers[$idx];
                $silentUrl = InfiniteTalkService::generateSilentWavUrl($project->id, $silentDuration);
                $audioSegments[$idx] = [
                    'name' => $speaker['name'],
                    'voiceId' => 'silent',
                    'text' => '',
                    'order' => $idx,
                    'startTime' => 0,
                    'duration' => $silentDuration,
                    'audioUrl' => $silentUrl,
                ];
                $successCount++;
                Log::info('Generated silent WAV for non-speaking face in processMultiSpeakerShot', [
                    'speaker' => $speaker['name'] ?? 'unknown',
                    'duration' => $silentDuration,
                ]);
            }
            // Re-index to maintain original speaker order
            ksort($audioSegments);
            $audioSegments = array_values(array_filter($audioSegments));
        }

        Log::info('Multi-speaker shot TTS complete (VOC-06)', [
            'shotId' => $shot['id'] ?? 'unknown',
            'speakersProcessed' => $successCount,
            'totalSpeakers' => count($speakers),
            'totalDuration' => $currentTime,
        ]);

        return [
            'success' => $successCount > 0,
            'speakers' => $audioSegments,
            'totalDuration' => $currentTime,
            'isMultiSpeaker' => count($audioSegments) > 1,
            'speakerCount' => $successCount,
        ];
    }

    /**
     * Estimate duration for text based on word count.
     *
     * @param string $text Text to estimate
     * @return float Estimated duration in seconds
     */
    protected function estimateDuration(string $text): float
    {
        $wordCount = str_word_count($text);
        // Average speaking rate: 150 words per minute = 2.5 words per second
        return max(1.0, $wordCount / 2.5);
    }

    /**
     * Get actual audio duration from a stored file using ffprobe.
     * Falls back to null if ffprobe is unavailable or fails.
     *
     * @param string $storagePath Full filesystem path to the audio file
     * @return float|null Actual duration in seconds, or null if probing failed
     */
    protected function getAudioDurationFromFile(string $storagePath): ?float
    {
        if (!file_exists($storagePath)) {
            return null;
        }

        try {
            // Try server ffprobe first, then system path
            $ffprobePaths = ['/home/artime/bin/ffprobe', 'ffprobe'];
            foreach ($ffprobePaths as $ffprobe) {
                $cmd = sprintf(
                    '%s -v quiet -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>/dev/null',
                    escapeshellcmd($ffprobe),
                    escapeshellarg($storagePath)
                );

                $output = @shell_exec($cmd);
                if ($output !== null && is_numeric(trim($output))) {
                    $duration = round((float) trim($output), 2);
                    if ($duration > 0) {
                        return $duration;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('VoiceoverService: Could not probe audio duration via ffprobe', [
                'file' => $storagePath,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Generate voiceover using Kokoro TTS.
     */
    protected function generateWithKokoro(WizardProject $project, array $scene, string $narration, string $voice, float $speed, array $options = []): array
    {
        $kokoroService = $this->getKokoroService();

        // Map voice if it's an OpenAI voice
        $kokoroVoice = $voice;
        if (isset($this->openaiVoices[$voice])) {
            $kokoroVoice = $kokoroService->mapOpenAIVoice($voice);
        }

        Log::info('VoiceoverService: Using Kokoro TTS', [
            'original_voice' => $voice,
            'kokoro_voice' => $kokoroVoice,
        ]);

        $result = $kokoroService->generateSpeech($narration, $kokoroVoice, $project->id, [
            'max_wait' => 120,
            'poll_interval' => 2,
        ]);

        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'Kokoro TTS generation failed');
        }

        $wordCount = str_word_count($narration);
        $estimatedDuration = $result['duration'] ?? (($wordCount / 150) * 60 / $speed);

        // Probe actual duration from the generated file
        $audioFilePath = $result['audioPath'] ?? '';
        $storagePath = !empty($audioFilePath) ? Storage::disk('public')->path($audioFilePath) : null;
        $actualDuration = $storagePath ? $this->getAudioDurationFromFile($storagePath) : null;
        $duration = $actualDuration ?? $estimatedDuration;

        // Create asset record
        $asset = WizardAsset::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'type' => WizardAsset::TYPE_VOICEOVER,
            'name' => ($scene['title'] ?? $scene['id']) . ' - Voiceover (Kokoro)',
            'path' => $result['audioPath'],
            'url' => $result['audioUrl'],
            'mime_type' => 'audio/flac',
            'scene_index' => $options['sceneIndex'] ?? null,
            'scene_id' => $scene['id'],
            'metadata' => [
                'voice' => $kokoroVoice,
                'voiceConfig' => $result['voiceConfig'],
                'speed' => $speed,
                'narration' => $narration,
                'wordCount' => $wordCount,
                'estimatedDuration' => $estimatedDuration,
                'actualDuration' => $actualDuration,
                'provider' => 'kokoro',
                'jobId' => $result['jobId'] ?? null,
            ],
        ]);

        return [
            'success' => true,
            'audioUrl' => $asset->url,
            'assetId' => $asset->id,
            'duration' => $duration,
            'voice' => $kokoroVoice,
            'provider' => 'kokoro',
        ];
    }

    /**
     * Generate voiceover using Qwen3 TTS via FAL.AI.
     */
    protected function generateWithQwen3Tts(WizardProject $project, array $scene, string $narration, string $voice, float $speed, array $options = []): array
    {
        $qwen3Service = $this->getQwen3TtsService();

        // Map voice if it's an OpenAI voice
        $qwenVoice = $voice;
        if (isset($this->openaiVoices[$voice])) {
            $qwenVoice = $qwen3Service->mapOpenAIVoice($voice);
        }

        Log::info('VoiceoverService: Using Qwen3 TTS', [
            'original_voice' => $voice,
            'qwen_voice' => $qwenVoice,
        ]);

        $result = $qwen3Service->generateSpeech($narration, $qwenVoice, $project->id, [
            'emotion' => $options['emotion'] ?? null,
            'mood' => $options['mood'] ?? null,
            'instructions' => $options['instructions'] ?? '',
            'characterDescription' => $options['characterDescription'] ?? null,
            'speechType' => $options['speechType'] ?? 'monologue',
            'characterName' => $options['characterName'] ?? null,
            'dialogueText' => $options['dialogueText'] ?? $narration,
        ]);

        if (!$result['success']) {
            throw new \Exception($result['error'] ?? 'Qwen3 TTS generation failed');
        }

        $wordCount = str_word_count($narration);
        $duration = $result['duration'] ?? (($wordCount / 150) * 60 / $speed);

        // Create asset record
        $asset = WizardAsset::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'type' => WizardAsset::TYPE_VOICEOVER,
            'name' => ($scene['title'] ?? $scene['id']) . ' - Voiceover (Qwen3)',
            'path' => $result['audioPath'],
            'url' => $result['audioUrl'],
            'mime_type' => 'audio/mpeg',
            'scene_index' => $options['sceneIndex'] ?? null,
            'scene_id' => $scene['id'],
            'metadata' => [
                'voice' => $qwenVoice,
                'voiceConfig' => $result['voiceConfig'] ?? null,
                'speed' => $speed,
                'narration' => $narration,
                'wordCount' => $wordCount,
                'duration' => $duration,
                'provider' => 'qwen3tts',
            ],
        ]);

        return [
            'success' => true,
            'audioUrl' => $asset->url,
            'assetId' => $asset->id,
            'duration' => $duration,
            'voice' => $qwenVoice,
            'provider' => 'qwen3tts',
        ];
    }

    /**
     * Generate voiceover using OpenAI TTS.
     */
    protected function generateWithOpenAI(WizardProject $project, array $scene, string $narration, string $voice, float $speed, $teamId, array $options = []): array
    {
        // Build speech options
        $speechOptions = ['voice' => $voice];
        if (!empty($options['instructions'])) {
            $speechOptions['instructions'] = $options['instructions'];
        }

        // Generate audio using OpenAI TTS
        $result = AI::process($narration, 'speech', $speechOptions, $teamId);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        $audioContent = $result['data'][0] ?? null;
        if (!$audioContent) {
            throw new \Exception('No audio generated');
        }

        // Store the audio file
        $filename = Str::slug($scene['id']) . '-voiceover-' . time() . '.mp3';
        $path = "wizard-projects/{$project->id}/audio/{$filename}";

        Storage::disk('public')->put($path, $audioContent);

        // Get audio duration - probe actual file, fall back to word-count estimate
        $wordCount = str_word_count($narration);
        $estimatedDuration = ($wordCount / 150) * 60 / $speed; // 150 words per minute

        $storagePath = Storage::disk('public')->path($path);
        $actualDuration = $this->getAudioDurationFromFile($storagePath);
        $duration = $actualDuration ?? $estimatedDuration;

        // Create asset record
        $asset = WizardAsset::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'type' => WizardAsset::TYPE_VOICEOVER,
            'name' => ($scene['title'] ?? $scene['id']) . ' - Voiceover',
            'path' => $path,
            'url' => url('/files/' . $path),
            'mime_type' => 'audio/mpeg',
            'scene_index' => $options['sceneIndex'] ?? null,
            'scene_id' => $scene['id'],
            'metadata' => [
                'voice' => $voice,
                'speed' => $speed,
                'narration' => $narration,
                'wordCount' => $wordCount,
                'estimatedDuration' => $estimatedDuration,
                'actualDuration' => $actualDuration,
                'provider' => 'openai',
            ],
        ]);

        return [
            'success' => true,
            'audioUrl' => $asset->url,
            'assetId' => $asset->id,
            'duration' => $duration,
            'voice' => $voice,
            'provider' => 'openai',
        ];
    }

    /**
     * Generate voiceovers for all scenes.
     */
    public function generateAllVoiceovers(WizardProject $project, array $options = [], callable $progressCallback = null): array
    {
        $scenes = $project->getScenes();
        $results = [];
        $voice = $options['voice'] ?? 'nova';
        $speed = $options['speed'] ?? 1.0;

        foreach ($scenes as $index => $scene) {
            try {
                $result = $this->generateSceneVoiceover($project, $scene, [
                    'voice' => $voice,
                    'speed' => $speed,
                    'sceneIndex' => $index,
                ]);
                $results[$scene['id']] = $result;

                if ($progressCallback) {
                    $progressCallback($index + 1, count($scenes), $scene['id']);
                }
            } catch (\Exception $e) {
                $results[$scene['id']] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get available voices.
     */
    /**
     * Get available voices based on the configured provider.
     */
    public function getAvailableVoices(?string $provider = null): array
    {
        $provider = $provider ?? $this->getProvider();

        if ($provider === 'kokoro' && $this->getKokoroService()->isConfigured()) {
            return $this->getKokoroService()->getAvailableVoices();
        }

        if ($provider === 'qwen3tts' && $this->getQwen3TtsService()->isConfigured()) {
            return $this->getQwen3TtsService()->getAvailableVoices();
        }

        return $this->openaiVoices;
    }

    /**
     * Get all voices for all providers (for UI that shows both).
     */
    public function getAllVoices(): array
    {
        return [
            'kokoro' => $this->getKokoroService()->getAvailableVoices(),
            'openai' => $this->openaiVoices,
            'qwen3tts' => $this->getQwen3TtsService()->getAvailableVoices(),
        ];
    }

    /**
     * Get the current active provider name.
     */
    public function getActiveProvider(): string
    {
        return $this->getProvider();
    }

    /**
     * Preview voice with sample text.
     */
    public function previewVoice(string $voice, string $sampleText = null, array $options = []): string
    {
        $provider = $this->getProvider();
        $voices = $this->getAvailableVoices($provider);
        $text = $sampleText ?? 'This is a preview of the ' . ($voices[$voice]['name'] ?? $voice) . ' voice.';
        $teamId = $options['teamId'] ?? session('current_team_id', 0);

        $result = AI::process($text, 'speech', [
            'voice' => $voice,
        ], $teamId);

        if (!empty($result['error'])) {
            throw new \Exception($result['error']);
        }

        $audioContent = $result['data'][0] ?? '';

        // Return base64 encoded audio for preview
        return 'data:audio/mpeg;base64,' . base64_encode($audioContent);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // NARRATOR VS CHARACTER VOICE SEPARATION
    // Supports mixed audio: narrator (off-screen) + character dialogue (lip-sync)
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Parse dialogue/narration text to extract speaker segments.
     * Supports formats:
     * - "SPEAKER: text" (standard dialogue format)
     * - "[NARRATOR] text" (narrator segments)
     * - Plain text (narrator by default)
     *
     * @param string $text The narration/dialogue text
     * @param string $defaultSpeaker Default speaker for unmarked text
     * @return array Array of ['speaker' => string, 'text' => string, 'isNarrator' => bool]
     */
    public function parseDialogue(string $text, string $defaultSpeaker = 'NARRATOR'): array
    {
        $segments = [];
        $lines = preg_split('/\n+/', trim($text));
        $currentSpeaker = $defaultSpeaker;
        $currentText = '';
        $isNarrator = true;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Check for narrator tag: [NARRATOR] text or [Narrator] text
            if (preg_match('/^\[NARRATOR\]\s*(.*)$/i', $line, $matches)) {
                // Save previous segment
                if (!empty($currentText)) {
                    $segments[] = [
                        'speaker' => $currentSpeaker,
                        'text' => trim($currentText),
                        'isNarrator' => $isNarrator,
                    ];
                }
                $currentSpeaker = 'NARRATOR';
                $currentText = $matches[1];
                $isNarrator = true;
                continue;
            }

            // Check for character dialogue: SPEAKER: text
            if (preg_match('/^([A-Z][A-Za-z\s\-\']+):\s*(.+)$/u', $line, $matches)) {
                // Save previous segment
                if (!empty($currentText)) {
                    $segments[] = [
                        'speaker' => $currentSpeaker,
                        'text' => trim($currentText),
                        'isNarrator' => $isNarrator,
                    ];
                }
                $currentSpeaker = trim($matches[1]);
                $currentText = $matches[2];
                $isNarrator = (strtoupper($currentSpeaker) === 'NARRATOR');
                continue;
            }

            // Check for parenthetical direction (skip)
            if (preg_match('/^\(.+\)$/', $line)) {
                continue;
            }

            // Continuation of current speaker's text
            $currentText .= ' ' . $line;
        }

        // Save final segment
        if (!empty($currentText)) {
            $segments[] = [
                'speaker' => $currentSpeaker,
                'text' => trim($currentText),
                'isNarrator' => $isNarrator,
            ];
        }

        return $segments;
    }

    /**
     * Detect unique speakers from dialogue text.
     *
     * @param string $text The narration/dialogue text
     * @return array List of unique speaker names
     */
    public function detectSpeakers(string $text): array
    {
        $segments = $this->parseDialogue($text);
        $speakers = [];

        foreach ($segments as $segment) {
            if (!$segment['isNarrator'] && !empty($segment['speaker'])) {
                $speakers[$segment['speaker']] = true;
            }
        }

        return array_keys($speakers);
    }

    /**
     * Determine voice ID for a character based on Character Bible or defaults.
     *
     * @param string $speakerName The character/speaker name
     * @param array $characterBible The Character Bible data
     * @param string $narratorVoice Default voice for narrator
     * @return string Voice ID for the active provider
     */
    public function getVoiceForSpeaker(string $speakerName, array $characterBible = [], string $narratorVoice = 'fable'): string
    {
        $speakerUpper = strtoupper(trim($speakerName));
        $provider = $this->getProvider();
        $isKokoro = $provider === 'kokoro' && $this->getKokoroService()->isConfigured();
        $isQwen3 = $provider === 'qwen3tts' && $this->getQwen3TtsService()->isConfigured();

        // Narrator uses designated narrator voice
        if ($speakerUpper === 'NARRATOR') {
            if ($isKokoro) {
                return $this->getKokoroService()->mapOpenAIVoice($narratorVoice);
            }
            if ($isQwen3) {
                return $this->getQwen3TtsService()->mapOpenAIVoice($narratorVoice);
            }
            return $narratorVoice;
        }

        // Look up in Character Bible
        $characters = $characterBible['characters'] ?? [];
        foreach ($characters as $char) {
            $charName = strtoupper(trim($char['name'] ?? ''));
            if ($charName === $speakerUpper) {
                // Check for configured voice (may have provider-specific voice ID)
                if (is_array($char['voice'] ?? null) && !empty($char['voice']['id'])) {
                    $voiceId = $char['voice']['id'];
                    // Map to Kokoro if needed
                    if ($isKokoro && isset($this->openaiVoices[$voiceId])) {
                        return $this->getKokoroService()->mapOpenAIVoice($voiceId);
                    }
                    if ($isQwen3 && isset($this->openaiVoices[$voiceId])) {
                        return $this->getQwen3TtsService()->mapOpenAIVoice($voiceId);
                    }
                    return $voiceId;
                }
                // Legacy string voice
                if (is_string($char['voice'] ?? null) && !empty($char['voice'])) {
                    $voiceId = $char['voice'];
                    if ($isKokoro && isset($this->openaiVoices[$voiceId])) {
                        return $this->getKokoroService()->mapOpenAIVoice($voiceId);
                    }
                    if ($isQwen3 && isset($this->openaiVoices[$voiceId])) {
                        return $this->getQwen3TtsService()->mapOpenAIVoice($voiceId);
                    }
                    return $voiceId;
                }
                // Determine by gender
                $gender = strtolower($char['gender'] ?? $char['voice']['gender'] ?? '');
                if (str_contains($gender, 'female') || str_contains($gender, 'woman')) {
                    return $isKokoro ? 'af_nicole' : ($isQwen3 ? 'Vivian' : 'nova');
                } elseif (str_contains($gender, 'male') || str_contains($gender, 'man')) {
                    return $isKokoro ? 'am_michael' : ($isQwen3 ? 'Dylan' : 'onyx');
                }
            }
        }

        // Fallback: use speaker name hash to assign consistent voice
        $hash = crc32($speakerUpper);
        if ($isKokoro) {
            $characterVoices = ['am_michael', 'am_adam', 'af_nicole', 'af_bella', 'bm_lewis'];
        } elseif ($isQwen3) {
            $characterVoices = ['Dylan', 'Ryan', 'Vivian', 'Serena', 'Aiden'];
        } else {
            $characterVoices = ['echo', 'onyx', 'nova', 'shimmer', 'alloy'];
        }
        return $characterVoices[$hash % count($characterVoices)];
    }

    /**
     * Generate dialogue audio with multiple character voices.
     * Each segment is generated with the appropriate voice.
     *
     * @param WizardProject $project The project
     * @param array $scene Scene data with narration
     * @param array $options Options including characterBible, narratorVoice, teamId
     * @return array Result with individual segment audio and combined URL
     */
    public function generateDialogueAudio(WizardProject $project, array $scene, array $options = []): array
    {
        $narration = $scene['narration'] ?? '';
        $characterBible = $options['characterBible'] ?? [];
        $narratorVoice = $options['narratorVoice'] ?? 'fable';
        $speed = $options['speed'] ?? 1.0;
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);

        if (empty($narration)) {
            throw new \Exception('No narration text provided');
        }

        // Parse dialogue into segments
        $segments = $this->parseDialogue($narration);

        if (empty($segments)) {
            throw new \Exception('No dialogue segments found');
        }

        $audioSegments = [];
        $totalDuration = 0;

        // Generate audio for each segment
        foreach ($segments as $index => $segment) {
            $voice = $this->getVoiceForSpeaker($segment['speaker'], $characterBible, $narratorVoice);

            $result = AI::process($segment['text'], 'speech', [
                'voice' => $voice,
            ], $teamId);

            if (!empty($result['error'])) {
                throw new \Exception("Failed to generate audio for {$segment['speaker']}: {$result['error']}");
            }

            $audioContent = $result['data'][0] ?? null;
            if (!$audioContent) {
                continue;
            }

            // Estimate duration
            $wordCount = str_word_count($segment['text']);
            $duration = ($wordCount / 150) * 60 / $speed;
            $totalDuration += $duration;

            $audioSegments[] = [
                'index' => $index,
                'speaker' => $segment['speaker'],
                'text' => $segment['text'],
                'voice' => $voice,
                'isNarrator' => $segment['isNarrator'],
                'audioContent' => $audioContent,
                'duration' => $duration,
                'needsLipSync' => !$segment['isNarrator'], // Characters need lip-sync
            ];
        }

        // Store individual segment files and combined audio
        $basePath = "wizard-projects/{$project->id}/audio";
        $sceneSlug = Str::slug($scene['id']);
        $timestamp = time();

        $segmentResults = [];
        foreach ($audioSegments as $seg) {
            $filename = "{$sceneSlug}-segment-{$seg['index']}-{$timestamp}.mp3";
            $path = "{$basePath}/{$filename}";
            Storage::disk('public')->put($path, $seg['audioContent']);

            $segmentResults[] = [
                'speaker' => $seg['speaker'],
                'text' => $seg['text'],
                'voice' => $seg['voice'],
                'isNarrator' => $seg['isNarrator'],
                'needsLipSync' => $seg['needsLipSync'],
                'audioUrl' => url('/files/' . $path),
                'duration' => $seg['duration'],
            ];
        }

        // Create combined audio (simple concatenation - for playback preview)
        $combinedContent = '';
        foreach ($audioSegments as $seg) {
            $combinedContent .= $seg['audioContent'];
        }

        $combinedFilename = "{$sceneSlug}-dialogue-combined-{$timestamp}.mp3";
        $combinedPath = "{$basePath}/{$combinedFilename}";
        Storage::disk('public')->put($combinedPath, $combinedContent);

        // Create asset record for combined audio
        $asset = WizardAsset::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'type' => WizardAsset::TYPE_VOICEOVER,
            'name' => ($scene['title'] ?? $scene['id']) . ' - Dialogue',
            'path' => $combinedPath,
            'url' => url('/files/' . $combinedPath),
            'mime_type' => 'audio/mpeg',
            'scene_index' => $options['sceneIndex'] ?? null,
            'scene_id' => $scene['id'],
            'metadata' => [
                'type' => 'dialogue',
                'segments' => $segmentResults,
                'narratorVoice' => $narratorVoice,
                'totalDuration' => $totalDuration,
                'speakerCount' => count(array_unique(array_column($segmentResults, 'speaker'))),
            ],
        ]);

        return [
            'success' => true,
            'audioUrl' => $asset->url,
            'assetId' => $asset->id,
            'duration' => $totalDuration,
            'segments' => $segmentResults,
            'speakers' => array_unique(array_column($segmentResults, 'speaker')),
        ];
    }

    /**
     * Generate mixed narration: combines narrator segments with character dialogue.
     * Narrator segments are standard voiceover, character segments can be lip-synced.
     *
     * @param WizardProject $project The project
     * @param array $scene Scene data
     * @param array $options Options including characterBible, narratorVoice
     * @return array Result with narrator and character audio separated
     */
    public function generateMixedNarration(WizardProject $project, array $scene, array $options = []): array
    {
        $narration = $scene['narration'] ?? '';
        $characterBible = $options['characterBible'] ?? [];
        $narratorVoice = $options['narratorVoice'] ?? 'fable';
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);

        // Parse the narration
        $segments = $this->parseDialogue($narration);

        // Separate narrator and character segments
        $narratorSegments = [];
        $characterSegments = [];

        foreach ($segments as $seg) {
            if ($seg['isNarrator']) {
                $narratorSegments[] = $seg;
            } else {
                $characterSegments[] = $seg;
            }
        }

        $result = [
            'success' => true,
            'narrator' => null,
            'characters' => [],
            'combined' => null,
            'timeline' => [],
        ];

        // Generate narrator audio (combined into single track)
        if (!empty($narratorSegments)) {
            $narratorText = implode(' ', array_column($narratorSegments, 'text'));

            // Validate before TTS
            if (empty(trim($narratorText))) {
                Log::warning('Empty narrator text skipped in VoiceoverService', [
                    'sceneId' => $scene['id'] ?? null,
                ]);
            } else {
                $audioResult = AI::process($narratorText, 'speech', [
                    'voice' => $narratorVoice,
                ], $teamId);

                if (empty($audioResult['error']) && !empty($audioResult['data'][0])) {
                    $path = "wizard-projects/{$project->id}/audio/" . Str::slug($scene['id']) . "-narrator-" . time() . ".mp3";
                    Storage::disk('public')->put($path, $audioResult['data'][0]);

                    $wordCount = str_word_count($narratorText);
                    $result['narrator'] = [
                        'voice' => $narratorVoice,
                        'audioUrl' => url('/files/' . $path),
                        'duration' => ($wordCount / 150) * 60,
                        'text' => $narratorText,
                    ];
                }
            }
        }

        // Generate character audio (separate files for lip-sync)
        foreach ($characterSegments as $index => $seg) {
            // Validate text before TTS
            if (empty(trim($seg['text'] ?? ''))) {
                Log::warning('Empty character segment text skipped in VoiceoverService', [
                    'speaker' => $seg['speaker'] ?? 'unknown',
                    'index' => $index,
                ]);
                continue;
            }

            $voice = $this->getVoiceForSpeaker($seg['speaker'], $characterBible, $narratorVoice);

            $audioResult = AI::process($seg['text'], 'speech', [
                'voice' => $voice,
            ], $teamId);

            if (empty($audioResult['error']) && !empty($audioResult['data'][0])) {
                $path = "wizard-projects/{$project->id}/audio/" . Str::slug($scene['id']) . "-char-{$index}-" . time() . ".mp3";
                Storage::disk('public')->put($path, $audioResult['data'][0]);

                $wordCount = str_word_count($seg['text']);
                $result['characters'][] = [
                    'speaker' => $seg['speaker'],
                    'voice' => $voice,
                    'audioUrl' => url('/files/' . $path),
                    'duration' => ($wordCount / 150) * 60,
                    'text' => $seg['text'],
                    'needsLipSync' => true,
                ];
            }
        }

        // Build timeline for sequenced playback
        foreach ($segments as $index => $seg) {
            $timeline = [
                'index' => $index,
                'speaker' => $seg['speaker'],
                'isNarrator' => $seg['isNarrator'],
                'text' => $seg['text'],
            ];

            if ($seg['isNarrator']) {
                $timeline['audioSource'] = 'narrator';
            } else {
                // Find matching character audio
                foreach ($result['characters'] as $charAudio) {
                    if ($charAudio['speaker'] === $seg['speaker'] && $charAudio['text'] === $seg['text']) {
                        $timeline['audioSource'] = 'character';
                        $timeline['audioUrl'] = $charAudio['audioUrl'];
                        $timeline['needsLipSync'] = true;
                        break;
                    }
                }
            }

            $result['timeline'][] = $timeline;
        }

        return $result;
    }

    /**
     * Get character voice mapping for a scene.
     * Maps character names to their configured voices.
     *
     * @param array $characterBible The Character Bible data
     * @param array $speakers List of speaker names in the scene
     * @return array Map of speaker name => voice config
     */
    public function getCharacterVoiceMapping(array $characterBible, array $speakers): array
    {
        $mapping = [];

        foreach ($speakers as $speaker) {
            $speakerUpper = strtoupper(trim($speaker));

            // Look up in Character Bible
            $found = false;
            foreach ($characterBible['characters'] ?? [] as $char) {
                $charName = strtoupper(trim($char['name'] ?? ''));
                if ($charName === $speakerUpper) {
                    $mapping[$speaker] = [
                        'voiceId' => $this->getVoiceForSpeaker($speaker, $characterBible),
                        'voiceConfig' => $char['voice'] ?? null,
                        'characterIndex' => array_search($char, $characterBible['characters']),
                        'hasReference' => !empty($char['referenceImageBase64']),
                    ];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $mapping[$speaker] = [
                    'voiceId' => $this->getVoiceForSpeaker($speaker, $characterBible),
                    'voiceConfig' => null,
                    'characterIndex' => null,
                    'hasReference' => false,
                ];
            }
        }

        return $mapping;
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // SPEECH SEGMENTS - Dynamic Multi-Type Audio Generation
    // Supports mixed narration/dialogue/internal/monologue within a single scene
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Generate audio for all speech segments in a scene.
     * Each segment gets its own audio track with appropriate voice.
     *
     * @param WizardProject $project The project
     * @param array $scene Scene data with speechSegments array
     * @param array $options Options including characterBible, narratorVoice, teamId
     * @return array Result with segment audio URLs and timeline
     */
    public function generateSegmentedAudio(WizardProject $project, array $scene, array $options = []): array
    {
        $speechSegments = $scene['speechSegments'] ?? [];
        $characterBible = $options['characterBible'] ?? [];
        $narratorVoice = $options['narratorVoice'] ?? 'fable';
        $speed = $options['speed'] ?? 1.0;
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);

        // If no segments, try to parse from narration text
        if (empty($speechSegments)) {
            $parser = new SpeechSegmentParser();
            $narration = $scene['narration'] ?? $scene['voiceover']['text'] ?? '';

            if (empty($narration)) {
                throw new \Exception('No speech segments or narration text provided');
            }

            $speechSegments = $parser->parse($narration, $characterBible);
        }

        // Convert array data to SpeechSegment objects if needed
        $segments = array_map(function ($seg) {
            return $seg instanceof SpeechSegment ? $seg : SpeechSegment::fromArray($seg);
        }, $speechSegments);

        if (empty($segments)) {
            throw new \Exception('No speech segments to generate audio for');
        }

        Log::info('VoiceoverService: Generating segmented audio', [
            'project_id' => $project->id,
            'scene_id' => $scene['id'] ?? 'unknown',
            'segment_count' => count($segments),
        ]);

        $results = [];
        $totalDuration = 0;
        $basePath = "wizard-projects/{$project->id}/audio";
        $sceneSlug = Str::slug($scene['id'] ?? 'scene');
        $timestamp = time();

        foreach ($segments as $index => $segment) {
            try {
                // Determine voice for this segment
                $voice = $this->getVoiceForSegment($segment, $characterBible, $narratorVoice);

                // Apply emotional direction if segment has emotion (VOC-11)
                $textToSpeak = $segment->text;
                $instructions = '';

                if (!empty($segment->emotion)) {
                    $provider = $this->getProvider();
                    $enhanced = $this->enhanceTextWithVoiceDirection($segment->text, $segment->emotion, $provider);
                    $textToSpeak = $enhanced['text'];
                    $instructions = $enhanced['instructions'];
                }

                // Build speech options with instructions if available
                $speechOptions = ['voice' => $voice];
                if (!empty($instructions)) {
                    $speechOptions['instructions'] = $instructions;
                }

                // Generate audio
                $audioResult = AI::process($textToSpeak, 'speech', $speechOptions, $teamId);

                if (!empty($audioResult['error'])) {
                    Log::warning('VoiceoverService: Segment audio generation failed', [
                        'segment_id' => $segment->id,
                        'error' => $audioResult['error'],
                    ]);
                    continue;
                }

                $audioContent = $audioResult['data'][0] ?? null;
                if (!$audioContent) {
                    continue;
                }

                // Calculate duration
                $wordCount = str_word_count($segment->text);
                $duration = ($wordCount / 150) * 60 / $speed;

                // Store audio file
                $filename = "{$sceneSlug}-seg-{$index}-{$segment->type}-{$timestamp}.mp3";
                $path = "{$basePath}/{$filename}";
                Storage::disk('public')->put($path, $audioContent);

                $audioUrl = url('/files/' . $path);

                // Update segment with audio info
                $segment->audioUrl = $audioUrl;
                $segment->duration = $duration;
                $segment->startTime = $totalDuration;
                $segment->voiceId = $voice;

                $totalDuration += $duration;

                $results[] = [
                    'id' => $segment->id,
                    'type' => $segment->type,
                    'speaker' => $segment->speaker,
                    'text' => $segment->text,
                    'voice' => $voice,
                    'audioUrl' => $audioUrl,
                    'duration' => $duration,
                    'startTime' => $segment->startTime,
                    'needsLipSync' => $segment->needsLipSync,
                    'characterId' => $segment->characterId,
                    'order' => $segment->order,
                    'emotionApplied' => !empty($segment->emotion),
                    'emotion' => $segment->emotion,
                ];
            } catch (\Exception $e) {
                Log::error('VoiceoverService: Failed to generate segment audio', [
                    'segment_id' => $segment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Create combined audio file for playback
        $combinedUrl = null;
        if (!empty($results)) {
            $combinedContent = '';
            foreach ($results as $res) {
                $segPath = str_replace(url('/files/'), '', $res['audioUrl']);
                $content = Storage::disk('public')->get($segPath);
                if ($content) {
                    $combinedContent .= $content;
                }
            }

            if (!empty($combinedContent)) {
                $combinedFilename = "{$sceneSlug}-segments-combined-{$timestamp}.mp3";
                $combinedPath = "{$basePath}/{$combinedFilename}";
                Storage::disk('public')->put($combinedPath, $combinedContent);
                $combinedUrl = url('/files/' . $combinedPath);
            }
        }

        // Create asset record
        $asset = WizardAsset::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'type' => WizardAsset::TYPE_VOICEOVER,
            'name' => ($scene['title'] ?? $scene['id'] ?? 'Scene') . ' - Segmented Audio',
            'path' => $combinedPath ?? '',
            'url' => $combinedUrl ?? '',
            'mime_type' => 'audio/mpeg',
            'scene_index' => $options['sceneIndex'] ?? null,
            'scene_id' => $scene['id'] ?? null,
            'metadata' => [
                'type' => 'segmented',
                'segments' => $results,
                'narratorVoice' => $narratorVoice,
                'totalDuration' => $totalDuration,
                'segmentCount' => count($results),
                'lipSyncCount' => count(array_filter($results, fn($r) => $r['needsLipSync'])),
            ],
        ]);

        return [
            'success' => true,
            'audioUrl' => $combinedUrl,
            'assetId' => $asset->id,
            'duration' => $totalDuration,
            'segments' => $results,
            'statistics' => [
                'total' => count($results),
                'needsLipSync' => count(array_filter($results, fn($r) => $r['needsLipSync'])),
                'voiceoverOnly' => count(array_filter($results, fn($r) => !$r['needsLipSync'])),
                'speakers' => array_unique(array_filter(array_column($results, 'speaker'))),
            ],
        ];
    }

    /**
     * Get the appropriate voice for a speech segment.
     *
     * @param SpeechSegment $segment The segment
     * @param array $characterBible Character Bible for voice lookup
     * @param string $narratorVoice Default narrator voice
     * @return string Voice ID
     */
    protected function getVoiceForSegment(SpeechSegment $segment, array $characterBible, string $narratorVoice): string
    {
        // Narrator segments use narrator voice
        if ($segment->isNarrator()) {
            return $narratorVoice;
        }

        // If segment has a pre-assigned voice ID, use it
        if (!empty($segment->voiceId)) {
            return $segment->voiceId;
        }

        // Look up voice by speaker name in Character Bible
        if (!empty($segment->speaker)) {
            return $this->getVoiceForSpeaker($segment->speaker, $characterBible, $narratorVoice);
        }

        // Fallback to narrator voice for internal thoughts without speaker
        if ($segment->isInternal()) {
            return $narratorVoice;
        }

        return $narratorVoice;
    }

    /**
     * Get speakers array from shot data (VOC-06).
     *
     * Handles both new multi-speaker format and legacy single-speaker format
     * for backward compatibility.
     *
     * @param array $shot Shot data
     * @return array Array of speaker entries with name, voiceId, text, order
     */
    protected function getSpeakersFromShot(array $shot): array
    {
        // Prefer new multi-speaker array if present
        if (!empty($shot['speakers']) && is_array($shot['speakers'])) {
            return $shot['speakers'];
        }

        // Fall back to single speaker for legacy shots
        if (!empty($shot['speakingCharacter'])) {
            return [[
                'name' => $shot['speakingCharacter'],
                'voiceId' => $shot['voiceId'] ?? null,
                'text' => $shot['dialogue'] ?? $shot['monologue'] ?? '',
                'order' => 0,
            ]];
        }

        return [];
    }

    /**
     * Parse scene narration into speech segments using SpeechSegmentParser.
     * This is the preferred method for extracting segments from raw text.
     *
     * @param string $narration The raw narration text
     * @param array $characterBible Character Bible for speaker validation
     * @return SpeechSegment[] Array of parsed segments
     */
    public function parseNarrationToSegments(string $narration, array $characterBible = []): array
    {
        $parser = new SpeechSegmentParser();
        return $parser->parse($narration, $characterBible);
    }

    /**
     * Get segment statistics for a scene.
     *
     * @param array $segments Array of segments (SpeechSegment objects or arrays)
     * @return array Statistics about the segments
     */
    public function getSegmentStatistics(array $segments): array
    {
        $parser = new SpeechSegmentParser();
        return $parser->getStatistics($segments);
    }

    /**
     * Validate segments against Character Bible.
     *
     * @param array $segments Array of segments
     * @param array $characterBible Character Bible data
     * @return array Validation result with warnings
     */
    public function validateSegmentSpeakers(array $segments, array $characterBible): array
    {
        $parser = new SpeechSegmentParser();
        return $parser->validateSpeakers($segments, $characterBible);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // MULTI-SPEAKER DIALOGUE GENERATION (VOC-10)
    // Handles conversations with 2+ characters in single unified audio generation
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Generate unified multi-speaker dialogue audio (VOC-10).
     *
     * Builds dialogue structure from segments, generates TTS for each turn
     * with correct voice from registry, applies emotional direction, and
     * produces combined audio with timing offsets.
     *
     * @param WizardProject $project The project
     * @param array $segments Dialogue segments (arrays or SpeechSegment objects)
     * @param array $options {
     *     characterBible: array, Character Bible for voice lookup
     *     narratorVoice: string, Default narrator voice (default 'fable')
     *     speed: float, TTS speed multiplier (default 1.0)
     *     teamId: int, Team ID for API billing
     *     includeEmotions: bool, Apply emotional direction (default true)
     * }
     * @return array{
     *     success: bool,
     *     combinedUrl: string,
     *     assetId: int,
     *     turns: array,
     *     totalDuration: float,
     *     statistics: array
     * }
     */
    public function generateMultiSpeakerDialogue(WizardProject $project, array $segments, array $options = []): array
    {
        $characterBible = $options['characterBible'] ?? [];
        $narratorVoice = $options['narratorVoice'] ?? 'fable';
        $speed = $options['speed'] ?? 1.0;
        $teamId = $options['teamId'] ?? $project->team_id ?? session('current_team_id', 0);
        $includeEmotions = $options['includeEmotions'] ?? true;
        $provider = $options['provider'] ?? $this->getProvider();

        if (empty($segments)) {
            throw new \Exception('No dialogue segments provided');
        }

        Log::info('VoiceoverService: Starting multi-speaker dialogue generation (VOC-10)', [
            'project_id' => $project->id,
            'segmentCount' => count($segments),
            'provider' => $provider,
        ]);

        // Build dialogue structure using MultiSpeakerDialogueBuilder
        $dialogueBuilder = app(MultiSpeakerDialogueBuilder::class);
        $dialogue = $dialogueBuilder->buildDialogue($segments, $characterBible, $narratorVoice);

        if (empty($dialogue['turns'])) {
            throw new \Exception('No valid dialogue turns after building');
        }

        // Generate audio for each turn
        $turnResults = [];
        $audioContents = [];
        $currentTime = 0;
        $basePath = "wizard-projects/{$project->id}/audio";
        $timestamp = time();
        $dialogueId = 'dialogue-' . Str::random(8);

        foreach ($dialogue['turns'] as $index => $turn) {
            try {
                // Apply emotional direction if enabled and emotion exists (VOC-11)
                $textToSpeak = $turn['text'];
                $instructions = '';

                if ($includeEmotions && !empty($turn['emotion'])) {
                    $enhanced = $this->enhanceTextWithVoiceDirection($textToSpeak, $turn['emotion'], $provider);
                    $textToSpeak = $enhanced['text'];
                    $instructions = $enhanced['instructions'];
                }

                // Build speech options
                $speechOptions = ['voice' => $turn['voiceId']];
                if (!empty($instructions)) {
                    $speechOptions['instructions'] = $instructions;
                }

                // Generate TTS
                $audioResult = AI::process($textToSpeak, 'speech', $speechOptions, $teamId);

                if (!empty($audioResult['error'])) {
                    Log::warning('VoiceoverService: Turn TTS failed (VOC-10)', [
                        'turnIndex' => $index,
                        'speaker' => $turn['speaker'],
                        'error' => $audioResult['error'],
                    ]);
                    continue;
                }

                $audioContent = $audioResult['data'][0] ?? null;
                if (!$audioContent) {
                    continue;
                }

                // Store individual turn audio
                $turnFilename = "{$dialogueId}-turn-{$index}-{$timestamp}.mp3";
                $turnPath = "{$basePath}/{$turnFilename}";
                Storage::disk('public')->put($turnPath, $audioContent);

                // Calculate actual duration (estimate if not provided)
                $wordCount = str_word_count($turn['text']);
                $duration = ($wordCount / 150) * 60 / $speed;

                $turnResult = [
                    'id' => $turn['id'],
                    'order' => $index,
                    'speaker' => $turn['speaker'],
                    'text' => $turn['text'],
                    'voiceId' => $turn['voiceId'],
                    'emotion' => $turn['emotion'],
                    'emotionApplied' => $includeEmotions && !empty($turn['emotion']),
                    'audioUrl' => url('/files/' . $turnPath),
                    'startTime' => $currentTime,
                    'duration' => $duration,
                    'endTime' => $currentTime + $duration,
                    'needsLipSync' => $turn['needsLipSync'],
                ];

                $turnResults[] = $turnResult;
                $audioContents[] = $audioContent;

                // Advance time with small pause between speakers
                $currentTime += $duration + 0.3;

            } catch (\Exception $e) {
                Log::error('VoiceoverService: Turn generation failed (VOC-10)', [
                    'turnIndex' => $index,
                    'speaker' => $turn['speaker'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (empty($turnResults)) {
            throw new \Exception('No turns successfully generated');
        }

        // Create combined audio
        $combinedContent = implode('', $audioContents);
        $combinedFilename = "{$dialogueId}-combined-{$timestamp}.mp3";
        $combinedPath = "{$basePath}/{$combinedFilename}";
        Storage::disk('public')->put($combinedPath, $combinedContent);

        $combinedUrl = url('/files/' . $combinedPath);
        $totalDuration = end($turnResults)['endTime'];

        // Create asset record
        $asset = WizardAsset::create([
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'type' => WizardAsset::TYPE_VOICEOVER,
            'name' => 'Multi-Speaker Dialogue - ' . date('Y-m-d H:i'),
            'path' => $combinedPath,
            'url' => $combinedUrl,
            'mime_type' => 'audio/mpeg',
            'scene_index' => $options['sceneIndex'] ?? null,
            'scene_id' => $options['sceneId'] ?? null,
            'metadata' => [
                'type' => 'multi_speaker_dialogue',
                'dialogueId' => $dialogueId,
                'turns' => $turnResults,
                'speakers' => $dialogue['speakers'],
                'narratorVoice' => $narratorVoice,
                'totalDuration' => $totalDuration,
                'turnCount' => count($turnResults),
                'provider' => $provider,
            ],
        ]);

        Log::info('VoiceoverService: Multi-speaker dialogue complete (VOC-10)', [
            'project_id' => $project->id,
            'assetId' => $asset->id,
            'turnCount' => count($turnResults),
            'totalDuration' => $totalDuration,
            'speakerCount' => count($dialogue['speakers']),
        ]);

        return [
            'success' => true,
            'combinedUrl' => $combinedUrl,
            'assetId' => $asset->id,
            'turns' => $turnResults,
            'totalDuration' => $totalDuration,
            'statistics' => [
                'turnCount' => count($turnResults),
                'speakerCount' => count($dialogue['speakers']),
                'speakers' => array_keys($dialogue['speakers']),
                'lipSyncTurns' => count(array_filter($turnResults, fn($t) => $t['needsLipSync'])),
                'voiceoverTurns' => count(array_filter($turnResults, fn($t) => !$t['needsLipSync'])),
                'emotionsApplied' => count(array_filter($turnResults, fn($t) => $t['emotionApplied'])),
            ],
        ];
    }

    /**
     * Generate multi-speaker dialogue for a scene.
     *
     * Convenience method that extracts segments from scene data
     * and generates unified dialogue audio.
     *
     * @param WizardProject $project The project
     * @param array $scene Scene data with speechSegments or narration
     * @param array $options Additional options
     * @return array Generation result
     */
    public function generateSceneDialogue(WizardProject $project, array $scene, array $options = []): array
    {
        // Extract segments from scene
        $segments = $scene['speechSegments'] ?? [];

        // If no segments, try to parse from narration
        if (empty($segments)) {
            $narration = $scene['narration'] ?? $scene['voiceover']['text'] ?? '';
            if (!empty($narration)) {
                $characterBible = $options['characterBible'] ?? [];
                $segments = $this->parseNarrationToSegments($narration, $characterBible);
            }
        }

        if (empty($segments)) {
            throw new \Exception('No dialogue segments found in scene');
        }

        // Add scene context to options
        $options['sceneId'] = $scene['id'] ?? null;
        $options['sceneIndex'] = $options['sceneIndex'] ?? null;

        return $this->generateMultiSpeakerDialogue($project, $segments, $options);
    }
}
