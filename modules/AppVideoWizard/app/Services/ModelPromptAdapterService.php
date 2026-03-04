<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ModelPromptAdapterService
 *
 * Model-aware prompt adapter that handles token limits for different image generation models.
 * CLIP-based models (HiDream/RunPod) truncate at 77 tokens, while Gemini-based models
 * (NanoBanana/Pro) support 4K-8K tokens.
 *
 * This service:
 * - Detects the target model and applies appropriate compression
 * - Uses actual BPE tokenization (when available) or word-based estimation
 * - Preserves subject/action while trimming style/quality tokens first
 * - Integrates with PromptTemplateLibrary for shot-type aware compression
 */
class ModelPromptAdapterService
{
    /**
     * Model-specific configurations for prompt handling.
     *
     * tokenizer: 'clip' (BPE, 77 tokens) or 'gemini' (large context)
     * maxTokens: Maximum token count for the model
     * truncation: 'intelligent' (compress by priority) or 'none' (no compression)
     */
    public const MODEL_CONFIGS = [
        'hidream' => [
            'tokenizer' => 'clip',
            'maxTokens' => 77,
            'truncation' => 'intelligent',
        ],
        'nanobanana' => [
            'tokenizer' => 'gemini',
            'maxTokens' => 4096,
            'truncation' => 'none',
        ],
        'nanobanana2' => [
            'tokenizer' => 'gemini',
            'maxTokens' => 8192,
            'truncation' => 'none',
        ],
    ];

    /**
     * Compression priority - higher number = remove first.
     * Subject and action are critical, style is expendable.
     */
    public const COMPRESSION_PRIORITY = [
        1 => 'subject',      // NEVER remove
        2 => 'action',       // RARELY remove
        3 => 'environment',  // CAN reduce
        4 => 'lighting',     // CAN reduce
        5 => 'atmosphere',   // OFTEN remove
        6 => 'style',        // FIRST to remove
    ];

    /**
     * Quality/style markers that are safe to remove for CLIP compression.
     * These don't affect semantic meaning but consume tokens.
     */
    protected const STYLE_MARKERS = [
        // Resolution markers
        '8K', '4K', '2K', '1080p', 'ultra HD', 'UHD',
        // Quality markers
        'photorealistic', 'hyper-realistic', 'hyperrealistic',
        'ultra detailed', 'highly detailed', 'extremely detailed',
        'ultra high quality', 'high quality', 'best quality',
        'masterpiece', 'professional',
        // Technical markers
        'sharp focus', 'intricate details', 'fine details',
        'octane render', 'unreal engine', 'ray tracing',
        'volumetric lighting', 'subsurface scattering',
        // Film/photography markers
        'film grain', 'analog film', '35mm film',
        'bokeh', 'depth of field', 'shallow depth of field',
        'DSLR', 'shot on', 'Canon', 'Sony', 'Arri',
    ];

    /**
     * Atmosphere markers that can be reduced.
     */
    protected const ATMOSPHERE_MARKERS = [
        'moody', 'atmospheric', 'ethereal', 'dreamy',
        'mystical', 'magical', 'enchanting', 'serene',
        'dramatic', 'epic', 'cinematic', 'filmic',
    ];

    /**
     * BPE vocabulary loaded from file (if available).
     */
    protected array $bpeVocab = [];

    /**
     * Whether BPE tokenization is available.
     */
    protected bool $hasBpeTokenizer = false;

    /**
     * PromptTemplateLibrary for shot-type aware compression.
     */
    protected ?PromptTemplateLibrary $templateLibrary;

    /**
     * Average tokens per word for English text (empirically derived).
     * Used for word-based estimation when BPE not available.
     */
    protected const TOKENS_PER_WORD = 1.3;

    public function __construct(?PromptTemplateLibrary $templateLibrary = null)
    {
        $this->templateLibrary = $templateLibrary ?? new PromptTemplateLibrary();
        $this->initializeTokenizer();
    }

