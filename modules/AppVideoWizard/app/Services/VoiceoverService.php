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

        if (empty($narration)) {
            throw new \Exception('No narration text provided');
        }

        // Determine which provider to use
        $provider = $forceProvider ?? $this->getProvider();

        Log::info('VoiceoverService: Generating voiceover', [
            'project_id' => $project->id,
            'scene_id' => $scene['id'] ?? 'unknown',
            'provider' => $provider,
            'voice' => $voice,
        ]);

        // Use Kokoro TTS if configured and selected
        if ($provider === 'kokoro' && $this->getKokoroService()->isConfigured()) {
            return $this->generateWithKokoro($project, $scene, $narration, $voice, $speed, $options);
        }

        // Fallback to OpenAI TTS
        return $this->generateWithOpenAI($project, $scene, $narration, $voice, $speed, $teamId, $options);
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
                'provider' => 'kokoro',
                'jobId' => $result['jobId'] ?? null,
            ],
        ]);

        return [
            'success' => true,
            'audioUrl' => $asset->url,
            'assetId' => $asset->id,
            'duration' => $estimatedDuration,
            'voice' => $kokoroVoice,
            'provider' => 'kokoro',
        ];
    }

    /**
     * Generate voiceover using OpenAI TTS.
     */
    protected function generateWithOpenAI(WizardProject $project, array $scene, string $narration, string $voice, float $speed, $teamId, array $options = []): array
    {
        // Generate audio using OpenAI TTS
        $result = AI::process($narration, 'speech', [
            'voice' => $voice,
        ], $teamId);

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

        // Get audio duration (approximate based on word count)
        $wordCount = str_word_count($narration);
        $estimatedDuration = ($wordCount / 150) * 60 / $speed; // 150 words per minute

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
                'provider' => 'openai',
            ],
        ]);

        return [
            'success' => true,
            'audioUrl' => $asset->url,
            'assetId' => $asset->id,
            'duration' => $estimatedDuration,
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

        // Narrator uses designated narrator voice
        if ($speakerUpper === 'NARRATOR') {
            if ($isKokoro) {
                return $this->getKokoroService()->mapOpenAIVoice($narratorVoice);
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
                    return $voiceId;
                }
                // Legacy string voice
                if (is_string($char['voice'] ?? null) && !empty($char['voice'])) {
                    $voiceId = $char['voice'];
                    if ($isKokoro && isset($this->openaiVoices[$voiceId])) {
                        return $this->getKokoroService()->mapOpenAIVoice($voiceId);
                    }
                    return $voiceId;
                }
                // Determine by gender
                $gender = strtolower($char['gender'] ?? $char['voice']['gender'] ?? '');
                if (str_contains($gender, 'female') || str_contains($gender, 'woman')) {
                    return $isKokoro ? 'af_nicole' : 'nova';
                } elseif (str_contains($gender, 'male') || str_contains($gender, 'man')) {
                    return $isKokoro ? 'am_michael' : 'onyx';
                }
            }
        }

        // Fallback: use speaker name hash to assign consistent voice
        $hash = crc32($speakerUpper);
        if ($isKokoro) {
            $characterVoices = ['am_michael', 'am_adam', 'af_nicole', 'af_bella', 'bm_lewis'];
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

                // Generate audio
                $audioResult = AI::process($segment->text, 'speech', [
                    'voice' => $voice,
                ], $teamId);

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
}
