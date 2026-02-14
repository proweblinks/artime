<?php

namespace Modules\AppVideoWizard\Services;

use App\Services\FalService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Qwen3TtsService
{
    protected FalService $falService;

    /**
     * Available Qwen 3 TTS built-in voices.
     */
    protected array $voices = [
        'Vivian'   => ['name' => 'Vivian', 'gender' => 'female', 'style' => 'friendly'],
        'Serena'   => ['name' => 'Serena', 'gender' => 'female', 'style' => 'bright'],
        'Ono_Anna' => ['name' => 'Ono Anna', 'gender' => 'female', 'style' => 'warm'],
        'Sohee'    => ['name' => 'Sohee', 'gender' => 'female', 'style' => 'soft'],
        'Dylan'    => ['name' => 'Dylan', 'gender' => 'male', 'style' => 'versatile'],
        'Eric'     => ['name' => 'Eric', 'gender' => 'male', 'style' => 'clear'],
        'Ryan'     => ['name' => 'Ryan', 'gender' => 'male', 'style' => 'warm'],
        'Aiden'    => ['name' => 'Aiden', 'gender' => 'male', 'style' => 'storytelling'],
        'Uncle_Fu' => ['name' => 'Uncle Fu', 'gender' => 'male', 'style' => 'deep'],
    ];

    /**
     * Map OpenAI voice IDs to Qwen3 built-in voices.
     */
    protected array $voiceMap = [
        'alloy'   => 'Dylan',     // Neutral, versatile
        'echo'    => 'Ryan',      // Male, warm
        'fable'   => 'Aiden',     // Storytelling
        'onyx'    => 'Uncle_Fu',  // Deep male
        'nova'    => 'Vivian',    // Female, friendly
        'shimmer' => 'Serena',    // Female, bright
    ];

    public function __construct()
    {
        $this->falService = app(FalService::class);
    }

    /**
     * Check if the service is configured (FAL API key exists).
     */
    public function isConfigured(): bool
    {
        return !empty(get_option('ai_fal_api_key', ''));
    }

    /**
     * Get available voices.
     */
    public function getAvailableVoices(): array
    {
        return $this->voices;
    }

    /**
     * Map an OpenAI voice ID to a Qwen3 built-in voice.
     */
    public function mapOpenAIVoice(string $openaiVoice): string
    {
        return $this->voiceMap[$openaiVoice] ?? 'Dylan';
    }

    /**
     * Map a voice ID — accepts OpenAI IDs, Kokoro IDs, or native Qwen3 names.
     */
    protected function mapVoice(string $voice): string
    {
        // Already a Qwen3 voice
        if (isset($this->voices[$voice])) {
            return $voice;
        }

        // OpenAI voice
        if (isset($this->voiceMap[$voice])) {
            return $this->voiceMap[$voice];
        }

        // Kokoro voice — map by gender pattern
        if (str_starts_with($voice, 'af_') || str_starts_with($voice, 'bf_')) {
            return 'Vivian'; // Female
        }
        if (str_starts_with($voice, 'am_') || str_starts_with($voice, 'bm_')) {
            return 'Dylan'; // Male
        }

        return 'Dylan';
    }

    /**
     * Generate speech using Qwen 3 TTS via FAL.AI.
     *
     * @param string $text Text to speak
     * @param string $voice Voice ID (OpenAI, Kokoro, or Qwen3 native)
     * @param int $projectId Project ID for file storage
     * @param array $options Additional options (emotion, mood, characterDescription, speechType, instructions)
     * @return array Result with audioUrl, audioPath, duration, voice, provider
     */
    public function generateSpeech(string $text, string $voice, int $projectId, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Qwen3 TTS is not configured. FAL API key is missing.',
            ];
        }

        if (empty($text)) {
            return [
                'success' => false,
                'error' => 'No text provided for speech generation',
            ];
        }

        // 1. Map voice ID to Qwen3 built-in voice
        $qwenVoice = $this->mapVoice($voice);

        // 2. Build style prompt from emotion/character context
        $stylePrompt = $this->buildStylePrompt($options);

        Log::info('Qwen3TTS: Starting speech generation', [
            'project_id' => $projectId,
            'original_voice' => $voice,
            'qwen_voice' => $qwenVoice,
            'text_length' => strlen($text),
            'has_style_prompt' => !empty($stylePrompt),
        ]);

        try {
            // 3. Call FalService->textToSpeech()
            $falOptions = [
                'voice' => $qwenVoice,
                'max_new_tokens' => $this->calculateMaxTokens($text),
            ];

            if (!empty($stylePrompt)) {
                $falOptions['prompt'] = $stylePrompt;
            }

            // Pass through cloned voice embedding if provided
            if (!empty($options['speaker_voice_embedding_file_url'])) {
                $falOptions['speaker_voice_embedding_file_url'] = $options['speaker_voice_embedding_file_url'];
                if (!empty($options['reference_text'])) {
                    $falOptions['reference_text'] = $options['reference_text'];
                }
                // Remove built-in voice when using embedding
                unset($falOptions['voice']);
            }

            if (isset($options['temperature'])) {
                $falOptions['temperature'] = $options['temperature'];
            }

            $result = $this->falService->textToSpeech($text, $falOptions);

            if (!empty($result['error'])) {
                throw new \Exception($result['error']);
            }

            // 4. Download audio from FAL CDN URL to local storage
            $audioData = $result['data'][0] ?? [];
            $audioUrl = $audioData['url'] ?? null;

            if (empty($audioUrl)) {
                throw new \Exception('No audio URL returned from Qwen3 TTS');
            }

            $localPath = $this->downloadAndStore($audioUrl, $projectId);

            // 5. Probe duration from the downloaded file
            $storagePath = Storage::disk('public')->path($localPath);
            $duration = $this->probeDuration($storagePath) ?? $audioData['duration'] ?? null;

            // Estimate if probing fails
            if (!$duration) {
                $wordCount = str_word_count($text);
                $duration = ($wordCount / 150) * 60; // 150 wpm
            }

            Log::info('Qwen3TTS: Speech generated successfully', [
                'project_id' => $projectId,
                'voice' => $qwenVoice,
                'duration' => $duration,
                'local_path' => $localPath,
            ]);

            return [
                'success' => true,
                'audioUrl' => Storage::disk('public')->url($localPath),
                'audioPath' => $localPath,
                'duration' => $duration,
                'voice' => $voice,
                'voiceConfig' => $this->voices[$qwenVoice] ?? null,
                'provider' => 'qwen3tts',
            ];

        } catch (\Exception $e) {
            Log::error('Qwen3TTS: Speech generation failed', [
                'error' => $e->getMessage(),
                'project_id' => $projectId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build a natural-language style prompt from character/emotion context.
     * This is where Qwen3 TTS shines — expressive voice direction.
     */
    protected function buildStylePrompt(array $options): string
    {
        $parts = [];

        $emotion = $options['emotion'] ?? $options['mood'] ?? null;
        $characterDesc = $options['characterDescription'] ?? null;
        $speechType = $options['speechType'] ?? 'monologue';
        $instructions = $options['instructions'] ?? '';

        // Use explicit instructions if provided (from VoicePromptBuilderService)
        if (!empty($instructions)) {
            $parts[] = $instructions;
        }

        // Character voice style from description
        if ($characterDesc) {
            if (preg_match('/\b(cat|kitten|feline|kitty)\b/i', $characterDesc)) {
                $parts[] = 'Speak with a slightly high-pitched, smug quality';
            } elseif (preg_match('/\b(dog|puppy|canine|pup)\b/i', $characterDesc)) {
                $parts[] = 'Speak with an eager, enthusiastic quality';
            } elseif (preg_match('/\b(robot|android|ai|cyborg)\b/i', $characterDesc)) {
                $parts[] = 'Speak with a slightly mechanical, precise diction';
            } elseif (preg_match('/\b(old|elderly|grandpa|grandma|ancient)\b/i', $characterDesc)) {
                $parts[] = 'Speak with a wise, weathered voice quality';
            } elseif (preg_match('/\b(child|kid|baby|toddler|young)\b/i', $characterDesc)) {
                $parts[] = 'Speak with a youthful, innocent voice quality';
            }
        }

        // Emotional direction — mapped to natural language
        if ($emotion) {
            $emotionMap = [
                'funny'     => 'comedic timing with deadpan delivery, slight pauses before punchlines',
                'absurd'    => 'exaggerated bewilderment, escalating disbelief',
                'wholesome' => 'warm, gentle sincerity with soft tone',
                'chaotic'   => 'fast-paced, unhinged energy, raising voice unpredictably',
                'cute'      => 'adorable, slightly squeaky with playful inflections',
                'sarcastic' => 'heavy sarcasm, eye-roll energy, flat affect with sharp emphasis',
                'dramatic'  => 'theatrical intensity, building tension with pauses',
                'angry'     => 'controlled anger, sharp consonants, clipped sentences',
                'sad'       => 'melancholic tone, slower pace, wavering quality',
                'excited'   => 'high energy, faster pace, rising intonation',
                'mysterious'=> 'hushed, conspiratorial tone with deliberate pacing',
                'confident' => 'strong, assured delivery with steady pacing',
            ];
            $parts[] = $emotionMap[$emotion] ?? "Speak with {$emotion} emotion";
        }

        // Dialogue-specific direction
        if ($speechType === 'dialogue') {
            $parts[] = 'conversational pace, natural reactions';
        }

        if (empty($parts)) {
            return '';
        }

        return implode('. ', $parts) . '.';
    }

    /**
     * Calculate max_new_tokens based on text length.
     * Roughly 1 token per character for English, with buffer.
     */
    protected function calculateMaxTokens(string $text): int
    {
        $charCount = mb_strlen($text);
        // Approximate: 4 tokens per word, average 5 chars per word → ~0.8 tokens per char
        // Add 50% buffer for pauses, breathing, etc.
        $estimated = (int) ceil($charCount * 0.8 * 1.5);
        return max(200, min(8192, $estimated));
    }

    /**
     * Download audio from FAL CDN and store in project audio directory.
     */
    protected function downloadAndStore(string $url, int $projectId): string
    {
        $contents = file_get_contents($url);
        if ($contents === false) {
            throw new \Exception("Failed to download audio from FAL CDN: {$url}");
        }

        $filename = 'qwen3_' . time() . '_' . Str::random(8) . '.mp3';
        $path = "wizard-projects/{$projectId}/audio/{$filename}";

        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    /**
     * Probe audio duration using ffprobe.
     */
    protected function probeDuration(string $filePath): ?float
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $ffprobePaths = ['/home/artime/bin/ffprobe', 'ffprobe'];
        foreach ($ffprobePaths as $ffprobe) {
            $cmd = sprintf(
                '%s -v quiet -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>/dev/null',
                escapeshellcmd($ffprobe),
                escapeshellarg($filePath)
            );

            $output = @shell_exec($cmd);
            if ($output !== null && is_numeric(trim($output))) {
                $duration = round((float) trim($output), 2);
                if ($duration > 0) {
                    return $duration;
                }
            }
        }

        return null;
    }

    /**
     * Get default voice by gender.
     */
    public function getDefaultVoiceByGender(string $gender): string
    {
        $gender = strtolower($gender);

        if (str_contains($gender, 'female') || str_contains($gender, 'woman')) {
            return 'Vivian';
        }
        if (str_contains($gender, 'male') || str_contains($gender, 'man')) {
            return 'Dylan';
        }

        return 'Vivian';
    }
}