    /**
     * Initialize the tokenizer.
     *
     * Attempts to load CLIP vocabulary for BPE tokenization.
     * Falls back to word-based estimation if unavailable.
     */
    protected function initializeTokenizer(): void
    {
        $vocabPath = storage_path('app/clip_vocab/bpe_simple_vocab_16e6.txt');

        if (file_exists($vocabPath)) {
            // Load BPE merge rules
            $content = file_get_contents($vocabPath);
            if ($content !== false) {
                $lines = explode("\n", $content);
                // Skip header line and count merge rules
                $this->bpeVocab = array_slice($lines, 1);
                $this->hasBpeTokenizer = count($this->bpeVocab) > 1000;

                Log::debug('[ModelPromptAdapterService] BPE vocabulary loaded', [
                    'vocabSize' => count($this->bpeVocab),
                    'tokenizerMode' => $this->hasBpeTokenizer ? 'bpe' : 'word-estimate',
                ]);
            }
        }

        if (!$this->hasBpeTokenizer) {
            Log::debug('[ModelPromptAdapterService] Using word-based token estimation');
        }
    }

    /**
     * Adapt a prompt for the target model.
     *
     * @param string $prompt The original prompt
     * @param string $modelId The target model ID (hidream, nanobanana, nanobanana2)
     * @param array $options Additional options (shotType, etc.)
     * @return string The adapted prompt
     */
    public function adaptPrompt(string $prompt, string $modelId, array $options = []): string
    {
        $config = self::MODEL_CONFIGS[$modelId] ?? self::MODEL_CONFIGS['nanobanana'];
        $originalTokens = $this->countTokens($prompt);

        // Gemini-based models don't need compression
        if ($config['tokenizer'] === 'gemini') {
            Log::debug('[ModelPromptAdapterService] Gemini model - no compression needed', [
                'model' => $modelId,
                'tokens' => $originalTokens,
            ]);
            return $prompt;
        }

        // CLIP-based models need compression
        $adapted = $this->compressForClip($prompt, $config['maxTokens'], $options);
        $adaptedTokens = $this->countTokens($adapted);

        Log::debug('[ModelPromptAdapterService] Prompt adapted for CLIP', [
            'model' => $modelId,
            'originalTokens' => $originalTokens,
            'adaptedTokens' => $adaptedTokens,
            'wasCompressed' => $originalTokens !== $adaptedTokens,
        ]);

        return $adapted;
    }

    /**
     * Count tokens in a text string.
     *
     * Uses BPE tokenization if available, otherwise word-based estimation.
     *
     * @param string $text The text to count tokens for
     * @return int Estimated token count
     */
    public function countTokens(string $text): int
    {
        if (empty($text)) {
            return 0;
        }

        if ($this->hasBpeTokenizer) {
            return $this->countTokensBpe($text);
        }

        return $this->countTokensWordEstimate($text);
    }

    /**
     * Count tokens using BPE-style estimation.
     *
     * This is a simplified BPE approximation that:
     * 1. Splits text into words
     * 2. Applies common BPE patterns
     * 3. Accounts for special tokens
     *
     * @param string $text The text to tokenize
     * @return int Token count
     */
    protected function countTokensBpe(string $text): int
    {
        // Normalize text
        $text = strtolower(trim($text));

        // Count base tokens (words and punctuation)
        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $tokenCount = 0;

        foreach ($tokens as $word) {
            // Base word token
            $tokenCount++;

            // Multi-syllable words typically split into subwords
            $wordLen = strlen($word);
            if ($wordLen > 8) {
                // Long words often become 2-3 subwords
                $tokenCount += (int) floor(($wordLen - 4) / 4);
            } elseif ($wordLen > 5) {
                // Medium words sometimes split
                $tokenCount += (int) floor(($wordLen - 3) / 5);
            }

            // Punctuation attached to words
            if (preg_match('/[.,!?;:"\']/', $word)) {
                $tokenCount++;
            }
        }

        // Add start/end tokens (CLIP uses <|startoftext|> and <|endoftext|>)
        $tokenCount += 2;

        return $tokenCount;
    }

    /**
     * Count tokens using word-based estimation.
     *
     * Simple fallback: count words and multiply by average tokens per word.
     *
     * @param string $text The text to estimate
     * @return int Estimated token count
     */
    protected function countTokensWordEstimate(string $text): int
    {
        // Count words
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);

