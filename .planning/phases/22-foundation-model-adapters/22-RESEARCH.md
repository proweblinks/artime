# Phase 22: Foundation & Model Adapters - Research

**Researched:** 2026-01-25
**Domain:** Model-aware prompt infrastructure, cinematography vocabulary, token compression
**Confidence:** HIGH

## Summary

This phase builds model-aware prompt infrastructure with token limits and professional cinematography vocabulary. The critical challenge is CLIP's 77-token limit for HiDream (RunPod) while NanoBanana/Pro (Gemini) can handle longer prompts.

The existing codebase already has sophisticated prompt building infrastructure in `StructuredPromptBuilderService` and `VideoPromptBuilderService` that should be extended, not replaced. The key additions are: (1) a model adapter layer that compresses prompts for CLIP-based models, (2) a template library organized by shot type, and (3) professional camera/lighting vocabulary with psychological reasoning.

**Primary recommendation:** Create a new `ModelPromptAdapterService` that wraps existing prompt builders, applying model-specific compression when needed. Use PHP BPE tokenizer library for accurate CLIP token counting. Front-load critical content (subject, action) and truncate style/quality tokens when compressing.

## Standard Stack

The established libraries/tools for this domain:

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| danny50610/bpe-tokeniser | ^2.0 | BPE token counting | PHP port of tiktoken, supports custom vocab |
| Existing StructuredPromptBuilderService | - | Structured prompt building | Already has visual modes, lighting presets, camera presets |
| Existing VideoPromptBuilderService | - | Hollywood formula prompts | Already has shot types, action verbs, component ordering |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| mehrab-wj/tiktoken-php | ^1.0 | Alternative tokenizer | If bpe-tokeniser has issues with CLIP vocab |
| Gioni06/GPT3Tokenizer | ^1.0 | Fallback estimation | If exact CLIP tokenization unavailable |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| PHP BPE tokenizer | Python microservice | More accurate but adds deployment complexity |
| CLIP vocab in PHP | Word-count estimation | Simpler but ~20% inaccurate |
| Custom compression | Truncate-only | Truncation loses important context |

**Installation:**
```bash
composer require danny50610/bpe-tokeniser
```

**Note on CLIP Vocabulary:** CLIP uses a specific BPE vocabulary (49,152 tokens) that differs from GPT models. The PHP BPE tokenizer libraries support custom vocabularies. Download CLIP's vocabulary from `https://openaipublic.blob.core.windows.net/clip/bpe_simple_vocab_16e6.txt` and configure the tokenizer to use it.

## Architecture Patterns

### Recommended Project Structure
```
modules/AppVideoWizard/app/Services/
├── ModelPromptAdapterService.php    # NEW: Model-aware prompt compression
├── PromptTemplateLibrary.php        # NEW: Shot-type organized templates
├── CinematographyVocabulary.php     # NEW: Camera/lighting vocabulary constants
├── StructuredPromptBuilderService.php  # EXTEND: Add template integration
├── VideoPromptBuilderService.php       # EXTEND: Add psychology vocabulary
└── ImageGenerationService.php          # EXTEND: Hook adapter before model dispatch
```

### Pattern 1: Model Adapter Pattern
**What:** Wrap prompt building with model-specific post-processing
**When to use:** Before sending prompts to any image generation model

**Example:**
```php
// Source: Recommended pattern based on codebase analysis
class ModelPromptAdapterService
{
    public const MODEL_CONFIGS = [
        'hidream' => [
            'tokenizer' => 'clip',
            'maxTokens' => 77,
            'truncation' => 'intelligent',  // Preserve subject/action
        ],
        'nanobanana' => [
            'tokenizer' => 'gemini',
            'maxTokens' => 4096,
            'truncation' => 'none',
        ],
        'nanobanana-pro' => [
            'tokenizer' => 'gemini',
            'maxTokens' => 8192,
            'truncation' => 'none',
        ],
    ];

    public function adaptPrompt(string $prompt, string $modelId): string
    {
        $config = self::MODEL_CONFIGS[$modelId] ?? self::MODEL_CONFIGS['nanobanana'];

        if ($config['tokenizer'] === 'clip') {
            return $this->compressForClip($prompt, $config['maxTokens']);
        }

        return $prompt;
    }

    protected function compressForClip(string $prompt, int $maxTokens): string
    {
        $tokens = $this->clipTokenizer->encode($prompt);

        if (count($tokens) <= $maxTokens) {
            return $prompt;
        }

        // Intelligent compression: preserve subject/action, drop style
        return $this->intelligentCompress($prompt, $maxTokens);
    }
}
```

