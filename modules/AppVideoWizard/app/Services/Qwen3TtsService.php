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
                'audioUrl' => url('/files/' . $localPath),
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
     * Build a rich, multi-layer natural-language style prompt from character/emotion context.
     * Qwen3 TTS's killer feature is the `prompt` parameter — we generate cinematic voice direction.
     */
    protected function buildStylePrompt(array $options): string
    {
        $parts = [];
        $emotion = $options['emotion'] ?? $options['mood'] ?? null;
        $characterDesc = $options['characterDescription'] ?? null;
        $characterName = $options['characterName'] ?? null;
        $speechType = $options['speechType'] ?? 'monologue';
        $instructions = $options['instructions'] ?? '';
        $dialogueText = $options['dialogueText'] ?? '';

        // Layer 1: Explicit instructions from VoicePromptBuilderService
        if (!empty($instructions)) {
            $parts[] = $instructions;
        }

        // Layer 2: Character identity and vocal quality from description
        if ($characterDesc) {
            $parts[] = $this->buildCharacterVocalIdentity($characterDesc, $characterName);
        }

        // Layer 3: Emotional delivery with intensity
        if ($emotion) {
            $parts[] = $this->buildEmotionalDelivery($emotion, $speechType);
        }

        // Layer 4: Dialogue-aware pacing
        if ($speechType === 'dialogue') {
            $parts[] = $this->buildDialoguePacing($dialogueText, $emotion);
        }

        if (empty($parts)) {
            return 'Natural, conversational delivery with clear articulation.';
        }

        return implode('. ', array_filter($parts)) . '.';
    }

    /**
     * Extract species, age, personality traits from character description
     * and generate a unique vocal identity prompt.
     */
    protected function buildCharacterVocalIdentity(string $desc, ?string $name): string
    {
        $descLower = strtolower($desc);
        $parts = [];

        // Species-specific vocal qualities
        if (preg_match('/\b(cat|kitten|feline|kitty)\b/', $descLower)) {
            $parts[] = 'smug, slightly condescending delivery with a self-satisfied purring quality';
            if (preg_match('/\b(grumpy|annoyed|angry)\b/', $descLower)) {
                $parts[] = 'irritated hissing undertone, sharp clipped words';
            }
        } elseif (preg_match('/\b(dog|puppy|canine|pup)\b/', $descLower)) {
            $parts[] = 'eager, panting enthusiasm, bursts of excited energy';
        } elseif (preg_match('/\b(robot|android|ai|cyborg)\b/', $descLower)) {
            $parts[] = 'precise mechanical diction, slight digital resonance';
        } elseif (preg_match('/\b(bird|parrot|crow|raven)\b/', $descLower)) {
            $parts[] = 'sharp staccato delivery, clipped chirpy cadence';
        } elseif (preg_match('/\b(snake|serpent|lizard|reptile)\b/', $descLower)) {
            $parts[] = 'drawn-out sibilant hiss on S sounds, slithering smooth delivery';
        }

        // Personality traits
        if (preg_match('/\b(grumpy|grouchy|cranky)\b/', $descLower)) {
            $parts[] = 'perpetually annoyed, sighing between sentences';
        } elseif (preg_match('/\b(cheerful|happy|bubbly)\b/', $descLower)) {
            $parts[] = 'bright, upbeat energy lifting every word';
        } elseif (preg_match('/\b(nervous|anxious|worried)\b/', $descLower)) {
            $parts[] = 'slightly trembling, hesitant, swallowing nervously';
        } elseif (preg_match('/\b(shy|timid|quiet)\b/', $descLower)) {
            $parts[] = 'soft-spoken, trailing off at sentence ends, reluctant volume';
        } elseif (preg_match('/\b(arrogant|smug|proud)\b/', $descLower)) {
            $parts[] = 'self-important, elongating vowels with superiority';
        }

        // Age/experience traits
        if (preg_match('/\b(old|elderly|grandpa|grandma|ancient|wise)\b/', $descLower)) {
            $parts[] = 'weathered, gravelly wisdom, slow deliberate pacing';
        } elseif (preg_match('/\b(child|kid|young|little|baby|toddler)\b/', $descLower)) {
            $parts[] = 'high-pitched, innocent wonder, breathless excitement';
        }

        // Profession traits
        if (preg_match('/\b(chef|cook)\b/', $descLower)) {
            $parts[] = 'passionate about food, Italian-chef-like intensity when discussing cooking';
        } elseif (preg_match('/\b(detective|investigator|spy)\b/', $descLower)) {
            $parts[] = 'noir-style measured delivery, suspicious of everything';
        } elseif (preg_match('/\b(narrator)\b/', $descLower)) {
            $parts[] = 'rich authoritative storytelling voice, painting pictures with words';
        }

        return implode(', ', $parts) ?: "natural, expressive delivery for {$name}";
    }

    /**
     * Generate intense, layered emotional delivery direction.
     */
    protected function buildEmotionalDelivery(string $emotion, string $speechType): string
    {
        $emotionMap = [
            'funny'     => 'masterful comedic timing — deadpan delivery with micro-pauses before punchlines, slight smirk in the voice, let the absurdity land',
            'absurd'    => 'escalating bewilderment — start measured then spiral into incredulous disbelief, voice cracking with "are you serious?" energy',
            'wholesome' => 'genuine warmth radiating through every word, gentle smile in the voice, tender pauses that let the sweetness breathe',
            'chaotic'   => 'unhinged manic energy — volume swings wildly, words tumble over each other, barely controlled chaos',
            'cute'      => 'adorably squeaky with playful pitch jumps, infectious giggling energy',
            'sarcastic' => 'weaponized sarcasm — flat affect with devastating emphasis on key words, eye-roll energy dripping from every syllable',
            'dramatic'  => 'theatrical intensity building to a crescendo — pregnant pauses, voice dropping to a whisper then exploding with emotion',
            'angry'     => 'white-hot controlled fury — jaw tight, words bitten off sharp, volume rising dangerously',
            'sad'       => 'aching melancholy — voice heavy with unshed tears, words trailing off into silence',
            'excited'   => 'barely contained explosive joy — voice climbing higher, words accelerating, infectious enthusiasm',
            'mysterious'=> 'conspiratorial whisper — deliberate pacing, each word placed like a chess piece, heavy with meaning',
            'confident' => 'commanding authority — chest-voice resonance, measured powerful pacing, every word lands like a verdict',
            'tense'     => 'coiled spring about to snap — tight throat, clipped breathing, words coming through gritted teeth',
            'romantic'  => 'intimate velvet warmth — soft breathy quality, lingering on vowels, pillow-talk intimacy',
            'dark'      => 'ominous gravitas — low rumbling undertone, words dripping with foreboding',
            'hopeful'   => 'quiet rising optimism — voice brightening with each phrase, dawn breaking through the tone',
        ];

        return $emotionMap[$emotion] ?? "intense {$emotion} emotion — fully committed, emotionally raw delivery";
    }

    /**
     * Build dialogue-specific pacing direction based on the actual text content.
     */
    protected function buildDialoguePacing(string $dialogueText, ?string $emotion): string
    {
        $parts = ['conversational rhythm with natural turn-taking energy'];

        // Detect question marks → rising intonation
        if (str_contains($dialogueText, '?')) {
            $parts[] = 'rising intonation on questions, genuine curiosity';
        }

        // Detect exclamation → emphatic delivery
        if (str_contains($dialogueText, '!')) {
            $parts[] = 'emphatic bursts of energy on exclamations';
        }

        // Detect ellipsis → trailing off
        if (str_contains($dialogueText, '...') || str_contains($dialogueText, '…')) {
            $parts[] = 'trailing off thoughtfully at ellipses, pregnant pauses';
        }

        // Short dialogue → punchy
        if (strlen($dialogueText) < 50) {
            $parts[] = 'punchy and direct, every word counts';
        }

        return implode(', ', $parts);
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

        if (str_contains($gender, 'female') || str_contains($gender, 'woman') || str_contains($gender, 'girl')) {
            return 'Vivian';
        }
        if (str_contains($gender, 'male') || str_contains($gender, 'man') || str_contains($gender, 'boy')) {
            return 'Dylan';
        }

        // Default to male voice (most narration skews male)
        return 'Dylan';
    }
}