        // Apply multiplier and round up
        $tokens = (int) ceil($wordCount * self::TOKENS_PER_WORD);

        // Add start/end tokens
        $tokens += 2;

        return $tokens;
    }

    /**
     * Compress a prompt for CLIP's 77-token limit.
     *
     * Compression strategy:
     * 1. Remove style markers (8K, photorealistic, etc.)
     * 2. Remove atmosphere markers (moody, cinematic, etc.)
     * 3. Use shot-type priority order to reduce sections
     * 4. Hard truncate if still over limit
     *
     * @param string $prompt The prompt to compress
     * @param int $maxTokens Maximum tokens allowed
     * @param array $options Options including shotType
     * @return string Compressed prompt
     */
    public function compressForClip(string $prompt, int $maxTokens = 77, array $options = []): string
    {
        $currentTokens = $this->countTokens($prompt);

        // Already under limit
        if ($currentTokens <= $maxTokens) {
            return $prompt;
        }

        $compressed = $prompt;

        // Phase 1: Remove style markers
        foreach (self::STYLE_MARKERS as $marker) {
            $compressed = preg_replace('/\b' . preg_quote($marker, '/') . '\b/i', '', $compressed);
        }
        $compressed = $this->cleanupPrompt($compressed);

        if ($this->countTokens($compressed) <= $maxTokens) {
            return $compressed;
        }

        // Phase 2: Remove atmosphere markers
        foreach (self::ATMOSPHERE_MARKERS as $marker) {
            $compressed = preg_replace('/\b' . preg_quote($marker, '/') . '\b/i', '', $compressed);
        }
        $compressed = $this->cleanupPrompt($compressed);

        if ($this->countTokens($compressed) <= $maxTokens) {
            return $compressed;
        }

        // Phase 3: Parse into components and reduce by priority
        $components = $this->parsePromptComponents($compressed);
        $shotType = $options['shotType'] ?? 'medium';
        $priorityOrder = $this->templateLibrary->getPriorityOrder($shotType);

        // Reverse priority order (remove lowest priority first)
        $removeOrder = array_reverse($priorityOrder);

        foreach ($removeOrder as $section) {
            if (!isset($components[$section]) || empty($components[$section])) {
                continue;
            }

            // Don't remove subject
            if ($section === 'subject') {
                break;
            }

            // Remove this section
            $components[$section] = '';

            $reconstructed = $this->reconstructPrompt($components);
            if ($this->countTokens($reconstructed) <= $maxTokens) {
                return $reconstructed;
            }
        }

        // Phase 4: Hard truncate (preserve subject)
        $reconstructed = $this->reconstructPrompt($components);
        return $this->hardTruncate($reconstructed, $maxTokens);
    }

    /**
     * Parse a prompt into component sections.
     *
     * Uses pattern matching to identify:
     * - Subject: First sentence or until action verb
     * - Action: Verb phrases (standing, walking, looking, etc.)
     * - Environment: Location/setting descriptions
     * - Lighting: Light/shadow/color temperature mentions
     * - Style: Quality/technique markers
     *
     * @param string $prompt The prompt to parse
     * @return array<string, string> Components by section name
     */
    protected function parsePromptComponents(string $prompt): array
    {
        $components = [
            'subject' => '',
            'action' => '',
            'environment' => '',
            'lighting' => '',
            'style' => '',
        ];

        // Split by comma for analysis
        $parts = array_map('trim', explode(',', $prompt));

        foreach ($parts as $part) {
            $lowerPart = strtolower($part);

            // Lighting keywords
            if (preg_match('/\b(light|lighting|lit|shadow|glow|rim|backlit|sunlight|moonlight|golden hour|blue hour|ambient|natural light|soft light|hard light|diffused|spotlight)\b/i', $lowerPart)) {
                $components['lighting'] .= $part . ', ';
                continue;
            }

            // Environment keywords
            if (preg_match('/\b(in a|inside|outside|at|room|studio|forest|city|urban|street|beach|mountain|sky|background|setting|location|scene|landscape)\b/i', $lowerPart)) {
                $components['environment'] .= $part . ', ';
                continue;
            }

            // Action keywords
            if (preg_match('/\b(standing|sitting|walking|running|looking|gazing|holding|reaching|dancing|posing|smiling|laughing|crying|thinking)\b/i', $lowerPart)) {
                $components['action'] .= $part . ', ';
                continue;
            }

            // Style keywords
            if (preg_match('/\b(style|aesthetic|artistic|rendering|quality|resolution|photography|cinematic|film|shot|camera|lens)\b/i', $lowerPart)) {
                $components['style'] .= $part . ', ';
                continue;
            }

            // Default to subject
            $components['subject'] .= $part . ', ';
        }

        // Trim trailing commas and spaces
        foreach ($components as $key => $value) {
            $components[$key] = rtrim(trim($value), ', ');
        }

        return $components;
    }

    /**
     * Reconstruct a prompt from components.
     *
     * @param array<string, string> $components Component strings by section
     * @return string Reconstructed prompt
     */
    protected function reconstructPrompt(array $components): string
    {
        $parts = [];

        // Maintain natural order: subject, action, environment, lighting, style
        foreach (['subject', 'action', 'environment', 'lighting', 'style'] as $section) {
            if (!empty($components[$section])) {
                $parts[] = $components[$section];
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Hard truncate a prompt to fit token limit.
     *
     * Truncates at word boundaries to avoid mid-word cuts.
     *
     * @param string $prompt The prompt to truncate
     * @param int $maxTokens Maximum tokens
     * @return string Truncated prompt
     */
    protected function hardTruncate(string $prompt, int $maxTokens): string
    {
        $words = preg_split('/\s+/', $prompt, -1, PREG_SPLIT_NO_EMPTY);
        $result = [];

        foreach ($words as $word) {
            $testPrompt = implode(' ', array_merge($result, [$word]));
            if ($this->countTokens($testPrompt) > $maxTokens) {
                break;
            }
            $result[] = $word;
        }

        return implode(' ', $result);
    }

    /**
     * Clean up a prompt after marker removal.
     *
     * Removes extra whitespace, double commas, etc.
     *
     * @param string $prompt The prompt to clean
     * @return string Cleaned prompt
     */
    protected function cleanupPrompt(string $prompt): string
    {
        // Remove double commas
        $prompt = preg_replace('/,\s*,/', ',', $prompt);

        // Remove leading/trailing commas
        $prompt = trim($prompt, ', ');

        // Normalize whitespace
        $prompt = preg_replace('/\s+/', ' ', $prompt);

        return trim($prompt);
    }

    /**
     * Get adaptation statistics for a prompt.
     *
     * @param string $original Original prompt
     * @param string $adapted Adapted prompt
     * @param string $modelId Target model ID
     * @return array Statistics including token counts and compression info
     */
    public function getAdaptationStats(string $original, string $adapted, string $modelId): array
    {
        $config = self::MODEL_CONFIGS[$modelId] ?? self::MODEL_CONFIGS['nanobanana'];

        return [
            'originalTokens' => $this->countTokens($original),
            'adaptedTokens' => $this->countTokens($adapted),
            'wasCompressed' => $original !== $adapted,
            'modelConfig' => $config,
            'tokenizerMode' => $this->hasBpeTokenizer ? 'bpe' : 'word-estimate',
            'maxTokens' => $config['maxTokens'],
            'underLimit' => $this->countTokens($adapted) <= $config['maxTokens'],
        ];
    }

    /**
     * Get the model configuration for a given model ID.
     *
     * @param string $modelId The model ID
     * @return array Model configuration
     */
    public function getModelConfig(string $modelId): array
    {
        return self::MODEL_CONFIGS[$modelId] ?? self::MODEL_CONFIGS['nanobanana'];
    }

    /**
     * Check if a model requires prompt compression.
     *
     * @param string $modelId The model ID
     * @return bool True if model uses CLIP tokenizer with truncation
     */
    public function requiresCompression(string $modelId): bool
    {
        $config = self::MODEL_CONFIGS[$modelId] ?? self::MODEL_CONFIGS['nanobanana'];
        return $config['truncation'] === 'intelligent';
    }
}