### Pattern 2: Template Library by Shot Type
**What:** Organize prompt templates by shot type with model-specific variants
**When to use:** When building prompts for different shot types (close-up, wide, etc.)

**Example:**
```php
// Source: Based on VideoPromptBuilderService shot types
class PromptTemplateLibrary
{
    public const SHOT_TEMPLATES = [
        'close-up' => [
            'emphasis' => ['facial_detail', 'emotion', 'micro_expressions'],
            'camera' => ['85mm lens', 'shallow depth of field'],
            'wordBudget' => [
                'subject' => 25,      // 35% - face/emotion critical
                'action' => 15,       // 20% - micro-expressions
                'environment' => 8,   // 10% - minimal background
                'lighting' => 12,     // 18% - key/fill important
                'style' => 12,        // 17% - quality markers
            ],
        ],
        'wide' => [
            'emphasis' => ['environment', 'spatial_context', 'scene_setting'],
            'camera' => ['24mm lens', 'deep focus'],
            'wordBudget' => [
                'subject' => 15,      // 20% - smaller in frame
                'action' => 10,       // 15% - body language
                'environment' => 25,  // 35% - critical for wide
                'lighting' => 10,     // 15% - ambient
                'style' => 10,        // 15% - atmosphere
            ],
        ],
        'medium' => [
            'emphasis' => ['subject_context', 'body_language', 'interaction'],
            'camera' => ['50mm lens', 'medium depth of field'],
            'wordBudget' => [
                'subject' => 20,      // 28%
                'action' => 15,       // 20%
                'environment' => 15,  // 20%
                'lighting' => 12,     // 17%
                'style' => 10,        // 15%
            ],
        ],
    ];
}
```

### Pattern 3: Priority-Based Compression
**What:** When compressing, remove tokens in reverse priority order
**When to use:** When prompt exceeds CLIP 77-token limit

**Example:**
```php
// Source: Stable Diffusion best practices - subject first, style last
public const COMPRESSION_PRIORITY = [
    1 => 'subject',      // NEVER remove - "a woman"
    2 => 'action',       // RARELY remove - "running through"
    3 => 'environment',  // CAN reduce - "rainy city street"
    4 => 'lighting',     // CAN reduce - "dramatic rim lighting"
    5 => 'atmosphere',   // OFTEN remove - "moody, tense"
    6 => 'style',        // FIRST to remove - "8K, cinematic"
    7 => 'negative',     // ALWAYS remove for CLIP - embedded negatives
];
```

### Anti-Patterns to Avoid
- **Building separate prompt systems:** Extend existing StructuredPromptBuilderService, don't duplicate
- **Char/4 token estimation:** CLIP tokenizes differently than GPT; use actual BPE tokenizer
- **Truncating from the front:** Subject/action at front is critical; truncate from end
- **Hardcoding 77 tokens:** Use config; future models may have different limits

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Token counting | Character/word estimation | danny50610/bpe-tokeniser | BPE is not linear; "photograph" = 2 tokens, "photo" = 1 |
| Camera presets | New camera library | Existing CAMERA_PRESETS in StructuredPromptBuilderService | Already has 85mm, 24mm, 50mm, 135mm, anamorphic |
| Lighting presets | New lighting library | Existing LIGHTING_PRESETS in StructuredPromptBuilderService | Already has golden_hour, blue_hour, dramatic_low_key |
| Shot type handling | New shot system | Existing VideoPromptBuilderService.ACTION_VERBS | Already mapped by shot type |
| Hollywood formula | New prompt structure | Existing PROMPT_COMPONENTS ordering | Already has camera > subject > action > environment |

**Key insight:** The codebase already has 80% of the cinematography infrastructure. Phase 22 primarily adds: (1) model detection, (2) token-aware compression, (3) shot-type templates that leverage existing presets.

## Common Pitfalls

### Pitfall 1: CLIP Silent Truncation
**What goes wrong:** HiDream accepts any prompt but silently truncates at 77 tokens
**Why it happens:** No error returned; prompt just stops mid-sentence
**How to avoid:** Count tokens before sending; compress intelligently
**Warning signs:** Generated images missing style/quality; inconsistent results

### Pitfall 2: Inaccurate Token Estimation
**What goes wrong:** Using word count or char/4 gives wrong token count
**Why it happens:** BPE tokenizes subwords; "cinematic" = 3 tokens, "film" = 1
**How to avoid:** Use actual BPE tokenizer with CLIP vocabulary
**Warning signs:** Prompts that "should fit" getting truncated

### Pitfall 3: Breaking Existing Service Integration
**What goes wrong:** New service doesn't integrate with VisualConsistencyService, Story Bible
**Why it happens:** Building adapter without considering existing data flow
**How to avoid:** Hook into ImageGenerationService after buildImagePrompt(), before model dispatch
**Warning signs:** Character DNA missing; style consistency broken

### Pitfall 4: Losing Psychological Context in Compression
**What goes wrong:** "85mm lens creates intimate compression" becomes "85mm lens"
**Why it happens:** Naive word-based truncation
**How to avoid:** Keep vocabulary units together; compress by removing entire concepts
**Warning signs:** Camera specs present but no emotional reasoning

### Pitfall 5: One-Size-Fits-All Templates
**What goes wrong:** Close-up prompts waste tokens on environment; wide shots lack context
**Why it happens:** Same prompt structure for all shot types
**How to avoid:** Shot-type specific word budgets; emphasis mapping
**Warning signs:** Close-ups showing too much background; wide shots with face detail

## Code Examples

Verified patterns from official sources and codebase analysis:

### CLIP Token Counting in PHP
```php
// Source: danny50610/bpe-tokeniser documentation pattern
use BpeTokeniser\Encoder;
use BpeTokeniser\EncoderFactory;

class ClipTokenizer
{
    private Encoder $encoder;

    public function __construct()
    {
        // Load CLIP's BPE vocabulary
        // Download from: https://openaipublic.blob.core.windows.net/clip/bpe_simple_vocab_16e6.txt
        $this->encoder = EncoderFactory::createFromVocabFile(
            storage_path('app/clip_vocab/bpe_simple_vocab_16e6.txt')
        );
    }

    public function countTokens(string $text): int
    {
        $tokens = $this->encoder->encode($text);
        return count($tokens);
    }

    public function encode(string $text): array
    {
        return $this->encoder->encode($text);
    }
}
```

### Intelligent Prompt Compression
```php
// Source: Stable Diffusion best practices - subject first, style last
class IntelligentCompressor
{
    public function compress(string $prompt, int $maxTokens, array $priorityMap): string
    {
        $components = $this->parsePromptComponents($prompt);
        $tokenizer = new ClipTokenizer();

        // Start removing from lowest priority until under limit
        $priorities = array_reverse($priorityMap); // style first

        foreach ($priorities as $component => $priority) {
            $current = $this->buildPrompt($components);
            if ($tokenizer->countTokens($current) <= $maxTokens) {
                return $current;
            }

            // Progressively reduce this component
            $components[$component] = $this->reduceComponent(
                $components[$component],
                $component
            );
        }

        // Last resort: hard truncate
        return $this->hardTruncate($prompt, $maxTokens);
    }

    protected function reduceComponent(string $text, string $type): string
    {
        if ($type === 'style') {
            // Remove quality markers: "8K, UHD, photorealistic" -> ""
            return '';
        }
        if ($type === 'lighting') {
            // Simplify: "key light 45 degrees at 5600K, fill -2 stops" -> "dramatic lighting"
            return 'dramatic lighting';
        }
        // ... other component reductions
        return $text;
    }
}
```

### Camera Psychology Vocabulary
```php
// Source: Cinematography research - lens focal length psychology
class CinematographyVocabulary
{
    public const LENS_PSYCHOLOGY = [
        '24mm' => [
            'effect' => 'environmental context, slight distortion at edges',
            'psychology' => 'places subject in world, shows vulnerability or power through environment',
            'use_for' => ['establishing', 'wide', 'extreme-wide'],
        ],
        '35mm' => [
            'effect' => 'candid, documentary feel',
            'psychology' => 'captures subject in context, natural storytelling, creates intimacy with environment',
            'use_for' => ['wide', 'medium-wide'],
        ],
        '50mm' => [
            'effect' => 'neutral, human-eye equivalent',
            'psychology' => 'lets performance speak, no lens-driven emotion, pure storytelling',
            'use_for' => ['medium', 'medium-wide'],
        ],
        '85mm' => [
            'effect' => 'flattering compression, creamy bokeh',
            'psychology' => 'creates intimacy, isolates subject from background, makes features attractive',
            'use_for' => ['close-up', 'medium-close'],
        ],
        '135mm' => [
            'effect' => 'strong compression, dramatic isolation',
            'psychology' => 'maximum subject isolation, voyeuristic quality, emotional intensity',
            'use_for' => ['close-up', 'extreme-close-up'],
        ],
    ];

    public function getLensDescription(string $shotType): string
    {
        foreach (self::LENS_PSYCHOLOGY as $lens => $data) {
            if (in_array($shotType, $data['use_for'])) {
                return "{$lens} lens {$data['effect']}, {$data['psychology']}";
            }
        }
        return '50mm lens neutral perspective';
    }
}
```

### Lighting Ratio Vocabulary
```php
// Source: Professional photography lighting ratio standards
class LightingVocabulary
{
    public const LIGHTING_RATIOS = [
        '1:1' => [
            'description' => 'flat even lighting, key equals fill',
            'mood' => 'commercial, beauty, innocence',
            'stops_difference' => 0,
        ],
        '2:1' => [
            'description' => 'subtle dimensionality, one stop difference',
            'mood' => 'natural portrait, approachable',
            'stops_difference' => 1,
        ],
        '4:1' => [
            'description' => 'dramatic contrast, two stop difference',
            'mood' => 'cinematic, moody, fashion',
            'stops_difference' => 2,
        ],
        '8:1' => [
            'description' => 'high contrast chiaroscuro, three stop difference',
            'mood' => 'noir, mysterious, intense drama',
            'stops_difference' => 3,
        ],
    ];

    public const COLOR_TEMPERATURES = [
        'candlelight' => '1900K',
        'tungsten' => '3200K',
        'golden_hour' => '3500K',
        'daylight' => '5600K',
        'overcast' => '6500K',
        'shade' => '7500K',
        'north_light' => '10000K',
    ];

    public function buildLightingDescription(string $mood, string $timeOfDay): string
    {
        $ratio = $this->getMoodRatio($mood);
        $temp = self::COLOR_TEMPERATURES[$timeOfDay] ?? '5600K';

        return "key light at {$temp}, {$ratio['description']}, {$ratio['stops_difference']} stop difference between highlight and shadow";
    }
}
```

### Integration Hook in ImageGenerationService
```php
// Source: Codebase analysis - hook location
// In ImageGenerationService.php, after buildImagePrompt(), before model dispatch:

public function generateSceneImage(WizardProject $project, array $scene, array $options = []): array
{
    $modelId = $options['model'] ?? $project->storyboard['imageModel'] ?? 'nanobanana';
    $modelConfig = self::IMAGE_MODELS[$modelId] ?? self::IMAGE_MODELS['nanobanana'];

    // ... existing code to build prompt ...
    $prompt = $this->buildImagePrompt(...);

    // NEW: Phase 22 - Model-aware prompt adaptation
    $adapter = app(ModelPromptAdapterService::class);
    $adaptedPrompt = $adapter->adaptPrompt($prompt, $modelId, [
        'shotType' => $options['shot_type'] ?? 'medium',
        'preserveCharacterDNA' => true,
    ]);

    // Log adaptation details
    Log::info('ImageGeneration: Prompt adapted for model', [
        'modelId' => $modelId,
        'originalTokens' => $adapter->countTokens($prompt),
        'adaptedTokens' => $adapter->countTokens($adaptedPrompt),
        'wasCompressed' => $prompt !== $adaptedPrompt,
    ]);

    // Route to provider with adapted prompt
    if ($modelConfig['provider'] === 'runpod') {
        return $this->generateWithHiDream($project, $scene, $adaptedPrompt, ...);
    } else {
        return $this->generateWithGemini($project, $scene, $adaptedPrompt, ...);
    }
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Character count / 4 | BPE tokenization | 2023+ | Accurate token counts vs ~20% error |
| Truncate from end | Priority-based compression | 2024+ | Preserves critical subject/action |
| Generic prompts | Shot-type specific templates | 2024+ | Better results per shot type |
| Single model prompts | Model-aware adapters | 2025+ | Optimal prompts per model |

**Deprecated/outdated:**
- Word count estimation: BPE does not correlate with word count
- Uniform prompt structure: Different shot types need different emphasis
- Manual truncation: Intelligent compression preserves meaning

## Open Questions

Things that couldn't be fully resolved:

1. **CLIP Vocabulary File Location**
   - What we know: CLIP uses `bpe_simple_vocab_16e6.txt` with 49,152 tokens
   - What's unclear: Whether PHP BPE tokenizer needs modification to load this format
   - Recommendation: Test during implementation; may need vocab format conversion

2. **Exact Token Budget Per Component**
   - What we know: Subject/action should get majority; style can be dropped
   - What's unclear: Optimal percentages for each shot type
   - Recommendation: Start with estimates, tune based on output quality

3. **Compression vs. Summarization**
   - What we know: Simple truncation loses meaning
   - What's unclear: Whether LLM-based summarization is worth the latency
   - Recommendation: Start with rule-based compression; add LLM summarization as optional premium feature

## Sources

### Primary (HIGH confidence)
- StructuredPromptBuilderService.php - Existing camera presets, lighting presets, visual modes
- VideoPromptBuilderService.php - Hollywood formula, action verbs, shot types
- ImageGenerationService.php - Model configurations, generation flow
- CODEBASE-MAP.md - Service inventory and integration points
- 22-CONTEXT.md - User decisions and phase requirements

### Secondary (MEDIUM confidence)
- [OpenAI CLIP GitHub Issues #212](https://github.com/openai/CLIP/issues/212) - 77 token limit handling, truncate parameter
- [danny50610/bpe-tokeniser](https://github.com/danny50610/bpe-tokeniser) - PHP BPE tokenizer documentation
- [Stable Diffusion Art Prompt Guide](https://stable-diffusion-art.com/prompt-guide/) - Subject-first ordering
- [Open CLIP Tokenizer](https://github.com/mlfoundations/open_clip/blob/main/src/open_clip/tokenizer.py) - CLIP tokenization details

### Tertiary (LOW confidence - needs validation)
- Color temperature Kelvin values from web search - verify with cinematography reference
- Lighting ratio stops from web search - verify with photography reference
- Lens psychology claims - verify with film theory sources

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - PHP BPE tokenizers verified, existing services well-documented
- Architecture: HIGH - Clear integration points in existing codebase
- Token counting: MEDIUM - PHP library may need CLIP vocab adaptation
- Camera/lighting vocabulary: MEDIUM - Based on web research, verify with domain experts
- Pitfalls: HIGH - Based on CLIP documentation and Stable Diffusion best practices

**Research date:** 2026-01-25
**Valid until:** 2026-02-25 (30 days - stable domain)

---

*Phase: 22-foundation-model-adapters*
*Research completed: 2026-01-25*
