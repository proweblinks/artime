<?php

namespace Modules\AppVideoWizard\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Modules\AppVideoWizard\Models\WizardProject;
use Modules\AppVideoWizard\Models\WizardProcessingJob;
use Modules\AppVideoWizard\Services\ConceptService;
use Modules\AppVideoWizard\Services\ScriptGenerationService;
use Modules\AppVideoWizard\Services\ImageGenerationService;
use Modules\AppVideoWizard\Services\VoiceoverService;
use Modules\AppVideoWizard\Services\StockMediaService;
use Modules\AppVideoWizard\Services\CharacterExtractionService;
use Modules\AppVideoWizard\Services\LocationExtractionService;
use Modules\AppVideoWizard\Services\CinematographyService;
use Modules\AppVideoWizard\Models\VwGenerationLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoWizard extends Component
{
    use WithFileUploads;

    // Import file for project import
    public $importFile;

    // Reference image uploads for Character/Location/Style Bible
    public $characterImageUpload;
    public $locationImageUpload;
    public $styleImageUpload;
    public bool $isGeneratingStyleRef = false;

    // Project state
    public ?int $projectId = null;
    public string $projectName = 'Untitled Video';
    public int $currentStep = 1;
    public int $maxReachedStep = 1;

    // Step 1: Platform & Format
    public ?string $platform = null;
    public string $aspectRatio = '16:9';
    public int $targetDuration = 60;
    public ?string $format = null;
    public ?string $productionType = null;
    public ?string $productionSubtype = null;

    // Step 1: Production Configuration (matches original wizard)
    public array $production = [
        'type' => 'standard',           // Production type: standard, cinematic, documentary, etc.
        'subType' => null,              // Production subtype
        'targetDuration' => 60,         // Target duration in seconds
    ];

    // Step 1: Content Configuration (matches original wizard)
    public array $content = [
        'pacing' => 'balanced',         // 'fast' | 'balanced' | 'contemplative'
        'productionMode' => 'standard', // 'standard' | 'documentary' | 'thriller' | 'cinematic'
        'genre' => null,                // Genre for style consistency
        // MASTER VISUAL MODE - Enforced across ALL AI generation (locations, characters, images)
        // This is the TOP-LEVEL style authority - prevents style conflicts
        'visualMode' => 'cinematic-realistic', // 'cinematic-realistic' | 'stylized-animation' | 'mixed-hybrid'
        // AI Model Tier for script generation (cost vs quality)
        'aiModelTier' => 'economy',     // 'economy' | 'standard' | 'premium'
        // Content generation language
        'language' => 'en',             // ISO 639-1 language code
        'videoModel' => [
            'model' => 'hailuo-2.3',
            'duration' => '10s',        // Clip duration: '5s' | '6s' | '10s'
            'resolution' => '768p',
        ],
    ];

    /**
     * AI Model Tiers - Cost vs Quality options
     * Users can choose based on their budget and quality needs.
     */
    public const AI_MODEL_TIERS = [
        'economy' => [
            'label' => 'Economy',
            'description' => 'Best value, great quality',
            'icon' => '💰',
            'provider' => 'grok',
            'model' => 'grok-4-fast',
            'pricing' => '$0.20 / $0.50 per 1M tokens',
            'badge' => 'BEST VALUE',
            'badgeColor' => 'green',
        ],
        'standard' => [
            'label' => 'Standard',
            'description' => 'Balanced performance',
            'icon' => '⚡',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'pricing' => '$0.15 / $0.60 per 1M tokens',
            'badge' => 'POPULAR',
            'badgeColor' => 'blue',
        ],
        'premium' => [
            'label' => 'Premium',
            'description' => 'Maximum quality',
            'icon' => '👑',
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'pricing' => '$2.50 / $10 per 1M tokens',
            'badge' => 'BEST QUALITY',
            'badgeColor' => 'purple',
        ],
    ];

    /**
     * Supported languages for content generation
     * Country codes are ISO 3166-1 alpha-2 for flag images
     */
    public const SUPPORTED_LANGUAGES = [
        // Major Global Languages
        'en' => ['name' => 'English', 'native' => 'English', 'country' => 'us'],
        'es' => ['name' => 'Spanish', 'native' => 'Español', 'country' => 'es'],
        'fr' => ['name' => 'French', 'native' => 'Français', 'country' => 'fr'],
        'de' => ['name' => 'German', 'native' => 'Deutsch', 'country' => 'de'],
        'it' => ['name' => 'Italian', 'native' => 'Italiano', 'country' => 'it'],
        'pt' => ['name' => 'Portuguese', 'native' => 'Português', 'country' => 'pt'],
        'pt-br' => ['name' => 'Portuguese (Brazil)', 'native' => 'Português (Brasil)', 'country' => 'br'],
        'ru' => ['name' => 'Russian', 'native' => 'Русский', 'country' => 'ru'],
        'zh' => ['name' => 'Chinese', 'native' => '中文', 'country' => 'cn'],
        'ja' => ['name' => 'Japanese', 'native' => '日本語', 'country' => 'jp'],
        'ko' => ['name' => 'Korean', 'native' => '한국어', 'country' => 'kr'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'country' => 'sa'],
        'hi' => ['name' => 'Hindi', 'native' => 'हिन्दी', 'country' => 'in'],
        // European Languages
        'nl' => ['name' => 'Dutch', 'native' => 'Nederlands', 'country' => 'nl'],
        'pl' => ['name' => 'Polish', 'native' => 'Polski', 'country' => 'pl'],
        'uk' => ['name' => 'Ukrainian', 'native' => 'Українська', 'country' => 'ua'],
        'el' => ['name' => 'Greek', 'native' => 'Ελληνικά', 'country' => 'gr'],
        'cs' => ['name' => 'Czech', 'native' => 'Čeština', 'country' => 'cz'],
        'ro' => ['name' => 'Romanian', 'native' => 'Română', 'country' => 'ro'],
        'hu' => ['name' => 'Hungarian', 'native' => 'Magyar', 'country' => 'hu'],
        'sv' => ['name' => 'Swedish', 'native' => 'Svenska', 'country' => 'se'],
        'da' => ['name' => 'Danish', 'native' => 'Dansk', 'country' => 'dk'],
        'no' => ['name' => 'Norwegian', 'native' => 'Norsk', 'country' => 'no'],
        'fi' => ['name' => 'Finnish', 'native' => 'Suomi', 'country' => 'fi'],
        // Middle East & Asia
        'he' => ['name' => 'Hebrew', 'native' => 'עברית', 'country' => 'il'],
        'tr' => ['name' => 'Turkish', 'native' => 'Türkçe', 'country' => 'tr'],
        'fa' => ['name' => 'Persian', 'native' => 'فارسی', 'country' => 'ir'],
        'th' => ['name' => 'Thai', 'native' => 'ไทย', 'country' => 'th'],
        'vi' => ['name' => 'Vietnamese', 'native' => 'Tiếng Việt', 'country' => 'vn'],
        'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'country' => 'id'],
        'ms' => ['name' => 'Malay', 'native' => 'Bahasa Melayu', 'country' => 'my'],
        'tl' => ['name' => 'Filipino', 'native' => 'Tagalog', 'country' => 'ph'],
        'bn' => ['name' => 'Bengali', 'native' => 'বাংলা', 'country' => 'bd'],
        'ta' => ['name' => 'Tamil', 'native' => 'தமிழ்', 'country' => 'in'],
    ];

    /**
     * Visual Mode definitions - Master style authority
     * This determines whether ALL generated content is realistic or stylized.
     */
    public const VISUAL_MODES = [
        'cinematic-realistic' => [
            'label' => 'Cinematic Realistic',
            'description' => 'Photorealistic, live-action, Hollywood film quality',
            'enforcement' => 'REALISTIC ONLY. All visuals must be photorealistic, live-action quality. NO cartoon, anime, fantasy art styles, or stylized rendering. Think: Netflix film, HBO series, theatrical release.',
            'keywords' => 'photorealistic, live-action, cinematic, film grain, natural lighting, real-world textures, DSLR quality, 8K, professional cinematography',
            'forbidden' => 'cartoon, anime, illustrated, stylized, fantasy art, 3D render, CGI look, digital painting, concept art',
        ],
        'stylized-animation' => [
            'label' => 'Stylized Animation',
            'description' => '2D/3D animation, cartoon, anime, illustrated styles',
            'enforcement' => 'STYLIZED/ANIMATED ONLY. All visuals should be animated, illustrated, or stylized. Think: Pixar, Disney, anime, motion graphics.',
            'keywords' => '3D animation, 2D animation, cartoon, anime style, illustrated, digital art, stylized, motion graphics',
            'forbidden' => 'photorealistic, live-action, real footage, documentary',
        ],
        'mixed-hybrid' => [
            'label' => 'Mixed / Hybrid',
            'description' => 'Combination of realistic and stylized elements',
            'enforcement' => 'Mixed style allowed. Can combine realistic and stylized elements as appropriate for each scene.',
            'keywords' => 'flexible style, mixed media, creative interpretation',
            'forbidden' => '',
        ],
    ];

    /**
     * Professional Genre Presets - Camera Language & Style Bible
     * Based on Hollywood cinematography standards from the original wizard.
     * Each genre has specific camera movements, color grades, and lighting setups.
     */
    public const GENRE_PRESETS = [
        // Documentary genres
        'documentary-narrative' => [
            'camera' => 'smooth tracking, wide establishing shots, intimate close-ups',
            'colorGrade' => 'natural tones, slight desaturation, documentary realism',
            'lighting' => 'natural light, available light, practical sources',
            'atmosphere' => 'authentic environments, real textures, genuine moments',
            'style' => 'documentary realism, authentic, observational',
        ],
        'documentary-interview' => [
            'camera' => 'static interviews, dramatic zooms, handheld urgency',
            'colorGrade' => 'neutral, clean whites, professional',
            'lighting' => '3-point interview lighting, soft key, subtle fill',
            'atmosphere' => 'professional, clean backgrounds, focus on subject',
            'style' => 'interview style, professional, clear',
        ],
        'documentary-observational' => [
            'camera' => 'observational, intimate close-ups, environmental wide shots',
            'colorGrade' => 'natural, slightly warm, authentic',
            'lighting' => 'available light only, natural sources',
            'atmosphere' => 'fly-on-the-wall, unobtrusive, authentic',
            'style' => 'observational documentary, candid, real',
        ],

        // Cinematic genres
        'cinematic-thriller' => [
            'camera' => 'slow dolly, low angles, stabilized gimbal, anamorphic lens feel',
            'colorGrade' => 'desaturated teal shadows, amber highlights, crushed blacks',
            'lighting' => 'harsh single-source, dramatic rim lights, deep shadows',
            'atmosphere' => 'smoke, rain reflections, wet surfaces, urban grit',
            'style' => 'ultra-cinematic photoreal, noir thriller, high contrast',
        ],
        'cinematic-action' => [
            'camera' => 'fast dolly, dutch angles, tracking shots, crash zooms',
            'colorGrade' => 'high contrast, orange and teal, saturated',
            'lighting' => 'dramatic backlighting, lens flares, explosions',
            'atmosphere' => 'dust, debris, fire, motion blur',
            'style' => 'blockbuster action, high energy, dynamic composition',
        ],
        'cinematic-drama' => [
            'camera' => 'elegant slow movements, meaningful compositions, long takes',
            'colorGrade' => 'rich but restrained, natural skin tones, dramatic contrast',
            'lighting' => 'motivated lighting, golden hour, intimate practicals',
            'atmosphere' => 'subtle, realistic environments, emotional resonance',
            'style' => 'prestige drama, Oscar-worthy cinematography, emotional depth',
        ],

        // Horror genres
        'horror-psychological' => [
            'camera' => 'dutch angles, slow creeping push-ins, unstable handheld',
            'colorGrade' => 'desaturated, sickly greens, deep blacks, red accents',
            'lighting' => 'low-key, single source, harsh shadows, flickering',
            'atmosphere' => 'fog, dust motes, decayed textures, uncanny valley',
            'style' => 'psychological horror, unsettling, dreamlike quality',
        ],
        'horror-supernatural' => [
            'camera' => 'slow reveals, creeping dolly, ominous wide shots',
            'colorGrade' => 'cold blues, deep shadows, occasional warm accents',
            'lighting' => 'moonlight, candlelight, unnatural light sources',
            'atmosphere' => 'mist, ancient architecture, supernatural elements',
            'style' => 'supernatural horror, otherworldly, gothic atmosphere',
        ],

        // Tech & Education
        'tech-explainer' => [
            'camera' => 'smooth dolly, symmetrical framing, focus pulls',
            'colorGrade' => 'cool blues, clean whites, accent neon highlights',
            'lighting' => 'high-key soft lighting, no harsh shadows',
            'atmosphere' => 'clean gradient backgrounds, subtle particle effects',
            'style' => 'clean modern, minimal, high-tech aesthetic',
        ],
        'educational-general' => [
            'camera' => 'steady, clear framing, emphasis on content',
            'colorGrade' => 'vibrant but professional, clear contrast',
            'lighting' => 'bright, even lighting, no distracting shadows',
            'atmosphere' => 'clean, organized, professional',
            'style' => 'educational, clear visuals, engaging graphics',
        ],

        // Lifestyle & Inspirational
        'lifestyle-wellness' => [
            'camera' => 'slow gentle movements, intimate framing, breathing room',
            'colorGrade' => 'soft pastels, warm earth tones, gentle highlights',
            'lighting' => 'soft natural light, golden hour, diffused',
            'atmosphere' => 'peaceful, nature elements, organic textures',
            'style' => 'wellness aesthetic, natural beauty, calming visuals',
        ],
        'inspirational-epic' => [
            'camera' => 'rising crane shots, slow push-ins, sweeping wide angles',
            'colorGrade' => 'rich, dramatic, warm tones, cinematic depth',
            'lighting' => 'dramatic rim lighting, god rays, golden hour',
            'atmosphere' => 'grand vistas, epic scale, awe-inspiring',
            'style' => 'epic inspirational, sweeping, emotionally resonant',
        ],

        // Commercial genres
        'commercial-comedy' => [
            'camera' => 'quick cuts, reaction shots, comedic timing',
            'colorGrade' => 'bright, saturated, punchy colors',
            'lighting' => 'high-key, even, flattering',
            'atmosphere' => 'energetic, fun, approachable',
            'style' => 'comedy commercial, punchy, entertaining',
        ],
        'commercial-product' => [
            'camera' => 'dynamic angles, quick cuts, energetic pacing',
            'colorGrade' => 'high contrast, brand-appropriate, vibrant',
            'lighting' => 'product lighting, clean, dramatic accents',
            'atmosphere' => 'aspirational, modern, polished',
            'style' => 'product showcase, dynamic, modern',
        ],

        // Default/Standard
        'standard' => [
            'camera' => 'balanced movements, professional framing, smooth transitions',
            'colorGrade' => 'natural, balanced, professional',
            'lighting' => 'motivated, natural-looking, balanced',
            'atmosphere' => 'clean, professional, versatile',
            'style' => 'professional standard, versatile, clean',
        ],
    ];

    // Step 2: Concept
    public array $concept = [
        'rawInput' => '',
        'refinedConcept' => '',
        'keywords' => [],
        'keyElements' => [],
        'logline' => '',
        'suggestedMood' => null,
        'suggestedTone' => null,
        'styleReference' => '',
        'avoidElements' => '',
        'targetAudience' => '',
    ];

    // Step 2: Character Intelligence (affects script generation)
    public array $characterIntelligence = [
        'enabled' => true,
        'narrationStyle' => 'voiceover', // voiceover, dialogue, narrator, none
        'characterCount' => 4,
        'suggestedCount' => 4,
        'characters' => [], // Will be populated after script generation
    ];

    // Step 3: Script
    public array $script = [
        'title' => '',
        'hook' => '',
        'scenes' => [],
        'cta' => '',
        'totalDuration' => 0,
        'totalNarrationTime' => 0,
        // Timing configuration for Hollywood-style scene/shot architecture
        'timing' => [
            'sceneDuration' => 35,      // Default scene duration (30-60s Hollywood style)
            'clipDuration' => 10,       // Shot/clip duration (5s/6s/10s)
            'pacing' => 'balanced',     // Pacing affects scene duration
        ],
    ];

    /// Step 3: Progressive Script Generation State
    public array $scriptGeneration = [
        'status' => 'idle',              // 'idle' | 'generating' | 'paused' | 'complete'
        'targetSceneCount' => 0,         // Total scenes needed (e.g., 30)
        'generatedSceneCount' => 0,      // Scenes generated so far
        'batchSize' => 5,                // Scenes per batch
        'currentBatch' => 0,             // Current batch index (0-indexed)
        'totalBatches' => 0,             // Total batches needed
        'batches' => [],                 // Batch status tracking
        'autoGenerate' => false,         // Auto-continue to next batch
        'maxRetries' => 3,               // Max retry attempts per batch
        'retryDelayMs' => 1000,          // Base delay for exponential backoff (1s, 2s, 4s)
    ];

    // Step 3: Voice & Dialogue Status
    public array $voiceStatus = [
        'dialogueLines' => 0,
        'speakers' => 0,
        'voicesMapped' => 0,
        'scenesWithDialogue' => 0,
        'scenesWithVoiceover' => 0,
        'pendingVoices' => 0,
    ];

    // Step 4: Storyboard
    public array $storyboard = [
        'scenes' => [],
        'styleBible' => null,
        'imageModel' => 'nanobanana', // Default to NanoBanana (Gemini) - HiDream requires RunPod setup
        'visualStyle' => [
            'mood' => '',
            'lighting' => '',
            'colorPalette' => '',
            'composition' => '',
        ],
        'technicalSpecs' => [
            'enabled' => true,
            'quality' => '4k',
            'positive' => 'high quality, detailed, professional, 8K resolution, sharp focus',
            'negative' => 'blurry, low quality, ugly, distorted, watermark, nsfw, text, logo',
        ],
        'promptChain' => [
            'enabled' => true,
            'status' => 'pending',
            'processedAt' => null,
            'scenes' => [],
        ],
    ];

    // Step 5: Animation
    public array $animation = [
        'scenes' => [],
        'selectedSceneIndex' => 0,
        'voiceover' => [
            'voice' => 'nova',
            'speed' => 1.0,
        ],
    ];

    // Step 6: Assembly
    public array $assembly = [
        'transitions' => [],
        'defaultTransition' => 'fade',
        'music' => ['enabled' => false, 'trackId' => null, 'volume' => 30],
        'captions' => [
            'enabled' => true,
            'style' => 'karaoke',
            'position' => 'bottom',
            'size' => 1,
        ],
        // Hollywood-style shot-based assembly
        'shotBased' => false,               // Whether using shot-based assembly
        'collectedVideos' => [],            // All video URLs in order
        'sceneClips' => [],                 // Videos grouped by scene
        'totalDuration' => 0,               // Total video duration
        'assemblyStatus' => 'pending',      // 'pending' | 'collecting' | 'ready' | 'rendering' | 'complete'
        'renderProgress' => 0,              // Render progress percentage
        'finalVideoUrl' => null,            // Final rendered video URL
    ];

    // UI state
    public bool $isLoading = false;
    public bool $isSaving = false;
    public bool $isTransitioning = false;  // Track step transitions for loading overlay
    public ?string $transitionMessage = null;  // Message to show during transition
    public ?string $error = null;

    // Stock Media Browser state
    public bool $showStockBrowser = false;
    public int $stockBrowserSceneIndex = 0;
    public string $stockSearchQuery = '';
    public string $stockMediaType = 'image';
    public string $stockOrientation = 'landscape';
    public array $stockSearchResults = [];
    public bool $stockSearching = false;

    // Edit Prompt/Scene Modal state
    public bool $showEditPromptModal = false;
    public int $editPromptSceneIndex = 0;
    public string $editPromptText = '';
    public string $editSceneNarration = '';
    public int $editSceneDuration = 8;
    public string $editSceneTransition = 'cut';

    // Project Manager Modal state
    public bool $showProjectManager = false;
    public array $projectManagerProjects = [];
    public string $projectManagerSearch = '';
    public string $projectManagerSort = 'updated_at';
    public string $projectManagerSortDirection = 'desc';
    public string $projectManagerStatusFilter = 'all';
    public int $projectManagerPage = 1;
    public int $projectManagerPerPage = 12;
    public int $projectManagerTotal = 0;
    public array $projectManagerStatusCounts = [
        'all' => 0,
        'draft' => 0,
        'in_progress' => 0,
        'complete' => 0,
    ];
    public array $projectManagerSelected = [];
    public bool $projectManagerSelectMode = false;

    // Scene Memory state (Style Bible, Character Bible, Location Bible)
    public array $sceneMemory = [
        'styleBible' => [
            'enabled' => false,
            'style' => '',
            'colorGrade' => '',
            'atmosphere' => '',
            'camera' => '',
            'visualDNA' => '',
            'referenceImage' => '',
            'referenceImageSource' => '',
            'referenceImageBase64' => null,      // Base64 data for API calls (style consistency)
            'referenceImageMimeType' => null,    // MIME type (e.g., 'image/png')
            'referenceImageStatus' => 'none',    // 'none' | 'generating' | 'ready' | 'error'
        ],
        'characterBible' => [
            'enabled' => false,
            'characters' => [],
        ],
        'locationBible' => [
            'enabled' => false,
            'locations' => [],
        ],
    ];

    // RunPod job polling state
    public array $pendingJobs = [];

    // Generation progress tracking
    public int $generationProgress = 0;
    public int $generationTotal = 0;
    public ?string $generationCurrentScene = null;

    // Concept variations state
    public array $conceptVariations = [];
    public int $selectedConceptIndex = 0;

    // Script generation options
    public string $scriptTone = 'engaging';
    public string $contentDepth = 'detailed';
    public string $additionalInstructions = '';

    // Narrative Structure Intelligence (Hollywood-level script generation)
    public ?string $narrativePreset = null; // Platform-optimized storytelling formula
    public ?string $storyArc = null; // Structure like three-act, hero's journey
    public ?string $tensionCurve = null; // Pacing dynamics
    public ?string $emotionalJourney = null; // The feeling arc for viewers
    public bool $showNarrativeAdvanced = false; // Toggle for advanced options
    public ?string $contentFormatOverride = null; // Manual override: 'short' or 'feature' (null = auto from duration)

    // Multi-Shot Mode state (Hollywood-style scene → shots architecture)
    // Each scene is decomposed into multiple shots (5-10s clips)
    // Structure matches original video-creation-wizard.html
    public array $multiShotMode = [
        'enabled' => false,
        'defaultShotCount' => 3,          // Default shots per scene (2-6)
        'autoDecompose' => false,         // Auto-decompose scenes when enabled
        'decomposedScenes' => [],         // { sceneId: { shots: [], consistencyAnchors: {}, status: 'pending'|'ready' } }
        'batchStatus' => null,            // Batch decomposition status
        'globalVisualProfile' => null,    // Global visual style for all shots
    ];

    /**
     * Shot Structure Schema (for reference):
     * [
     *     'id' => 'shot-{sceneId}-{index}',
     *     'sceneId' => 'scene_1',
     *     'index' => 0,
     *     'imagePrompt' => 'Visual description for image generation',
     *     'videoPrompt' => 'Action description for video generation',
     *     'cameraMovement' => 'Pan left',  // Camera movement for Minimax
     *     'duration' => 5,                  // Shot duration: 5, 6, or 10 seconds
     *     'durationClass' => 'short',       // 'short' (5s), 'standard' (6s), 'cinematic' (10s)
     *     'imageUrl' => null,               // Generated image URL
     *     'imageStatus' => 'pending',       // 'pending' | 'generating' | 'ready' | 'error'
     *     'videoUrl' => null,               // Generated video URL
     *     'videoStatus' => 'pending',       // 'pending' | 'generating' | 'ready' | 'error'
     *     'fromSceneImage' => false,        // True if shot 1 uses scene's main image
     *     'fromFrameCapture' => false,      // True if image from previous shot's last frame
     *     'capturedFrameUrl' => null,       // URL of captured frame (for shot chaining)
     *     'dialogue' => null,               // Dialogue/narration for this shot
     *     'speakingCharacters' => [],       // Characters speaking in this shot
     * ]
     */

    public bool $showMultiShotModal = false;
    public int $multiShotSceneIndex = 0;
    public int $multiShotCount = 3;

    // Shot Preview Modal state
    public bool $showShotPreviewModal = false;
    public int $shotPreviewSceneIndex = 0;
    public int $shotPreviewShotIndex = 0;
    public string $shotPreviewTab = 'image'; // 'image' or 'video'

    // Frame Capture Modal state
    public bool $showFrameCaptureModal = false;
    public int $frameCaptureSceneIndex = 0;
    public int $frameCaptureShotIndex = 0;
    public ?string $capturedFrame = null;

    // Upscale Modal state
    public bool $showUpscaleModal = false;
    public int $upscaleSceneIndex = 0;
    public string $upscaleQuality = 'hd'; // 'hd' or '4k'
    public bool $isUpscaling = false;

    // AI Edit Modal state
    public bool $showAIEditModal = false;
    public int $aiEditSceneIndex = 0;
    public string $aiEditPrompt = '';
    public int $aiEditBrushSize = 30;
    public bool $isApplyingEdit = false;

    // Character Bible Modal state
    public bool $showCharacterBibleModal = false;
    public int $editingCharacterIndex = 0;
    public bool $isGeneratingPortrait = false;

    // Location Bible Modal state
    public bool $showLocationBibleModal = false;
    public int $editingLocationIndex = 0;
    public bool $isGeneratingLocationRef = false;

    // Scene Overwrite Confirmation Modal
    public bool $showSceneOverwriteModal = false;
    public string $sceneOverwriteAction = 'replace'; // 'replace' or 'append'

    // Storyboard Pagination (Performance optimization for 45+ scenes)
    public int $storyboardPage = 1;
    public int $storyboardPerPage = 12;

    // Save debouncing
    protected int $saveDebounceMs = 500;
    protected ?string $lastSaveHash = null;

    /**
     * Get paginated scenes for storyboard display.
     * Returns only scenes for current page to optimize rendering.
     */
    public function getPaginatedScenesProperty(): array
    {
        $allScenes = $this->script['scenes'] ?? [];
        $totalScenes = count($allScenes);

        if ($totalScenes <= $this->storyboardPerPage) {
            // No pagination needed for small scene counts
            return [
                'scenes' => $allScenes,
                'indices' => range(0, max(0, $totalScenes - 1)),
                'totalPages' => 1,
                'currentPage' => 1,
                'totalScenes' => $totalScenes,
                'showingFrom' => 1,
                'showingTo' => $totalScenes,
                'hasPrevious' => false,
                'hasNext' => false,
            ];
        }

        $totalPages = (int) ceil($totalScenes / $this->storyboardPerPage);
        $currentPage = max(1, min($this->storyboardPage, $totalPages));
        $offset = ($currentPage - 1) * $this->storyboardPerPage;

        $paginatedScenes = array_slice($allScenes, $offset, $this->storyboardPerPage, true);
        $indices = array_keys($paginatedScenes);

        return [
            'scenes' => array_values($paginatedScenes),
            'indices' => $indices,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'totalScenes' => $totalScenes,
            'showingFrom' => $offset + 1,
            'showingTo' => min($offset + $this->storyboardPerPage, $totalScenes),
            'hasPrevious' => $currentPage > 1,
            'hasNext' => $currentPage < $totalPages,
        ];
    }

    /**
     * Navigate to storyboard page.
     */
    public function goToStoryboardPage(int $page): void
    {
        $totalPages = (int) ceil(count($this->script['scenes'] ?? []) / $this->storyboardPerPage);
        $this->storyboardPage = max(1, min($page, $totalPages));
    }

    /**
     * Navigate to next storyboard page.
     */
    public function nextStoryboardPage(): void
    {
        $this->goToStoryboardPage($this->storyboardPage + 1);
    }

    /**
     * Navigate to previous storyboard page.
     */
    public function previousStoryboardPage(): void
    {
        $this->goToStoryboardPage($this->storyboardPage - 1);
    }

    /**
     * Jump to the page containing a specific scene.
     */
    public function goToScenePage(int $sceneIndex): void
    {
        $page = (int) floor($sceneIndex / $this->storyboardPerPage) + 1;
        $this->goToStoryboardPage($page);
    }

    /**
     * Mount the component.
     * Note: We accept mixed $project to avoid Livewire's implicit model binding
     * which fails when null is passed.
     */
    public function mount($project = null)
    {
        // Handle both WizardProject instance and null
        if ($project instanceof WizardProject && $project->exists) {
            $this->loadProject($project);
            $this->recoverPendingJobs($project);
        }
    }

    /**
     * Recover pending async jobs from database.
     * This restores job tracking after page refresh.
     * Jobs older than 10 minutes are automatically marked as timed out.
     */
    protected function recoverPendingJobs(WizardProject $project): void
    {
        $pendingJobs = WizardProcessingJob::where('project_id', $project->id)
            ->whereIn('status', [
                WizardProcessingJob::STATUS_PENDING,
                WizardProcessingJob::STATUS_PROCESSING
            ])
            ->get();

        // First, clean up any stuck "generating" scenes that don't have valid pending jobs
        $this->cleanupStuckScenes($project, $pendingJobs);

        if ($pendingJobs->isEmpty()) {
            return;
        }

        // Timeout threshold: 10 minutes
        $timeoutThreshold = now()->subMinutes(10);

        // Restore pending jobs to component state
        foreach ($pendingJobs as $job) {
            // Check if job has timed out
            if ($job->created_at < $timeoutThreshold) {
                $job->markAsFailed('Job timed out after 10 minutes');
                \Log::warning("Auto-cancelled timed out job", [
                    'jobId' => $job->id,
                    'externalJobId' => $job->external_job_id,
                    'createdAt' => $job->created_at,
                ]);
                continue;
            }

            $inputData = $job->input_data ?? [];
            $sceneIndex = $inputData['sceneIndex'] ?? null;

            if ($sceneIndex !== null && $job->type === WizardProcessingJob::TYPE_IMAGE_GENERATION) {
                // Mark scene as generating in storyboard
                if (!isset($this->storyboard['scenes'][$sceneIndex])) {
                    $this->storyboard['scenes'][$sceneIndex] = [];
                }
                $this->storyboard['scenes'][$sceneIndex]['status'] = 'generating';
                $this->storyboard['scenes'][$sceneIndex]['jobId'] = $job->external_job_id;
                $this->storyboard['scenes'][$sceneIndex]['processingJobId'] = $job->id;

                // Add to pendingJobs array for polling
                $this->pendingJobs[$sceneIndex] = [
                    'jobId' => $job->external_job_id,
                    'processingJobId' => $job->id,
                    'type' => $job->type,
                    'sceneIndex' => $sceneIndex,
                ];
            }
        }

        // Dispatch event to start polling if we have pending jobs
        if (!empty($this->pendingJobs)) {
            $this->dispatch('resume-job-polling', count: count($this->pendingJobs));
        }
    }

    /**
     * Clean up scenes that are stuck in "generating" status without valid pending jobs.
     */
    protected function cleanupStuckScenes(WizardProject $project, $pendingJobs): void
    {
        // Get scene indices that have valid pending jobs
        $validPendingScenes = [];
        foreach ($pendingJobs as $job) {
            $inputData = $job->input_data ?? [];
            $sceneIndex = $inputData['sceneIndex'] ?? null;
            if ($sceneIndex !== null) {
                $validPendingScenes[] = $sceneIndex;
            }
        }

        // Check each scene in storyboard
        $needsSave = false;
        if (isset($this->storyboard['scenes']) && is_array($this->storyboard['scenes'])) {
            foreach ($this->storyboard['scenes'] as $index => $scene) {
                // If scene is "generating" but doesn't have a valid pending job, reset it
                if (($scene['status'] ?? '') === 'generating' && !in_array($index, $validPendingScenes)) {
                    // Reset to error state to allow regeneration
                    $this->storyboard['scenes'][$index]['status'] = 'error';
                    $this->storyboard['scenes'][$index]['error'] = 'Previous generation failed or was interrupted';
                    unset($this->storyboard['scenes'][$index]['jobId']);
                    unset($this->storyboard['scenes'][$index]['processingJobId']);
                    $needsSave = true;
                }
            }
        }

        // Save if we made changes
        if ($needsSave) {
            $this->saveProject();
        }
    }

    /**
     * Load project data into component state.
     */
    protected function loadProject(WizardProject $project): void
    {
        $this->projectId = $project->id;
        $this->projectName = $project->name;
        $this->currentStep = $project->current_step;
        $this->maxReachedStep = $project->max_reached_step;

        $this->platform = $project->platform;
        $this->aspectRatio = $project->aspect_ratio;
        $this->targetDuration = $project->target_duration;
        $this->format = $project->format;
        $this->productionType = $project->production_type;
        $this->productionSubtype = $project->production_subtype;

        if ($project->concept) {
            $this->concept = array_merge($this->concept, $project->concept);
        }
        if ($project->script) {
            $this->script = array_merge($this->script, $project->script);
            // Sanitize loaded script data to prevent type errors in views
            $this->sanitizeScriptData();
        }
        if ($project->storyboard) {
            $this->storyboard = array_merge($this->storyboard, $project->storyboard);
        }
        if ($project->animation) {
            $this->animation = array_merge($this->animation, $project->animation);
        }
        if ($project->assembly) {
            $this->assembly = array_merge($this->assembly, $project->assembly);
        }

        // Restore Scene Memory, Multi-Shot Mode, Concept Variations, and Character Intelligence from content_config
        if ($project->content_config) {
            $config = $project->content_config;

            if (isset($config['sceneMemory'])) {
                $this->sceneMemory = array_merge($this->sceneMemory, $config['sceneMemory']);
            }
            if (isset($config['multiShotMode'])) {
                $this->multiShotMode = array_merge($this->multiShotMode, $config['multiShotMode']);
            }
            if (isset($config['conceptVariations'])) {
                $this->conceptVariations = $config['conceptVariations'];
            }
            if (isset($config['characterIntelligence'])) {
                $this->characterIntelligence = array_merge($this->characterIntelligence, $config['characterIntelligence']);
            }

            // Restore script generation state (for resuming after browser refresh)
            if (isset($config['scriptGeneration'])) {
                $this->scriptGeneration = array_merge($this->scriptGeneration, $config['scriptGeneration']);
                // If generation was in progress, set to paused so user can resume
                if (in_array($this->scriptGeneration['status'], ['generating', 'retrying'])) {
                    $this->scriptGeneration['status'] = 'paused';
                    // Also update any generating/retrying batches to pending
                    foreach ($this->scriptGeneration['batches'] as &$batch) {
                        if (in_array($batch['status'], ['generating', 'retrying'])) {
                            $batch['status'] = 'pending';
                        }
                    }
                }
            }

            // Restore production and content configuration (Hollywood-style scene/shot architecture)
            if (isset($config['production'])) {
                $this->production = array_merge($this->production, $config['production']);
            }
            if (isset($config['content'])) {
                $this->content = array_merge($this->content, $config['content']);
            }
        }

        // Recalculate voice status if script exists
        if (!empty($this->script['scenes'])) {
            $this->recalculateVoiceStatus();
        }

        // Initialize save hash to prevent redundant save after loading
        $this->lastSaveHash = $this->computeSaveHash();
    }

    /**
     * Compute a hash of the current saveable state.
     * Used to detect changes and avoid redundant saves.
     */
    protected function computeSaveHash(): string
    {
        $data = [
            'name' => $this->projectName,
            'current_step' => $this->currentStep,
            'platform' => $this->platform,
            'aspect_ratio' => $this->aspectRatio,
            'target_duration' => $this->targetDuration,
            'format' => $this->format,
            'production_type' => $this->productionType,
            'production_subtype' => $this->productionSubtype,
            'concept' => $this->concept,
            'script' => $this->script,
            'storyboard' => $this->storyboard,
            'animation' => $this->animation,
            'assembly' => $this->assembly,
            'sceneMemory' => $this->sceneMemory,
            'multiShotMode' => $this->multiShotMode,
            'production' => $this->production,
            'content' => $this->content,
        ];

        return md5(json_encode($data));
    }

    /**
     * Save project with change detection to avoid redundant database writes.
     * Uses hash comparison to skip saves when nothing has changed.
     */
    public function saveProject(): void
    {
        // Skip save if nothing has changed (except for new projects)
        $currentHash = $this->computeSaveHash();
        if ($this->projectId && $this->lastSaveHash === $currentHash) {
            return; // No changes to save
        }

        $this->isSaving = true;
        $isNewProject = !$this->projectId;

        try {
            $data = [
                'name' => $this->projectName,
                'current_step' => $this->currentStep,
                'max_reached_step' => max($this->maxReachedStep, $this->currentStep),
                'platform' => $this->platform,
                'aspect_ratio' => $this->aspectRatio,
                'target_duration' => $this->targetDuration,
                'format' => $this->format,
                'production_type' => $this->productionType,
                'production_subtype' => $this->productionSubtype,
                'concept' => $this->concept,
                'script' => $this->script,
                'storyboard' => $this->storyboard,
                'animation' => $this->animation,
                'assembly' => $this->assembly,
                // Save Scene Memory, Multi-Shot Mode, Concept Variations, Character Intelligence, Script Generation State,
                // and Hollywood-style Production/Content configuration
                'content_config' => [
                    'sceneMemory' => $this->sceneMemory,
                    'multiShotMode' => $this->multiShotMode,
                    'conceptVariations' => $this->conceptVariations,
                    'characterIntelligence' => $this->characterIntelligence,
                    'scriptGeneration' => $this->scriptGeneration,
                    'production' => $this->production,
                    'content' => $this->content,
                ],
            ];

            if ($this->projectId) {
                $project = WizardProject::findOrFail($this->projectId);
                $project->update($data);
            } else {
                $project = WizardProject::create(array_merge($data, [
                    'user_id' => auth()->id(),
                    'team_id' => session('current_team_id'),
                ]));
                $this->projectId = $project->id;
            }

            // Update hash after successful save
            $this->lastSaveHash = $currentHash;

            $this->dispatch('project-saved', projectId: $this->projectId);

            // Update browser URL with project ID for new projects
            if ($isNewProject && $this->projectId) {
                $this->dispatch('update-browser-url', projectId: $this->projectId);
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to save project: ' . $e->getMessage();
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Force save project even if no changes detected.
     * Use this when you need to ensure the save happens.
     */
    public function forceSaveProject(): void
    {
        $this->lastSaveHash = null;
        $this->saveProject();
    }

    /**
     * Go to a specific step.
     */
    public function goToStep(int $step): void
    {
        if ($step < 1 || $step > 7) {
            return;
        }

        // Can only go to steps we've reached or the next step
        if ($step <= $this->maxReachedStep + 1) {
            $previousStep = $this->currentStep;
            $this->currentStep = $step;
            $this->maxReachedStep = max($this->maxReachedStep, $step);

            // Step Transition Hook: Auto-populate Scene Memory when entering Storyboard (step 4)
            // Use deferred async call to prevent blocking the UI
            if ($step === 4 && $previousStep !== 4 && !empty($this->script['scenes'])) {
                $this->isTransitioning = true;
                $this->transitionMessage = __('Analyzing script for characters and locations...');

                // Dispatch event to trigger async population after view renders
                $this->dispatch('step-changed', step: $step, needsPopulation: true);
            }

            // Only save if user is authenticated
            if (auth()->check()) {
                $this->saveProject();
            }
        }
    }

    /**
     * Handle deferred scene memory population after step transition.
     * This is called async after the view renders to prevent blocking.
     */
    #[On('populate-scene-memory')]
    public function handleDeferredSceneMemoryPopulation(): void
    {
        if (!$this->isTransitioning) {
            return;
        }

        try {
            $this->autoPopulateSceneMemory();
        } catch (\Exception $e) {
            Log::warning('VideoWizard: Scene memory population failed', ['error' => $e->getMessage()]);
        } finally {
            $this->isTransitioning = false;
            $this->transitionMessage = null;
        }
    }

    /**
     * Go to next step.
     */
    public function nextStep(): void
    {
        $this->goToStep($this->currentStep + 1);
    }

    /**
     * Go to previous step.
     */
    public function previousStep(): void
    {
        $this->goToStep($this->currentStep - 1);
    }

    /**
     * Update platform selection.
     */
    public function selectPlatform(string $platformId): void
    {
        $this->platform = $platformId;

        $platforms = config('appvideowizard.platforms');
        if (isset($platforms[$platformId])) {
            $platform = $platforms[$platformId];
            $this->aspectRatio = $platform['defaultFormat'];

            // Get the max duration - but respect production type's suggested range if it's higher
            $maxDuration = $platform['maxDuration'];
            $minDuration = $platform['minDuration'];

            // If production type is set and allows longer videos, use that range instead
            $productionDuration = $this->getProductionTypeDurationRange();
            if ($productionDuration) {
                // Use the higher of platform max or production type max (for movies/films)
                $maxDuration = max($maxDuration, $productionDuration['max']);
                $minDuration = $productionDuration['min'];
            }

            // Only adjust duration if it's outside the valid range
            if ($this->targetDuration < $minDuration) {
                $this->targetDuration = $minDuration;
            } elseif ($this->targetDuration > $maxDuration) {
                $this->targetDuration = $maxDuration;
            }
        }
        // Note: Don't auto-save on selection - will save on step navigation
    }

    /**
     * Get the suggested duration range for the selected production type.
     */
    public function getProductionTypeDurationRange(): ?array
    {
        if (empty($this->productionType)) {
            return null;
        }

        $productionTypes = config('appvideowizard.production_types', []);
        $type = $productionTypes[$this->productionType] ?? null;

        if (!$type) {
            return null;
        }

        // Check if subtype has a specific duration range
        if ($this->productionSubtype && isset($type['subTypes'][$this->productionSubtype]['suggestedDuration'])) {
            return $type['subTypes'][$this->productionSubtype]['suggestedDuration'];
        }

        // Look for duration in any subtype as a fallback
        if (isset($type['subTypes']) && is_array($type['subTypes'])) {
            foreach ($type['subTypes'] as $subtype) {
                if (isset($subtype['suggestedDuration'])) {
                    return $subtype['suggestedDuration'];
                }
            }
        }

        return null;
    }

    /**
     * Update format selection.
     */
    public function selectFormat(string $formatId): void
    {
        $this->format = $formatId;

        $formats = config('appvideowizard.formats');
        if (isset($formats[$formatId])) {
            $this->aspectRatio = $formats[$formatId]['aspectRatio'];
        }
        // Note: Don't auto-save on selection - will save on step navigation
    }

    /**
     * Update production type and auto-apply recommended narrative preset.
     * This enables cascading selection where Step 1 choices influence Step 3 defaults.
     */
    public function selectProductionType(string $type, ?string $subtype = null): void
    {
        $this->productionType = $type;
        $this->productionSubtype = $subtype;

        // Get the recommended preset mapping and auto-apply default
        $mapping = $this->getPresetMappingForProduction();
        if (!empty($mapping['default']) && empty($this->narrativePreset)) {
            $this->applyNarrativePreset($mapping['default']);
        }
        // Note: Don't auto-save on selection - will save on step navigation
    }

    /**
     * Determine if current duration is feature-length (20+ minutes).
     */
    public function isFeatureLength(): bool
    {
        return $this->targetDuration >= 1200; // 20 minutes in seconds
    }

    /**
     * Get the content format category based on duration or manual override.
     */
    public function getContentFormat(): string
    {
        // Manual override takes precedence
        if ($this->contentFormatOverride !== null) {
            return $this->contentFormatOverride;
        }
        return $this->isFeatureLength() ? 'feature' : 'short';
    }

    /**
     * Toggle between short and feature format manually.
     */
    public function toggleContentFormat(): void
    {
        $currentFormat = $this->getContentFormat();
        $this->contentFormatOverride = ($currentFormat === 'short') ? 'feature' : 'short';

        // Clear current preset when format changes so user selects appropriate one
        $this->narrativePreset = null;

        // Auto-apply the default preset for the new format
        $mapping = $this->getPresetMappingForProduction();
        if (!empty($mapping['default'])) {
            $this->applyNarrativePreset($mapping['default']);
        }
    }

    /**
     * Set content format explicitly.
     */
    public function setContentFormat(string $format): void
    {
        if (in_array($format, ['short', 'feature'])) {
            $this->contentFormatOverride = $format;

            // Clear current preset and apply new default
            $this->narrativePreset = null;
            $mapping = $this->getPresetMappingForProduction();
            if (!empty($mapping['default'])) {
                $this->applyNarrativePreset($mapping['default']);
            }
        }
    }

    /**
     * Get the preset mapping for current production type/subtype.
     * Returns recommended, compatible presets based on content format (short/feature).
     */
    public function getPresetMappingForProduction(): array
    {
        $mappings = config('appvideowizard.production_preset_mapping', []);
        $format = $this->getContentFormat();

        $emptyMapping = [
            'default' => null,
            'recommended' => [],
            'compatible' => [],
        ];

        if (empty($this->productionType) || !isset($mappings[$this->productionType])) {
            return $emptyMapping;
        }

        $typeMapping = $mappings[$this->productionType];

        // First check for specific subtype mapping, then fall back to _default
        $subtypeMapping = null;
        if (!empty($this->productionSubtype) && isset($typeMapping[$this->productionSubtype])) {
            $subtypeMapping = $typeMapping[$this->productionSubtype];
        } else {
            $subtypeMapping = $typeMapping['_default'] ?? null;
        }

        if (!$subtypeMapping) {
            return $emptyMapping;
        }

        // Get the format-specific mapping (short or feature)
        $formatMapping = $subtypeMapping[$format] ?? null;

        // If no mapping for this format, try the other format as fallback
        if (!$formatMapping) {
            $fallbackFormat = $format === 'feature' ? 'short' : 'feature';
            $formatMapping = $subtypeMapping[$fallbackFormat] ?? null;
        }

        return $formatMapping ?? $emptyMapping;
    }

    /**
     * Get narrative presets organized by recommendation level.
     * Used by the view to display presets with proper hierarchy.
     * Filters presets by content format (short/feature) based on duration.
     */
    public function getOrganizedNarrativePresets(): array
    {
        $allPresets = config('appvideowizard.narrative_presets', []);
        $mapping = $this->getPresetMappingForProduction();
        $format = $this->getContentFormat();

        $recommended = [];
        $compatible = [];
        $other = [];

        foreach ($allPresets as $key => $preset) {
            $presetCategory = $preset['category'] ?? 'short';

            // Filter by content format - only show presets matching current format
            // unless they're in recommended/compatible lists
            $inRecommended = in_array($key, $mapping['recommended'] ?? []);
            $inCompatible = in_array($key, $mapping['compatible'] ?? []);

            if ($inRecommended) {
                $recommended[$key] = $preset;
            } elseif ($inCompatible) {
                $compatible[$key] = $preset;
            } elseif ($presetCategory === $format) {
                // Only show other presets if they match the current format
                $other[$key] = $preset;
            }
        }

        return [
            'recommended' => $recommended,
            'compatible' => $compatible,
            'other' => $other,
            'defaultPreset' => $mapping['default'] ?? null,
            'contentFormat' => $format,
        ];
    }

    /**
     * Update concept.
     */
    #[On('concept-updated')]
    public function updateConcept(array $conceptData): void
    {
        $this->concept = array_merge($this->concept, $conceptData);
        $this->saveProject();
    }

    /**
     * Enhance concept with AI.
     */
    public function enhanceConcept(): void
    {
        $startTime = microtime(true);
        $promptSlug = 'concept-enhance';

        // Dispatch debug event to browser
        $this->dispatch('vw-debug', [
            'action' => 'enhance-concept-start',
            'message' => 'Starting AI concept enhancement',
            'data' => [
                'rawInput' => substr($this->concept['rawInput'] ?? '', 0, 100) . '...',
                'productionType' => $this->productionType,
                'productionSubtype' => $this->productionSubtype,
            ]
        ]);

        if (empty($this->concept['rawInput'])) {
            $this->error = __('Please enter a concept description first.');
            $this->dispatch('vw-debug', [
                'action' => 'enhance-concept-error',
                'message' => 'No concept input provided',
                'level' => 'warn'
            ]);
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        $inputData = [
            'rawInput' => $this->concept['rawInput'],
            'productionType' => $this->productionType,
            'productionSubType' => $this->productionSubtype,
            'teamId' => session('current_team_id', 0),
        ];

        try {
            Log::info('VideoWizard: Starting concept enhancement', $inputData);

            $conceptService = app(ConceptService::class);

            $result = $conceptService->improveConcept($this->concept['rawInput'], [
                'productionType' => $this->productionType,
                'productionSubType' => $this->productionSubtype,
                'teamId' => session('current_team_id', 0),
            ]);

            $durationMs = (int)((microtime(true) - $startTime) * 1000);

            // Log success to admin panel
            try {
                VwGenerationLog::logSuccess(
                    $promptSlug,
                    $inputData,
                    $result,
                    null, // tokens - not available from ConceptService
                    $durationMs,
                    $this->projectId,
                    auth()->id(),
                    session('current_team_id')
                );
            } catch (\Exception $logEx) {
                Log::warning('VideoWizard: Failed to log generation success', ['error' => $logEx->getMessage()]);
            }

            // Update concept with AI-enhanced data
            $this->concept['refinedConcept'] = $result['improvedConcept'] ?? '';
            $this->concept['logline'] = $result['logline'] ?? '';
            $this->concept['suggestedMood'] = $result['suggestedMood'] ?? null;
            $this->concept['suggestedTone'] = $result['suggestedTone'] ?? null;
            $this->concept['keyElements'] = $result['keyElements'] ?? [];
            $this->concept['targetAudience'] = $result['targetAudience'] ?? '';

            // Also populate avoid elements if AI suggested them
            if (!empty($result['avoidElements']) && is_array($result['avoidElements'])) {
                $this->concept['avoidElements'] = implode(', ', $result['avoidElements']);
            }

            $this->saveProject();

            // Dispatch success debug event
            $this->dispatch('vw-debug', [
                'action' => 'enhance-concept-success',
                'message' => 'Concept enhanced successfully',
                'data' => [
                    'duration_ms' => $durationMs,
                    'has_refined' => !empty($this->concept['refinedConcept']),
                    'has_logline' => !empty($this->concept['logline']),
                    'mood' => $this->concept['suggestedMood'],
                    'tone' => $this->concept['suggestedTone'],
                ]
            ]);

            $this->dispatch('concept-enhanced');

            Log::info('VideoWizard: Concept enhancement completed', [
                'project_id' => $this->projectId,
                'duration_ms' => $durationMs,
            ]);

        } catch (\Exception $e) {
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            // Log failure to admin panel
            try {
                VwGenerationLog::logFailure(
                    $promptSlug,
                    $inputData,
                    $errorMessage,
                    $durationMs,
                    $this->projectId,
                    auth()->id(),
                    session('current_team_id')
                );
            } catch (\Exception $logEx) {
                Log::warning('VideoWizard: Failed to log generation failure', ['error' => $logEx->getMessage()]);
            }

            // Dispatch error debug event
            $this->dispatch('vw-debug', [
                'action' => 'enhance-concept-error',
                'message' => 'Concept enhancement failed: ' . $errorMessage,
                'level' => 'error',
                'data' => [
                    'error' => $errorMessage,
                    'duration_ms' => $durationMs,
                    'trace' => $e->getTraceAsString(),
                ]
            ]);

            Log::error('VideoWizard: Concept enhancement failed', [
                'project_id' => $this->projectId,
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error = __('Failed to enhance concept: ') . $errorMessage;
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Apply the enhanced concept to the main input.
     * This copies the refined concept to rawInput so the user can edit it further.
     */
    public function applyEnhancedConcept(): void
    {
        if (!empty($this->concept['refinedConcept'])) {
            // Apply the refined concept to the main input
            $this->concept['rawInput'] = $this->concept['refinedConcept'];

            // Keep the enhancement data but mark as applied
            $this->saveProject();

            $this->dispatch('vw-debug', [
                'action' => 'apply-enhancement',
                'message' => 'Enhanced concept applied to input',
            ]);
        }
    }

    /**
     * Dismiss the enhancement preview without applying.
     * This clears the refined concept but keeps the original input.
     */
    public function dismissEnhancement(): void
    {
        // Clear the refined concept to hide the preview
        $this->concept['refinedConcept'] = '';
        $this->concept['logline'] = '';
        $this->concept['suggestedMood'] = null;
        $this->concept['suggestedTone'] = null;
        $this->concept['keyElements'] = [];
        $this->concept['targetAudience'] = '';

        $this->saveProject();

        $this->dispatch('vw-debug', [
            'action' => 'dismiss-enhancement',
            'message' => 'Enhancement preview dismissed',
        ]);
    }

    /**
     * Generate unique ideas based on concept.
     */
    public function generateIdeas(): void
    {
        $startTime = microtime(true);
        $promptSlug = 'concept-ideas';

        $this->dispatch('vw-debug', [
            'action' => 'generate-ideas-start',
            'message' => 'Starting AI idea generation',
            'data' => ['rawInput' => substr($this->concept['rawInput'] ?? '', 0, 100) . '...']
        ]);

        if (empty($this->concept['rawInput'])) {
            $this->error = __('Please enter a concept description first.');
            $this->dispatch('vw-debug', ['action' => 'generate-ideas-error', 'message' => 'No concept input', 'level' => 'warn']);
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        $inputData = [
            'rawInput' => $this->concept['rawInput'],
            'productionType' => $this->productionType,
            'teamId' => session('current_team_id', 0),
        ];

        try {
            Log::info('VideoWizard: Starting idea generation', $inputData);

            $conceptService = app(ConceptService::class);

            // First enhance the concept if not already done
            if (empty($this->concept['refinedConcept'])) {
                $result = $conceptService->improveConcept($this->concept['rawInput'], [
                    'productionType' => $this->productionType,
                    'productionSubType' => $this->productionSubtype,
                    'teamId' => session('current_team_id', 0),
                ]);

                $this->concept['refinedConcept'] = $result['improvedConcept'] ?? '';
                $this->concept['logline'] = $result['logline'] ?? '';
                $this->concept['suggestedMood'] = $result['suggestedMood'] ?? null;
                $this->concept['suggestedTone'] = $result['suggestedTone'] ?? null;
                $this->concept['keyElements'] = $result['keyElements'] ?? [];
                $this->concept['targetAudience'] = $result['targetAudience'] ?? '';
            }

            // Generate concept variations
            $variations = $conceptService->generateVariations(
                $this->concept['refinedConcept'] ?: $this->concept['rawInput'],
                3,
                ['teamId' => session('current_team_id', 0)]
            );

            $durationMs = (int)((microtime(true) - $startTime) * 1000);

            // Log success
            try {
                VwGenerationLog::logSuccess(
                    $promptSlug,
                    $inputData,
                    ['variations_count' => count($variations)],
                    null,
                    $durationMs,
                    $this->projectId,
                    auth()->id(),
                    session('current_team_id')
                );
            } catch (\Exception $logEx) {
                Log::warning('VideoWizard: Failed to log generation success', ['error' => $logEx->getMessage()]);
            }

            $this->conceptVariations = $variations;
            $this->selectedConceptIndex = 0;

            $this->saveProject();

            $this->dispatch('vw-debug', [
                'action' => 'generate-ideas-success',
                'message' => 'Ideas generated successfully',
                'data' => ['variations_count' => count($variations), 'duration_ms' => $durationMs]
            ]);

            Log::info('VideoWizard: Idea generation completed', ['variations' => count($variations), 'duration_ms' => $durationMs]);

        } catch (\Exception $e) {
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            try {
                VwGenerationLog::logFailure($promptSlug, $inputData, $errorMessage, $durationMs, $this->projectId, auth()->id(), session('current_team_id'));
            } catch (\Exception $logEx) {
                Log::warning('VideoWizard: Failed to log generation failure', ['error' => $logEx->getMessage()]);
            }

            $this->dispatch('vw-debug', [
                'action' => 'generate-ideas-error',
                'message' => 'Idea generation failed: ' . $errorMessage,
                'level' => 'error',
                'data' => ['error' => $errorMessage, 'duration_ms' => $durationMs]
            ]);

            Log::error('VideoWizard: Idea generation failed', ['error' => $errorMessage]);
            $this->error = __('Failed to generate ideas: ') . $errorMessage;
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Select a concept variation.
     */
    public function selectConceptVariation(int $index): void
    {
        if (isset($this->conceptVariations[$index])) {
            $this->selectedConceptIndex = $index;

            // Update the refined concept with the selected variation
            $variation = $this->conceptVariations[$index];
            $this->concept['refinedConcept'] = $variation['concept'] ?? $this->concept['refinedConcept'];

            $this->saveProject();
        }
    }

    /**
     * Generate different concepts (re-generate variations).
     */
    public function generateDifferentConcepts(): void
    {
        if (empty($this->concept['rawInput'])) {
            $this->error = __('Please enter a concept description first.');
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        try {
            $conceptService = app(ConceptService::class);

            // Generate new variations
            $variations = $conceptService->generateVariations(
                $this->concept['rawInput'], // Use original input for fresh variations
                3,
                ['teamId' => session('current_team_id', 0)]
            );

            $this->conceptVariations = $variations;
            $this->selectedConceptIndex = 0;

            // Update refined concept with first variation
            if (!empty($variations[0]['concept'])) {
                $this->concept['refinedConcept'] = $variations[0]['concept'];
            }

            $this->saveProject();

        } catch (\Exception $e) {
            $this->error = __('Failed to generate concepts: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Generate script using AI.
     */
    #[On('generate-script')]
    public function generateScript(): void
    {
        $startTime = microtime(true);
        $promptSlug = 'script-generation';

        // Dispatch debug event to browser
        $this->dispatch('vw-debug', [
            'action' => 'generate-script-start',
            'message' => 'Starting AI script generation',
            'data' => [
                'project_id' => $this->projectId,
                'tone' => $this->scriptTone,
                'contentDepth' => $this->contentDepth,
                'targetDuration' => $this->targetDuration,
            ]
        ]);

        if (empty($this->concept['rawInput']) && empty($this->concept['refinedConcept'])) {
            $this->error = __('Please complete the concept step first.');
            $this->dispatch('vw-debug', [
                'action' => 'generate-script-error',
                'message' => 'No concept input provided',
                'level' => 'warn'
            ]);
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        $inputData = [
            'concept' => substr($this->concept['refinedConcept'] ?: $this->concept['rawInput'], 0, 200),
            'tone' => $this->scriptTone,
            'contentDepth' => $this->contentDepth,
            'targetDuration' => $this->targetDuration,
            'additionalInstructions' => $this->additionalInstructions,
            'narrativePreset' => $this->narrativePreset,
            'storyArc' => $this->storyArc,
            'tensionCurve' => $this->tensionCurve,
            'emotionalJourney' => $this->emotionalJourney,
            'teamId' => session('current_team_id', 0),
        ];

        try {
            Log::info('VideoWizard: Starting script generation', $inputData);

            // Always save project first to ensure database has latest settings (duration, etc.)
            $this->forceSaveProject();

            $project = WizardProject::findOrFail($this->projectId);

            // Log the actual duration that will be used
            Log::info('VideoWizard: Script generation using duration', [
                'component_targetDuration' => $this->targetDuration,
                'project_target_duration' => $project->target_duration,
            ]);
            $scriptService = app(ScriptGenerationService::class);

            $generatedScript = $scriptService->generateScript($project, [
                'teamId' => session('current_team_id', 0),
                'tone' => $this->scriptTone,
                'contentDepth' => $this->contentDepth,
                'additionalInstructions' => $this->additionalInstructions,
                // Narrative Structure Intelligence
                'narrativePreset' => $this->narrativePreset,
                'storyArc' => $this->storyArc,
                'tensionCurve' => $this->tensionCurve,
                'emotionalJourney' => $this->emotionalJourney,
            ]);

            $durationMs = (int)((microtime(true) - $startTime) * 1000);

            // Log success to admin panel
            try {
                VwGenerationLog::logSuccess(
                    $promptSlug,
                    $inputData,
                    ['scenes_count' => count($generatedScript['scenes'] ?? [])],
                    null,
                    $durationMs,
                    $this->projectId,
                    auth()->id(),
                    session('current_team_id')
                );
            } catch (\Exception $logEx) {
                Log::warning('VideoWizard: Failed to log generation success', ['error' => $logEx->getMessage()]);
            }

            // Update script data
            $this->script = array_merge($this->script, $generatedScript);

            // Sanitize generated script data to prevent type errors in views
            $this->sanitizeScriptData();

            // Auto-detect Character Intelligence from generated script
            $this->autoDetectCharacterIntelligence();

            // Recalculate voice status based on new script
            $this->recalculateVoiceStatus();

            $this->saveProject();

            // Dispatch success debug event
            $this->dispatch('vw-debug', [
                'action' => 'generate-script-success',
                'message' => 'Script generated successfully',
                'data' => [
                    'duration_ms' => $durationMs,
                    'scenes_count' => count($this->script['scenes'] ?? []),
                ]
            ]);

            $this->dispatch('script-generated');

            Log::info('VideoWizard: Script generation completed', [
                'project_id' => $this->projectId,
                'duration_ms' => $durationMs,
                'scenes_count' => count($this->script['scenes'] ?? []),
            ]);

        } catch (\Exception $e) {
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            // Log failure to admin panel
            try {
                VwGenerationLog::logFailure(
                    $promptSlug,
                    $inputData,
                    $errorMessage,
                    $durationMs,
                    $this->projectId,
                    auth()->id(),
                    session('current_team_id')
                );
            } catch (\Exception $logEx) {
                Log::warning('VideoWizard: Failed to log generation failure', ['error' => $logEx->getMessage()]);
            }

            // Dispatch error debug event
            $this->dispatch('vw-debug', [
                'action' => 'generate-script-error',
                'message' => 'Script generation failed: ' . $errorMessage,
                'level' => 'error',
                'data' => [
                    'error' => $errorMessage,
                    'duration_ms' => $durationMs,
                    'trace' => $e->getTraceAsString(),
                ]
            ]);

            Log::error('VideoWizard: Script generation failed', [
                'project_id' => $this->projectId,
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error = __('Failed to generate script: ') . $errorMessage;
        } finally {
            $this->isLoading = false;
        }
    }

    // =========================================================================
    // PROGRESSIVE SCRIPT GENERATION (Batch-Based)
    // =========================================================================

    /**
     * Calculate exact scene count based on duration and pacing.
     *
     * HOLLYWOOD-STYLE ARCHITECTURE:
     * - Scenes are 25-45 seconds each (content segments with narration)
     * - Each scene will be decomposed into multiple SHOTS (5-10s clips)
     * - Pacing controls scene duration, not shot duration
     *
     * For a 2:30 (150s) video with balanced pacing:
     * - 150 / 35 = ~4-5 scenes
     * - Each scene has 3-4 shots
     * - Total: ~12-20 video clips
     */
    public function calculateSceneCount(): int
    {
        $targetDuration = $this->targetDuration ?? 60;
        $pacing = $this->content['pacing'] ?? 'balanced';

        // Hollywood-style scene durations based on pacing
        // These are SCENE durations (content segments), NOT shot/clip durations
        $sceneDurations = [
            'fast' => 25,           // Fast-paced: ~25s per scene (more scenes, quicker transitions)
            'balanced' => 35,       // Balanced: ~35s per scene (standard Hollywood pacing)
            'contemplative' => 45,  // Contemplative: ~45s per scene (fewer scenes, more breathing room)
        ];

        $sceneDuration = $sceneDurations[$pacing] ?? 35;

        // Update script timing for reference
        $this->script['timing']['sceneDuration'] = $sceneDuration;
        $this->script['timing']['pacing'] = $pacing;

        // Minimum 2 scenes, maximum based on duration
        $sceneCount = (int) ceil($targetDuration / $sceneDuration);

        return max(2, $sceneCount);
    }

    /**
     * Calculate estimated shot count for display.
     * Each scene is decomposed into multiple shots.
     */
    public function calculateEstimatedShotCount(): int
    {
        $sceneCount = $this->calculateSceneCount();
        $clipDuration = $this->getClipDuration();
        $sceneDuration = $this->script['timing']['sceneDuration'] ?? 35;

        // Hollywood Math: shots per scene = sceneDuration / clipDuration
        $shotsPerScene = (int) ceil($sceneDuration / $clipDuration);

        return $sceneCount * $shotsPerScene;
    }

    /**
     * Get clip/shot duration based on video model settings.
     */
    public function getClipDuration(): int
    {
        $durationStr = $this->content['videoModel']['duration'] ?? '10s';

        return match($durationStr) {
            '5s' => 5,
            '6s' => 6,
            '10s' => 10,
            default => 10,
        };
    }

    /**
     * Set content pacing.
     */
    public function setPacing(string $pacing): void
    {
        $validPacings = ['fast', 'balanced', 'contemplative'];
        if (in_array($pacing, $validPacings)) {
            $this->content['pacing'] = $pacing;
            $this->script['timing']['pacing'] = $pacing;

            // Auto-adjust clip duration based on pacing
            if ($pacing === 'fast') {
                $this->content['videoModel']['duration'] = '6s';
            } else {
                $this->content['videoModel']['duration'] = '10s';
            }

            $this->saveProject();
        }
    }

    /**
     * Set video model clip duration.
     */
    public function setClipDuration(string $duration): void
    {
        $validDurations = ['5s', '6s', '10s'];
        if (in_array($duration, $validDurations)) {
            $this->content['videoModel']['duration'] = $duration;
            $this->script['timing']['clipDuration'] = $this->getClipDuration();
            $this->saveProject();
        }
    }

    /**
     * Start progressive script generation.
     * If scenes exist, shows confirmation modal first.
     */
    #[On('start-progressive-generation')]
    public function startProgressiveGeneration(): void
    {
        if (empty($this->concept['rawInput']) && empty($this->concept['refinedConcept'])) {
            $this->error = __('Please complete the concept step first.');
            return;
        }

        // Check if scenes already exist - show confirmation modal
        $existingSceneCount = count($this->script['scenes'] ?? []);
        if ($existingSceneCount > 0) {
            $this->showSceneOverwriteModal = true;
            return;
        }

        // No existing scenes - proceed directly
        $this->executeProgressiveGeneration('replace');
    }

    /**
     * Handle scene overwrite confirmation.
     */
    public function confirmSceneOverwrite(string $action): void
    {
        $this->showSceneOverwriteModal = false;
        $this->sceneOverwriteAction = $action;

        if ($action === 'cancel') {
            return;
        }

        $this->executeProgressiveGeneration($action);
    }

    /**
     * Execute the progressive generation with specified action.
     * @param string $action 'replace' to start fresh, 'append' to add to existing scenes
     */
    protected function executeProgressiveGeneration(string $action): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            $targetSceneCount = $this->calculateSceneCount();
            $batchSize = 5;

            // If appending, adjust target count
            $existingSceneCount = 0;
            if ($action === 'append') {
                $existingSceneCount = count($this->script['scenes'] ?? []);
                // Calculate how many more scenes we need
                $remainingScenes = max(0, $targetSceneCount - $existingSceneCount);
                if ($remainingScenes === 0) {
                    $this->error = __('You already have :count scenes. Target is :target scenes.', [
                        'count' => $existingSceneCount,
                        'target' => $targetSceneCount,
                    ]);
                    $this->isLoading = false;
                    return;
                }
                $targetSceneCount = $remainingScenes;
            }

            $totalBatches = (int) ceil($targetSceneCount / $batchSize);

            // Initialize batch tracking
            $batches = [];
            for ($i = 0; $i < $totalBatches; $i++) {
                $startScene = $existingSceneCount + ($i * $batchSize) + 1;
                $endScene = $existingSceneCount + min(($i + 1) * $batchSize, $targetSceneCount);

                $batches[] = [
                    'batchNumber' => $i + 1,
                    'startScene' => $startScene,
                    'endScene' => $endScene,
                    'status' => 'pending',
                    'generatedAt' => null,
                    'sceneIds' => [],
                    'retryCount' => 0,
                    'lastError' => null,
                ];
            }

            $this->scriptGeneration = [
                'status' => 'generating',
                'targetSceneCount' => $existingSceneCount + $targetSceneCount,
                'generatedSceneCount' => $existingSceneCount,
                'batchSize' => $batchSize,
                'currentBatch' => 0,
                'totalBatches' => $totalBatches,
                'batches' => $batches,
                'autoGenerate' => false,
            ];

            // Initialize or keep script structure based on action
            if ($action === 'replace') {
                $this->script = [
                    'title' => $this->concept['refinedConcept'] ?? $this->concept['rawInput'] ?? 'Untitled',
                    'hook' => '',
                    'scenes' => [],
                    'cta' => '',
                    'totalDuration' => 0,
                    'totalNarrationTime' => 0,
                ];
            }
            // If appending, keep existing script structure

            $this->saveProject();

            // Dispatch event for UI update
            $this->dispatch('progressive-generation-started', [
                'targetSceneCount' => $this->scriptGeneration['targetSceneCount'],
                'totalBatches' => $totalBatches,
                'action' => $action,
            ]);

            // Generate first batch
            $this->generateNextBatch();

        } catch (\Exception $e) {
            $this->error = __('Failed to start generation: ') . $e->getMessage();
            $this->scriptGeneration['status'] = 'idle';
            Log::error('VideoWizard: Progressive generation start failed', [
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Generate the next batch of scenes.
     */
    #[On('generate-next-batch')]
    public function generateNextBatch(): void
    {
        $currentBatchIndex = $this->scriptGeneration['currentBatch'];

        if ($currentBatchIndex >= $this->scriptGeneration['totalBatches']) {
            $this->scriptGeneration['status'] = 'complete';
            $this->dispatch('progressive-generation-complete');
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        // Update batch status
        $this->scriptGeneration['batches'][$currentBatchIndex]['status'] = 'generating';
        $this->scriptGeneration['status'] = 'generating';

        try {
            $batch = $this->scriptGeneration['batches'][$currentBatchIndex];
            $scriptService = app(ScriptGenerationService::class);
            $project = WizardProject::findOrFail($this->projectId);

            // Build context from existing scenes
            $context = $scriptService->buildBatchContext(
                $this->script['scenes'] ?? [],
                $batch['batchNumber'],
                $this->scriptGeneration['totalBatches']
            );

            // Generate this batch
            $result = $scriptService->generateSceneBatch(
                $project,
                $batch['startScene'],
                $batch['endScene'],
                $this->scriptGeneration['targetSceneCount'],
                $context,
                [
                    'topic' => $this->concept['refinedConcept'] ?? $this->concept['rawInput'] ?? '',
                    'tone' => $this->scriptTone ?? 'engaging',
                    'contentDepth' => $this->contentDepth ?? 'detailed',
                    'productionType' => $this->production['type'] ?? 'standard',
                    'teamId' => session('current_team_id', 0),
                    'narrativePreset' => $this->narrativePreset,
                    'emotionalJourney' => $this->emotionalJourney,
                ]
            );

            if ($result['success'] && !empty($result['scenes'])) {
                // Sanitize and append new scenes
                foreach ($result['scenes'] as $index => $scene) {
                    $sceneIndex = count($this->script['scenes']);
                    $this->script['scenes'][] = $this->sanitizeScene($scene, $sceneIndex);
                }

                // Update batch status
                $this->scriptGeneration['batches'][$currentBatchIndex]['status'] = 'complete';
                $this->scriptGeneration['batches'][$currentBatchIndex]['generatedAt'] = now()->toDateTimeString();
                $this->scriptGeneration['batches'][$currentBatchIndex]['sceneIds'] = array_column($result['scenes'], 'id');

                // Update counts
                $this->scriptGeneration['generatedSceneCount'] = count($this->script['scenes']);
                $this->scriptGeneration['currentBatch']++;

                // Recalculate totals
                $this->recalculateScriptTotals();
                $this->recalculateVoiceStatus();

                // Check if complete
                if ($this->scriptGeneration['currentBatch'] >= $this->scriptGeneration['totalBatches']) {
                    $this->scriptGeneration['status'] = 'complete';
                    $this->autoDetectCharacterIntelligence();
                    $this->dispatch('progressive-generation-complete');
                    $this->dispatch('script-generated');
                } else {
                    $this->scriptGeneration['status'] = 'paused';

                    // Dispatch batch complete event
                    $this->dispatch('batch-generated', [
                        'batchNumber' => $batch['batchNumber'],
                        'scenesGenerated' => count($result['scenes']),
                        'totalGenerated' => $this->scriptGeneration['generatedSceneCount'],
                    ]);

                    // Auto-continue if enabled
                    if ($this->scriptGeneration['autoGenerate']) {
                        $this->generateNextBatch();
                        return;
                    }
                }

                $this->saveProject();

            } else {
                $errorMessage = $result['error'] ?? __('Failed to generate batch');
                $this->handleBatchError($currentBatchIndex, $errorMessage);
            }

        } catch (\Exception $e) {
            $this->handleBatchError($currentBatchIndex, $e->getMessage());
        } finally {
            $this->isLoading = false;
            $this->saveProject();
        }
    }

    /**
     * Handle batch generation error with exponential backoff retry.
     */
    protected function handleBatchError(int $batchIndex, string $errorMessage): void
    {
        $batch = &$this->scriptGeneration['batches'][$batchIndex];
        $batch['retryCount'] = ($batch['retryCount'] ?? 0) + 1;
        $batch['lastError'] = $errorMessage;
        $maxRetries = $this->scriptGeneration['maxRetries'] ?? 3;

        Log::warning('VideoWizard: Batch generation failed', [
            'batch' => $batch['batchNumber'],
            'retryCount' => $batch['retryCount'],
            'maxRetries' => $maxRetries,
            'error' => $errorMessage,
        ]);

        if ($batch['retryCount'] < $maxRetries) {
            // Calculate exponential backoff delay: 1s, 2s, 4s
            $delayMs = ($this->scriptGeneration['retryDelayMs'] ?? 1000) * pow(2, $batch['retryCount'] - 1);

            $batch['status'] = 'retrying';
            $this->error = __('Batch :num failed, retrying in :sec seconds... (Attempt :attempt/:max)', [
                'num' => $batch['batchNumber'],
                'sec' => $delayMs / 1000,
                'attempt' => $batch['retryCount'] + 1,
                'max' => $maxRetries,
            ]);

            // Dispatch delayed retry event
            $this->dispatch('retry-batch-delayed', [
                'batchIndex' => $batchIndex,
                'delayMs' => $delayMs,
            ]);

        } else {
            // Max retries exceeded - mark as failed
            $batch['status'] = 'error';
            $this->scriptGeneration['status'] = 'paused';
            $this->error = __('Batch :num failed after :max attempts: :error', [
                'num' => $batch['batchNumber'],
                'max' => $maxRetries,
                'error' => $errorMessage,
            ]);

            Log::error('VideoWizard: Batch generation failed permanently', [
                'batch' => $batch['batchNumber'],
                'error' => $errorMessage,
            ]);
        }
    }

    /**
     * Execute delayed retry for a batch (called from JS after delay).
     */
    #[On('execute-delayed-retry')]
    public function executeDelayedRetry(int $batchIndex): void
    {
        if (!isset($this->scriptGeneration['batches'][$batchIndex])) {
            return;
        }

        $batch = $this->scriptGeneration['batches'][$batchIndex];
        if ($batch['status'] !== 'retrying') {
            return;
        }

        // Reset to pending and retry
        $this->scriptGeneration['batches'][$batchIndex]['status'] = 'pending';
        $this->scriptGeneration['currentBatch'] = $batchIndex;
        $this->error = null;

        $this->generateNextBatch();
    }

    /**
     * Auto-generate all remaining batches.
     */
    #[On('generate-all-remaining')]
    public function generateAllRemaining(): void
    {
        $this->scriptGeneration['autoGenerate'] = true;
        $this->generateNextBatch();
    }

    /**
     * Retry failed batch.
     */
    public function retryBatch(int $batchIndex): void
    {
        if (!isset($this->scriptGeneration['batches'][$batchIndex])) {
            return;
        }

        // Reset batch status
        $this->scriptGeneration['batches'][$batchIndex]['status'] = 'pending';
        $this->scriptGeneration['currentBatch'] = $batchIndex;
        $this->scriptGeneration['status'] = 'paused';

        // Generate the batch
        $this->generateNextBatch();
    }

    /**
     * Reset progressive generation.
     */
    public function resetProgressiveGeneration(): void
    {
        $this->scriptGeneration = [
            'status' => 'idle',
            'targetSceneCount' => 0,
            'generatedSceneCount' => 0,
            'batchSize' => 5,
            'currentBatch' => 0,
            'totalBatches' => 0,
            'batches' => [],
            'autoGenerate' => false,
        ];

        $this->script = [
            'title' => '',
            'hook' => '',
            'scenes' => [],
            'cta' => '',
            'totalDuration' => 0,
            'totalNarrationTime' => 0,
        ];

        $this->saveProject();
    }

    /**
     * Apply narrative preset defaults.
     * When a preset is selected, auto-set story structure, tension curve, and emotional journey.
     */
    public function applyNarrativePreset(string $preset): void
    {
        $this->narrativePreset = $preset;

        $presets = config('appvideowizard.narrative_presets', []);

        if (!isset($presets[$preset])) {
            return;
        }

        $presetConfig = $presets[$preset];

        // Auto-set story arc/structure if preset defines one
        // Support both 'defaultStructure' (new) and 'defaultArc' (legacy)
        if (!empty($presetConfig['defaultStructure'])) {
            $this->storyArc = $presetConfig['defaultStructure'];
        } elseif (!empty($presetConfig['defaultArc'])) {
            $this->storyArc = $presetConfig['defaultArc'];
        }

        // Auto-set tension curve if preset defines one
        if (!empty($presetConfig['defaultTension'])) {
            $this->tensionCurve = $presetConfig['defaultTension'];
        }

        // Auto-set emotional journey if preset defines one
        if (!empty($presetConfig['defaultEmotion'])) {
            $this->emotionalJourney = $presetConfig['defaultEmotion'];
        }

        // Show advanced options when preset is selected
        $this->showNarrativeAdvanced = true;
    }

    /**
     * Clear narrative structure selections.
     */
    public function clearNarrativeSettings(): void
    {
        $this->narrativePreset = null;
        $this->storyArc = null;
        $this->tensionCurve = null;
        $this->emotionalJourney = null;
        $this->showNarrativeAdvanced = false;
    }

    /**
     * Update script.
     */
    #[On('script-updated')]
    public function updateScript(array $scriptData): void
    {
        $this->script = array_merge($this->script, $scriptData);
        $this->saveProject();
    }

    /**
     * Toggle "Music only" (no voiceover) for a scene.
     */
    public function toggleSceneMusicOnly(int $sceneIndex): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        // Toggle in script array
        $currentValue = $this->script['scenes'][$sceneIndex]['voiceover']['enabled'] ?? true;
        $newValue = !$currentValue;
        $this->script['scenes'][$sceneIndex]['voiceover']['enabled'] = $newValue;

        // Also sync to animation array for UI state
        // musicOnly = true when voiceover.enabled = false
        if (!isset($this->animation['scenes'][$sceneIndex])) {
            $this->animation['scenes'][$sceneIndex] = [];
        }
        $this->animation['scenes'][$sceneIndex]['musicOnly'] = !$newValue;

        // If enabling music only, clear any existing voiceover
        if ($this->animation['scenes'][$sceneIndex]['musicOnly']) {
            unset($this->animation['scenes'][$sceneIndex]['voiceoverUrl']);
            unset($this->animation['scenes'][$sceneIndex]['assetId']);
        }

        $this->recalculateVoiceStatus();
        $this->saveProject();
    }

    /**
     * Update scene duration.
     */
    public function updateSceneDuration(int $sceneIndex, int $duration): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $this->script['scenes'][$sceneIndex]['duration'] = max(1, min(300, $duration));
        $this->recalculateScriptTotals();
        $this->saveProject();
    }

    /**
     * Update scene transition.
     */
    public function updateSceneTransition(int $sceneIndex, string $transition): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $validTransitions = array_keys(config('appvideowizard.transitions', []));
        if (!in_array($transition, $validTransitions)) {
            $transition = 'cut';
        }

        $this->script['scenes'][$sceneIndex]['transition'] = $transition;
        $this->saveProject();
    }

    /**
     * Update scene visual prompt.
     */
    public function updateSceneVisualPrompt(int $sceneIndex, string $prompt): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $this->script['scenes'][$sceneIndex]['visualPrompt'] = $prompt;
        $this->saveProject();
    }

    /**
     * Update scene narration text.
     */
    public function updateSceneNarration(int $sceneIndex, string $narration): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $this->script['scenes'][$sceneIndex]['narration'] = $narration;
        $this->recalculateScriptTotals();
        $this->saveProject();
    }

    /**
     * Update scene voiceover text.
     */
    public function updateSceneVoiceover(int $sceneIndex, string $text): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        if (!isset($this->script['scenes'][$sceneIndex]['voiceover'])) {
            $this->script['scenes'][$sceneIndex]['voiceover'] = [
                'enabled' => true,
                'text' => '',
                'voiceId' => null,
                'status' => 'pending',
            ];
        }

        $this->script['scenes'][$sceneIndex]['voiceover']['text'] = $text;
        $this->recalculateVoiceStatus();
        $this->saveProject();
    }

    /**
     * Regenerate a single scene.
     */
    public function regenerateScene(int $sceneIndex): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        try {
            $project = WizardProject::findOrFail($this->projectId);
            $scriptService = app(ScriptGenerationService::class);

            $regeneratedScene = $scriptService->regenerateScene($project, $sceneIndex, [
                'teamId' => session('current_team_id', 0),
                'tone' => $this->scriptTone,
                'contentDepth' => $this->contentDepth,
                'existingScene' => $this->script['scenes'][$sceneIndex],
            ]);

            if ($regeneratedScene) {
                // Preserve certain fields from the original scene
                $regeneratedScene['id'] = $this->script['scenes'][$sceneIndex]['id'];
                $regeneratedScene['transition'] = $this->script['scenes'][$sceneIndex]['transition'] ?? 'cut';

                // Sanitize the regenerated scene to ensure proper data types
                $this->script['scenes'][$sceneIndex] = $this->sanitizeScene($regeneratedScene, $sceneIndex);
                $this->recalculateScriptTotals();
                $this->recalculateVoiceStatus();
                $this->saveProject();

                $this->dispatch('scene-regenerated', ['sceneIndex' => $sceneIndex]);
            }

        } catch (\Exception $e) {
            $this->error = __('Failed to regenerate scene: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Reorder a scene (move up or down).
     */
    public function reorderScene(int $sceneIndex, string $direction): void
    {
        $scenes = $this->script['scenes'] ?? [];
        $sceneCount = count($scenes);

        if ($sceneIndex < 0 || $sceneIndex >= $sceneCount) {
            return;
        }

        $newIndex = $direction === 'up' ? $sceneIndex - 1 : $sceneIndex + 1;

        if ($newIndex < 0 || $newIndex >= $sceneCount) {
            return;
        }

        // Swap scenes
        $temp = $scenes[$sceneIndex];
        $scenes[$sceneIndex] = $scenes[$newIndex];
        $scenes[$newIndex] = $temp;

        // Reindex array
        $this->script['scenes'] = array_values($scenes);
        $this->saveProject();

        $this->dispatch('scenes-reordered');
    }

    /**
     * Delete a scene.
     */
    public function deleteScene(int $sceneIndex): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        // Don't allow deleting the last scene
        if (count($this->script['scenes']) <= 1) {
            $this->error = __('Cannot delete the last scene.');
            return;
        }

        // Remove the scene
        array_splice($this->script['scenes'], $sceneIndex, 1);

        // Also remove corresponding storyboard scene if exists
        if (isset($this->storyboard['scenes'][$sceneIndex])) {
            array_splice($this->storyboard['scenes'], $sceneIndex, 1);
        }

        $this->recalculateScriptTotals();
        $this->recalculateVoiceStatus();
        $this->saveProject();

        $this->dispatch('scene-deleted', ['sceneIndex' => $sceneIndex]);
    }

    /**
     * Add a new scene.
     */
    public function addScene(): void
    {
        $sceneCount = count($this->script['scenes'] ?? []);
        $newSceneId = 'scene_' . ($sceneCount + 1) . '_' . time();

        // Create new scene with sanitized structure
        $newScene = $this->sanitizeScene([
            'id' => $newSceneId,
            'title' => __('Scene') . ' ' . ($sceneCount + 1),
            'narration' => '',
            'visualDescription' => '',
            'visualPrompt' => '',
            'duration' => 15,
            'transition' => 'cut',
            'mood' => 'neutral',
            'status' => 'draft',
        ], $sceneCount);

        $this->script['scenes'][] = $newScene;
        $this->recalculateScriptTotals();
        $this->recalculateVoiceStatus();
        $this->saveProject();

        $this->dispatch('scene-added', ['sceneIndex' => $sceneCount]);
    }

    /**
     * Generate visual prompt for a scene using AI.
     */
    public function generateVisualPrompt(int $sceneIndex): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $scene = $this->script['scenes'][$sceneIndex];
        $narration = $scene['narration'] ?? '';

        if (empty($narration)) {
            $this->error = __('Scene has no narration to generate visual prompt from.');
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        try {
            $scriptService = app(ScriptGenerationService::class);

            $visualPrompt = $scriptService->generateVisualPromptForScene(
                $narration,
                $this->concept,
                [
                    'mood' => $scene['mood'] ?? $this->concept['suggestedMood'] ?? 'cinematic',
                    'style' => $this->concept['styleReference'] ?? '',
                    'productionType' => $this->productionType,
                    'aspectRatio' => $this->aspectRatio,
                ]
            );

            $this->script['scenes'][$sceneIndex]['visualPrompt'] = $visualPrompt;
            $this->saveProject();

            $this->dispatch('visual-prompt-generated', ['sceneIndex' => $sceneIndex]);

        } catch (\Exception $e) {
            $this->error = __('Failed to generate visual prompt: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Generate voiceover text for a scene using AI.
     */
    public function generateVoiceoverText(int $sceneIndex): void
    {
        if (!isset($this->script['scenes'][$sceneIndex])) {
            return;
        }

        $scene = $this->script['scenes'][$sceneIndex];
        $narration = $scene['narration'] ?? '';

        if (empty($narration)) {
            // Use narration as voiceover text if no separate voiceover needed
            $this->script['scenes'][$sceneIndex]['voiceover']['text'] = $narration;
            $this->saveProject();
            return;
        }

        $this->isLoading = true;
        $this->error = null;

        try {
            $scriptService = app(ScriptGenerationService::class);

            $voiceoverText = $scriptService->generateVoiceoverForScene(
                $narration,
                $this->concept,
                [
                    'narrationStyle' => $this->characterIntelligence['narrationStyle'] ?? 'voiceover',
                    'tone' => $this->scriptTone,
                ]
            );

            $this->script['scenes'][$sceneIndex]['voiceover']['text'] = $voiceoverText;
            $this->recalculateVoiceStatus();
            $this->saveProject();

            $this->dispatch('voiceover-text-generated', ['sceneIndex' => $sceneIndex]);

        } catch (\Exception $e) {
            $this->error = __('Failed to generate voiceover text: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Recalculate script totals (duration, narration time).
     */
    protected function recalculateScriptTotals(): void
    {
        $totalDuration = 0;
        $totalNarrationTime = 0;

        foreach ($this->script['scenes'] ?? [] as $scene) {
            $totalDuration += $scene['duration'] ?? 15;

            // Estimate narration time based on word count (150 words/minute)
            $narration = $scene['narration'] ?? '';
            $wordCount = str_word_count($narration);
            $totalNarrationTime += ($wordCount / 150) * 60;
        }

        $this->script['totalDuration'] = $totalDuration;
        $this->script['totalNarrationTime'] = round($totalNarrationTime, 1);
    }

    /**
     * Sanitize script data to ensure all fields are properly typed.
     * This prevents htmlspecialchars errors when rendering in Blade views.
     * Should be called after loading script data from database or generating new script.
     */
    protected function sanitizeScriptData(): void
    {
        if (empty($this->script['scenes'])) {
            return;
        }

        // Sanitize top-level script fields
        $this->script['title'] = $this->ensureString($this->script['title'] ?? null, 'Untitled Script');
        $this->script['hook'] = $this->ensureString($this->script['hook'] ?? null, '');
        $this->script['cta'] = $this->ensureString($this->script['cta'] ?? null, '');

        // Sanitize each scene
        foreach ($this->script['scenes'] as $index => &$scene) {
            $scene = $this->sanitizeScene($scene, $index);
        }
    }

    /**
     * Sanitize a single scene to ensure all fields are properly typed.
     * Updated to include Hollywood-style scene architecture fields.
     */
    protected function sanitizeScene(array $scene, int $index = 0): array
    {
        return [
            // Core identifiers
            'id' => $this->ensureString($scene['id'] ?? null, 'scene-' . ($index + 1)),
            'title' => $this->ensureString($scene['title'] ?? null, 'Scene ' . ($index + 1)),

            // Text content - must be strings
            'narration' => $this->ensureString($scene['narration'] ?? null, ''),
            'visualDescription' => $this->ensureString(
                $scene['visualDescription'] ?? $scene['visual_description'] ?? $scene['visual'] ?? null,
                ''
            ),
            'visualPrompt' => $this->ensureString($scene['visualPrompt'] ?? null, ''),
            'visual' => $this->ensureString($scene['visual'] ?? $scene['visualDescription'] ?? null, ''),

            // Metadata - must be strings
            'mood' => $this->ensureString($scene['mood'] ?? null, ''),
            'transition' => $this->ensureString($scene['transition'] ?? null, 'cut'),
            'status' => $this->ensureString($scene['status'] ?? null, 'draft'),

            // Duration - must be numeric (Hollywood scenes are 25-60 seconds)
            'duration' => $this->ensureNumeric($scene['duration'] ?? null, 35, 10, 120),

            // Voiceover structure
            'voiceover' => $this->sanitizeVoiceover($scene['voiceover'] ?? []),

            // Ken Burns effect (preserve if valid, otherwise generate)
            'kenBurns' => is_array($scene['kenBurns'] ?? null) ? $scene['kenBurns'] : [
                'startScale' => 1.0, 'endScale' => 1.15,
                'startX' => 0.5, 'startY' => 0.5, 'endX' => 0.5, 'endY' => 0.5
            ],

            // Image data (preserve as-is if exists)
            'image' => $scene['image'] ?? null,
            'imageUrl' => $this->ensureString($scene['imageUrl'] ?? null, ''),

            // ================================================
            // Hollywood-style scene architecture fields
            // These enable multi-shot decomposition and video generation
            // ================================================

            // Scene type classification
            'sceneType' => $this->ensureString($scene['sceneType'] ?? null, 'narrative'),

            // Action blueprint for shot decomposition
            'sceneAction' => $this->ensureString($scene['sceneAction'] ?? $scene['action'] ?? null, ''),
            'actionBlueprint' => is_array($scene['actionBlueprint'] ?? null) ? $scene['actionBlueprint'] : null,

            // Audio layer for dialogue distribution to shots
            'audioLayer' => is_array($scene['audioLayer'] ?? null) ? $scene['audioLayer'] : [
                'hasDialogue' => false,
                'dialogueLines' => [],
                'speakers' => [],
            ],

            // Characters in this scene
            'charactersInScene' => is_array($scene['charactersInScene'] ?? null) ? $scene['charactersInScene'] : [],

            // Location reference
            'locationRef' => is_array($scene['locationRef'] ?? null) ? $scene['locationRef'] : null,

            // Camera/direction hints
            'cameraHints' => is_array($scene['cameraHints'] ?? null) ? $scene['cameraHints'] : [],
        ];
    }

    /**
     * Sanitize voiceover structure.
     */
    protected function sanitizeVoiceover($voiceover): array
    {
        if (!is_array($voiceover)) {
            $voiceover = [];
        }

        return [
            'enabled' => (bool)($voiceover['enabled'] ?? true),
            'text' => $this->ensureString($voiceover['text'] ?? null, ''),
            'voiceId' => $voiceover['voiceId'] ?? null,
            'status' => $this->ensureString($voiceover['status'] ?? null, 'pending'),
        ];
    }

    /**
     * Ensure a value is a string. If it's an array, recursively extract first string.
     * Handles nested arrays like [['value']] that AI sometimes returns.
     */
    protected function ensureString($value, string $default = ''): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (string)$value;
        }
        // Handle arrays - recursively extract first string value
        if (is_array($value)) {
            foreach ($value as $item) {
                $result = $this->ensureString($item, '');
                if ($result !== '') {
                    return $result;
                }
            }
        }
        return $default;
    }

    /**
     * Ensure a value is numeric within bounds.
     */
    protected function ensureNumeric($value, int $default, int $min = 0, int $max = PHP_INT_MAX): int
    {
        if (is_numeric($value)) {
            return max($min, min($max, (int)$value));
        }
        return $default;
    }

    /**
     * Recalculate voice status from script scenes.
     */
    protected function recalculateVoiceStatus(): void
    {
        $dialogueLines = 0;
        $speakers = [];
        $voicesMapped = 0;
        $scenesWithDialogue = 0;
        $scenesWithVoiceover = 0;
        $pendingVoices = 0;

        foreach ($this->script['scenes'] ?? [] as $scene) {
            $voiceover = $scene['voiceover'] ?? [];

            if ($voiceover['enabled'] ?? true) {
                $scenesWithVoiceover++;

                if (!empty($voiceover['text'])) {
                    $dialogueLines++;

                    if (!empty($voiceover['voiceId'])) {
                        $voicesMapped++;
                    } else {
                        $pendingVoices++;
                    }
                } else {
                    $pendingVoices++;
                }
            }

            // Count speakers from dialogue (if narrationStyle is dialogue)
            if ($this->characterIntelligence['narrationStyle'] === 'dialogue') {
                // Extract speaker names from narration (format: "SPEAKER: text")
                $narration = $scene['narration'] ?? '';
                if (preg_match_all('/^([A-Z][A-Z\s]+):/m', $narration, $matches)) {
                    foreach ($matches[1] as $speaker) {
                        $speakers[trim($speaker)] = true;
                        $dialogueLines++;
                    }
                    $scenesWithDialogue++;
                }
            }
        }

        $this->voiceStatus = [
            'dialogueLines' => $dialogueLines,
            'speakers' => count($speakers),
            'voicesMapped' => $voicesMapped,
            'scenesWithDialogue' => $scenesWithDialogue,
            'scenesWithVoiceover' => $scenesWithVoiceover,
            'pendingVoices' => $pendingVoices,
        ];
    }

    /**
     * Auto-detect Character Intelligence from generated script.
     * Analyzes the script to automatically set narration style, character count, etc.
     */
    protected function autoDetectCharacterIntelligence(): void
    {
        try {
            $characterService = app(CharacterExtractionService::class);

            $detection = $characterService->autoDetectCharacterIntelligence(
                $this->script,
                ['productionType' => $this->productionSubtype ?? $this->productionType]
            );

            // Update Character Intelligence with detected values
            $this->characterIntelligence['narrationStyle'] = $detection['narrationStyle'];
            $this->characterIntelligence['characterCount'] = $detection['characterCount'];
            $this->characterIntelligence['suggestedCount'] = $detection['suggestedCount'];

            // Store detection metadata for UI display
            $this->characterIntelligence['autoDetected'] = true;
            $this->characterIntelligence['detectionConfidence'] = $detection['detectionConfidence'];
            $this->characterIntelligence['hasDialogue'] = $detection['hasDialogue'];
            $this->characterIntelligence['detectedSpeakers'] = $detection['detectedSpeakers'] ?? [];

            Log::info('VideoWizard: Character Intelligence auto-detected', [
                'project_id' => $this->projectId,
                'narrationStyle' => $detection['narrationStyle'],
                'characterCount' => $detection['characterCount'],
                'confidence' => $detection['detectionConfidence'],
            ]);

            // Dispatch event for UI notification
            $this->dispatch('vw-debug', [
                'action' => 'character-intelligence-detected',
                'message' => "Auto-detected: {$detection['narrationStyle']} style ({$detection['detectionConfidence']} confidence)",
                'data' => $detection,
            ]);

        } catch (\Exception $e) {
            Log::warning('VideoWizard: Character Intelligence auto-detection failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update Character Intelligence settings.
     */
    public function updateCharacterIntelligence(string $field, $value): void
    {
        if (in_array($field, ['enabled', 'narrationStyle', 'characterCount'])) {
            // Cast values to proper types
            if ($field === 'enabled') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif ($field === 'characterCount') {
                $value = (int) $value;
            }

            $this->characterIntelligence[$field] = $value;

            // Recalculate suggested character count based on production type
            if ($field === 'narrationStyle' || $field === 'enabled') {
                $this->characterIntelligence['suggestedCount'] = $this->calculateSuggestedCharacterCount();
            }

            $this->saveProject();
        }
    }

    /**
     * Calculate suggested character count based on production type and narration style.
     */
    protected function calculateSuggestedCharacterCount(): int
    {
        $narrationStyle = $this->characterIntelligence['narrationStyle'] ?? 'voiceover';

        // No characters needed for voiceover or none
        if (in_array($narrationStyle, ['voiceover', 'narrator', 'none'])) {
            return 0;
        }

        // For dialogue, suggest based on production type
        $productionType = $this->productionType ?? 'social';

        return match ($productionType) {
            'movie' => 4,
            'series' => 5,
            'commercial' => 2,
            'educational' => 1,
            default => 2,
        };
    }

    /**
     * Update storyboard.
     */
    #[On('storyboard-updated')]
    public function updateStoryboard(array $storyboardData): void
    {
        $this->storyboard = array_merge($this->storyboard, $storyboardData);
        $this->saveProject();
    }

    /**
     * Generate image for a single scene.
     */
    #[On('generate-image')]
    public function generateImage(int $sceneIndex, string $sceneId): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            if (!$this->projectId) {
                $this->saveProject();
            }

            $project = WizardProject::findOrFail($this->projectId);
            $scene = $this->script['scenes'][$sceneIndex] ?? null;

            if (!$scene) {
                throw new \Exception(__('Scene not found'));
            }

            // Initialize scenes array if needed
            if (!isset($this->storyboard['scenes'])) {
                $this->storyboard['scenes'] = [];
            }

            // Set generating status BEFORE the API call for immediate UI feedback
            $this->storyboard['scenes'][$sceneIndex] = [
                'sceneId' => $sceneId,
                'imageUrl' => null,
                'assetId' => null,
                'status' => 'generating',
                'source' => 'ai',
            ];

            // Force save and UI update before the potentially slow API call
            $this->saveProject();

            $imageService = app(ImageGenerationService::class);
            $result = $imageService->generateSceneImage($project, $scene, [
                'sceneIndex' => $sceneIndex,
                'teamId' => session('current_team_id', 0),
                'model' => $this->storyboard['imageModel'] ?? 'nanobanana', // Use UI-selected model
            ]);

            if ($result['async'] ?? false) {
                // HiDream async job - update with job ID for polling
                $this->storyboard['scenes'][$sceneIndex]['jobId'] = $result['jobId'] ?? null;
                $this->storyboard['scenes'][$sceneIndex]['processingJobId'] = $result['processingJobId'] ?? null;

                $this->saveProject();

                // Dispatch event to start polling
                $this->dispatch('image-generation-started', [
                    'sceneIndex' => $sceneIndex,
                    'async' => true,
                ]);
            } else {
                // Sync generation - image is ready
                $this->storyboard['scenes'][$sceneIndex] = [
                    'sceneId' => $sceneId,
                    'imageUrl' => $result['imageUrl'],
                    'assetId' => $result['assetId'] ?? null,
                    'source' => 'ai',
                    'status' => 'ready',
                ];

                $this->saveProject();
            }

        } catch (\Exception $e) {
            // Set error status on failure
            if (isset($this->storyboard['scenes'][$sceneIndex])) {
                $this->storyboard['scenes'][$sceneIndex]['status'] = 'error';
                $this->storyboard['scenes'][$sceneIndex]['error'] = $e->getMessage();
                $this->saveProject();
            }
            $this->error = __('Failed to generate image: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Generate images for all scenes.
     */
    #[On('generate-all-images')]
    public function generateAllImages(): void
    {
        $this->isLoading = true;
        $this->error = null;
        $hasAsyncJobs = false;

        try {
            if (!$this->projectId) {
                $this->saveProject();
            }

            $project = WizardProject::findOrFail($this->projectId);
            $imageService = app(ImageGenerationService::class);

            if (!isset($this->storyboard['scenes'])) {
                $this->storyboard['scenes'] = [];
            }

            // First pass: Set all pending scenes to 'generating' status for immediate UI feedback
            $scenesToGenerate = [];
            foreach ($this->script['scenes'] as $index => $scene) {
                $existingScene = $this->storyboard['scenes'][$index] ?? null;
                if (empty($existingScene['imageUrl']) && ($existingScene['status'] ?? '') !== 'generating') {
                    $this->storyboard['scenes'][$index] = [
                        'sceneId' => $scene['id'],
                        'imageUrl' => null,
                        'assetId' => null,
                        'status' => 'generating',
                        'source' => 'ai',
                    ];
                    $scenesToGenerate[] = $index;
                }
            }

            // Save with all 'generating' statuses for immediate UI update
            if (!empty($scenesToGenerate)) {
                $this->saveProject();
            }

            // Second pass: Actually generate images
            foreach ($scenesToGenerate as $index) {
                $scene = $this->script['scenes'][$index];

                try {
                    $result = $imageService->generateSceneImage($project, $scene, [
                        'sceneIndex' => $index,
                        'teamId' => session('current_team_id', 0),
                        'model' => $this->storyboard['imageModel'] ?? 'nanobanana',
                    ]);

                    if ($result['async'] ?? false) {
                        // HiDream async job - update with job ID
                        $this->storyboard['scenes'][$index]['jobId'] = $result['jobId'] ?? null;
                        $this->storyboard['scenes'][$index]['processingJobId'] = $result['processingJobId'] ?? null;
                        $hasAsyncJobs = true;
                    } else {
                        // Sync generation - image is ready
                        $this->storyboard['scenes'][$index] = [
                            'sceneId' => $scene['id'],
                            'imageUrl' => $result['imageUrl'],
                            'assetId' => $result['assetId'] ?? null,
                            'source' => 'ai',
                            'status' => 'ready',
                        ];
                    }

                    $this->saveProject();

                } catch (\Exception $e) {
                    // Set error status on failure
                    $this->storyboard['scenes'][$index]['status'] = 'error';
                    $this->storyboard['scenes'][$index]['error'] = $e->getMessage();
                    $this->saveProject();
                    Log::warning("Failed to generate image for scene {$index}: " . $e->getMessage());
                }
            }

            // Dispatch polling start if we have async jobs
            if ($hasAsyncJobs) {
                $this->dispatch('image-generation-started', [
                    'async' => true,
                    'sceneIndex' => -1, // Indicates batch
                ]);
            }

        } catch (\Exception $e) {
            $this->error = __('Failed to generate images: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Regenerate image for a scene.
     */
    #[On('regenerate-image')]
    public function regenerateImage(int $sceneIndex): void
    {
        $scene = $this->script['scenes'][$sceneIndex] ?? null;
        if ($scene) {
            $this->generateImage($sceneIndex, $scene['id']);
        }
    }

    /**
     * Generate AI-powered stock search suggestions based on scene content.
     */
    public function generateStockSuggestions(int $sceneIndex): array
    {
        $scene = $this->script['scenes'][$sceneIndex] ?? null;
        if (!$scene) {
            return ['primaryQuery' => '', 'alternatives' => []];
        }

        $visual = $scene['visual'] ?? '';
        $narration = $scene['narration'] ?? '';
        $combined = trim($visual . ' ' . $narration);

        if (empty($combined)) {
            return ['primaryQuery' => '', 'alternatives' => []];
        }

        // Extract keywords from scene description
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
            'should', 'may', 'might', 'must', 'shall', 'can', 'need', 'to', 'of',
            'in', 'for', 'on', 'with', 'at', 'by', 'this', 'that', 'it', 'and', 'or',
            'but', 'if', 'then', 'else', 'when', 'where', 'why', 'how', 'all', 'each',
            'every', 'both', 'few', 'more', 'most', 'other', 'some', 'such', 'no',
            'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 'just',
            'show', 'showing', 'shows', 'scene', 'shot', 'shots', 'frame', 'frames'];

        // Clean and extract meaningful words
        $words = preg_split('/\s+/', strtolower($combined));
        $keywords = array_filter($words, function($word) use ($stopWords) {
            $word = preg_replace('/[^a-z]/', '', $word);
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });

        $keywords = array_values(array_unique($keywords));
        $primaryWords = array_slice($keywords, 0, 3);
        $primaryQuery = implode(' ', $primaryWords);

        // Generate alternative queries
        $alternatives = [];
        if (count($keywords) > 3) {
            $alternatives[] = implode(' ', array_slice($keywords, 1, 3));
        }
        if (count($keywords) > 4) {
            $alternatives[] = implode(' ', array_slice($keywords, 2, 3));
        }

        // Add context-based alternatives
        $contextKeywords = [
            'office' => ['business office', 'corporate workspace', 'professional meeting'],
            'nature' => ['natural landscape', 'outdoor scenery', 'forest trees'],
            'technology' => ['tech devices', 'digital innovation', 'computer screen'],
            'people' => ['diverse team', 'professional people', 'lifestyle portrait'],
            'city' => ['urban skyline', 'city streets', 'metropolitan view'],
        ];

        foreach ($contextKeywords as $context => $suggestions) {
            if (stripos($combined, $context) !== false) {
                $alternatives = array_merge($alternatives, array_slice($suggestions, 0, 2));
                break;
            }
        }

        return [
            'primaryQuery' => $primaryQuery,
            'alternatives' => array_slice(array_unique($alternatives), 0, 4),
        ];
    }

    /**
     * Search stock media.
     */
    #[On('search-stock-media')]
    public function searchStockMedia(string $query, string $type = 'image', int $sceneIndex = 0): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            $stockService = app(StockMediaService::class);

            // Get orientation based on aspect ratio
            $orientation = $stockService->getOrientation($this->aspectRatio);

            $result = $stockService->searchPexels($query, $type, [
                'orientation' => $orientation,
                'page' => 1,
                'perPage' => 20,
            ]);

            if ($result['success']) {
                $this->dispatch('stock-media-results', [
                    'results' => $result['results'],
                    'total' => $result['total'],
                    'sceneIndex' => $sceneIndex,
                ]);
            } else {
                $this->error = $result['error'] ?? __('Failed to search stock media');
            }

        } catch (\Exception $e) {
            $this->error = __('Failed to search stock media: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Select stock media for a scene.
     */
    #[On('select-stock-media')]
    public function selectStockMedia(int $sceneIndex, string $mediaUrl, string $mediaId, string $type = 'image'): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            if (!$this->projectId) {
                $this->saveProject();
            }

            $project = WizardProject::findOrFail($this->projectId);
            $scene = $this->script['scenes'][$sceneIndex] ?? null;

            if (!$scene) {
                throw new \Exception(__('Scene not found'));
            }

            $stockService = app(StockMediaService::class);

            $result = $stockService->importMedia(
                $project,
                $mediaUrl,
                $mediaId,
                $type,
                $scene['id'],
                ['sceneIndex' => $sceneIndex]
            );

            if ($result['success']) {
                // Update storyboard with the stock media
                if (!isset($this->storyboard['scenes'])) {
                    $this->storyboard['scenes'] = [];
                }

                $this->storyboard['scenes'][$sceneIndex] = [
                    'sceneId' => $scene['id'],
                    'imageUrl' => $result['url'],
                    'assetId' => $result['assetId'],
                    'source' => 'stock',
                    'status' => 'ready',
                ];

                $this->saveProject();

                $this->dispatch('stock-media-selected', [
                    'sceneIndex' => $sceneIndex,
                    'imageUrl' => $result['url'],
                ]);
            } else {
                throw new \Exception($result['error'] ?? 'Import failed');
            }

        } catch (\Exception $e) {
            $this->error = __('Failed to import stock media: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Poll for pending HiDream image generation jobs.
     */
    #[On('poll-image-jobs')]
    public function pollImageJobs(): void
    {
        if (!$this->projectId) {
            return;
        }

        try {
            $project = WizardProject::findOrFail($this->projectId);
            $imageService = app(ImageGenerationService::class);

            // Get pending/processing jobs
            $jobs = \Modules\AppVideoWizard\Models\WizardProcessingJob::query()
                ->where('project_id', $project->id)
                ->where('type', \Modules\AppVideoWizard\Models\WizardProcessingJob::TYPE_IMAGE_GENERATION)
                ->whereIn('status', [
                    \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PENDING,
                    \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PROCESSING,
                ])
                ->get();

            foreach ($jobs as $job) {
                $result = $imageService->pollHiDreamJob($job);

                if ($result['status'] === 'ready' && $result['success']) {
                    // Image is ready - update storyboard
                    $sceneIndex = $result['sceneIndex'] ?? null;
                    if ($sceneIndex !== null) {
                        if (!isset($this->storyboard['scenes'])) {
                            $this->storyboard['scenes'] = [];
                        }
                        $this->storyboard['scenes'][$sceneIndex] = [
                            'sceneId' => $job->input_data['sceneId'] ?? null,
                            'imageUrl' => $result['imageUrl'],
                            'assetId' => $result['assetId'],
                            'source' => 'ai',
                            'status' => 'ready',
                        ];

                        $this->saveProject();

                        $this->dispatch('image-ready', [
                            'sceneIndex' => $sceneIndex,
                            'imageUrl' => $result['imageUrl'],
                        ]);
                    }
                } elseif ($result['status'] === 'error') {
                    $this->dispatch('image-error', [
                        'sceneIndex' => $job->input_data['sceneIndex'] ?? null,
                        'error' => $result['error'],
                    ]);
                }
            }

            // If there are still pending jobs, schedule another poll
            $pendingCount = \Modules\AppVideoWizard\Models\WizardProcessingJob::query()
                ->where('project_id', $project->id)
                ->where('type', \Modules\AppVideoWizard\Models\WizardProcessingJob::TYPE_IMAGE_GENERATION)
                ->whereIn('status', [
                    \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PENDING,
                    \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PROCESSING,
                ])
                ->count();

            $this->dispatch('poll-status', [
                'pendingJobs' => $pendingCount,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to poll image jobs: ' . $e->getMessage());
        }
    }

    /**
     * Get pending jobs count for a project.
     */
    public function getPendingJobsCount(): int
    {
        if (!$this->projectId) {
            return 0;
        }

        return \Modules\AppVideoWizard\Models\WizardProcessingJob::query()
            ->where('project_id', $this->projectId)
            ->where('type', \Modules\AppVideoWizard\Models\WizardProcessingJob::TYPE_IMAGE_GENERATION)
            ->whereIn('status', [
                \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PENDING,
                \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PROCESSING,
            ])
            ->count();
    }

    /**
     * Cancel a stuck image generation job.
     */
    #[On('cancel-image-generation')]
    public function cancelImageGeneration(int $sceneIndex): void
    {
        try {
            // Get the processing job for this scene
            if ($this->projectId) {
                $job = \Modules\AppVideoWizard\Models\WizardProcessingJob::query()
                    ->where('project_id', $this->projectId)
                    ->where('type', \Modules\AppVideoWizard\Models\WizardProcessingJob::TYPE_IMAGE_GENERATION)
                    ->whereIn('status', [
                        \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PENDING,
                        \Modules\AppVideoWizard\Models\WizardProcessingJob::STATUS_PROCESSING,
                    ])
                    ->whereJsonContains('input_data->sceneIndex', $sceneIndex)
                    ->first();

                if ($job) {
                    $job->markAsCancelled();
                    \Log::info("Cancelled stuck job for scene {$sceneIndex}", ['jobId' => $job->id]);
                }
            }

            // Reset the scene status in storyboard
            if (isset($this->storyboard['scenes'][$sceneIndex])) {
                $this->storyboard['scenes'][$sceneIndex]['status'] = null;
                $this->storyboard['scenes'][$sceneIndex]['imageUrl'] = null;
                $this->storyboard['scenes'][$sceneIndex]['jobId'] = null;
                $this->storyboard['scenes'][$sceneIndex]['processingJobId'] = null;
            }

            // Remove from pendingJobs array if present
            if (isset($this->pendingJobs[$sceneIndex])) {
                unset($this->pendingJobs[$sceneIndex]);
            }

            $this->saveProject();

            $this->dispatch('generation-cancelled', [
                'sceneIndex' => $sceneIndex,
            ]);

        } catch (\Exception $e) {
            \Log::error("Failed to cancel generation for scene {$sceneIndex}: " . $e->getMessage());
            $this->error = __('Failed to cancel generation');
        }
    }

    /**
     * Generate voiceover for a single scene.
     */
    #[On('generate-voiceover')]
    public function generateVoiceover(int $sceneIndex, string $sceneId): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            if (!$this->projectId) {
                $this->saveProject();
            }

            $project = WizardProject::findOrFail($this->projectId);
            $scene = $this->script['scenes'][$sceneIndex] ?? null;

            if (!$scene) {
                throw new \Exception(__('Scene not found'));
            }

            $voiceoverService = app(VoiceoverService::class);
            $result = $voiceoverService->generateSceneVoiceover($project, $scene, [
                'sceneIndex' => $sceneIndex,
                'voice' => $this->animation['voiceover']['voice'] ?? 'nova',
                'speed' => $this->animation['voiceover']['speed'] ?? 1.0,
                'teamId' => session('current_team_id', 0),
            ]);

            // Update animation with the generated voiceover
            if (!isset($this->animation['scenes'])) {
                $this->animation['scenes'] = [];
            }
            $this->animation['scenes'][$sceneIndex] = [
                'sceneId' => $sceneId,
                'voiceoverUrl' => $result['audioUrl'],
                'assetId' => $result['assetId'] ?? null,
                'duration' => $result['duration'] ?? null,
            ];

            $this->saveProject();

        } catch (\Exception $e) {
            $this->error = __('Failed to generate voiceover: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Generate voiceovers for all scenes.
     */
    #[On('generate-all-voiceovers')]
    public function generateAllVoiceovers(): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            if (!$this->projectId) {
                $this->saveProject();
            }

            $project = WizardProject::findOrFail($this->projectId);
            $voiceoverService = app(VoiceoverService::class);

            if (!isset($this->animation['scenes'])) {
                $this->animation['scenes'] = [];
            }

            foreach ($this->script['scenes'] as $index => $scene) {
                // Skip if already has a voiceover
                if (!empty($this->animation['scenes'][$index]['voiceoverUrl'])) {
                    continue;
                }

                try {
                    $result = $voiceoverService->generateSceneVoiceover($project, $scene, [
                        'sceneIndex' => $index,
                        'voice' => $this->animation['voiceover']['voice'] ?? 'nova',
                        'speed' => $this->animation['voiceover']['speed'] ?? 1.0,
                        'teamId' => session('current_team_id', 0),
                    ]);

                    $this->animation['scenes'][$index] = [
                        'sceneId' => $scene['id'],
                        'voiceoverUrl' => $result['audioUrl'],
                        'assetId' => $result['assetId'] ?? null,
                        'duration' => $result['duration'] ?? null,
                    ];

                    $this->saveProject();

                } catch (\Exception $e) {
                    \Log::warning("Failed to generate voiceover for scene {$index}: " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            $this->error = __('Failed to generate voiceovers: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Regenerate voiceover for a scene.
     */
    #[On('regenerate-voiceover')]
    public function regenerateVoiceover(int $sceneIndex): void
    {
        $scene = $this->script['scenes'][$sceneIndex] ?? null;
        if ($scene) {
            // Clear existing voiceover first
            if (isset($this->animation['scenes'][$sceneIndex])) {
                unset($this->animation['scenes'][$sceneIndex]['voiceoverUrl']);
            }
            $this->generateVoiceover($sceneIndex, $scene['id']);
        }
    }

    /**
     * Generate AI video animation for a single scene.
     */
    #[On('animate-scene')]
    public function animateScene(int $sceneIndex): void
    {
        $scene = $this->script['scenes'][$sceneIndex] ?? null;
        $sbScene = $this->storyboard['scenes'][$sceneIndex] ?? [];

        if (!$scene) {
            $this->error = __('Scene not found');
            return;
        }

        // Check if scene has an image to animate
        $imageUrl = $sbScene['imageUrl'] ?? null;
        if (!$imageUrl) {
            $this->error = __('Please generate an image first before creating video');
            return;
        }

        // Initialize animation scene data if not exists
        if (!isset($this->animation['scenes'][$sceneIndex])) {
            $this->animation['scenes'][$sceneIndex] = [];
        }

        // Mark as generating
        $this->animation['scenes'][$sceneIndex]['animationStatus'] = 'generating';
        $this->saveProject();

        try {
            // Get video settings
            $videoModel = $this->content['videoModel'] ?? [
                'model' => 'hailuo-2.3',
                'duration' => '10s',
                'resolution' => '768p'
            ];

            // Get camera movements for this scene
            $cameraMovements = $this->animation['scenes'][$sceneIndex]['cameraMovements'] ?? [];

            // TODO: Integrate with actual video generation API (Minimax/Hailuo)
            // For now, dispatch a job or queue the video generation
            // This is a placeholder that will be connected to the actual API

            \Log::info("Animating scene {$sceneIndex}", [
                'imageUrl' => $imageUrl,
                'model' => $videoModel['model'],
                'duration' => $videoModel['duration'],
                'cameraMovements' => $cameraMovements,
            ]);

            // Placeholder: In production, this would call the video generation API
            // and update the status when the video is ready via webhook or polling
            $this->dispatch('notify', [
                'type' => 'info',
                'message' => __('Video generation started for Scene :num. This may take a few minutes.', ['num' => $sceneIndex + 1])
            ]);

        } catch (\Exception $e) {
            $this->animation['scenes'][$sceneIndex]['animationStatus'] = 'error';
            $this->animation['scenes'][$sceneIndex]['animationError'] = $e->getMessage();
            $this->error = __('Failed to start video generation: ') . $e->getMessage();
            \Log::error("Failed to animate scene {$sceneIndex}: " . $e->getMessage());
        }

        $this->saveProject();
    }

    /**
     * Generate AI video animation for all scenes that have images.
     */
    #[On('animate-all-scenes')]
    public function animateAllScenes(): void
    {
        $scenesQueued = 0;
        $scenesSkipped = 0;

        foreach ($this->script['scenes'] as $index => $scene) {
            $sbScene = $this->storyboard['scenes'][$index] ?? [];
            $animScene = $this->animation['scenes'][$index] ?? [];

            // Skip if already has video
            if (!empty($animScene['videoUrl'])) {
                $scenesSkipped++;
                continue;
            }

            // Skip if no image to animate
            $imageUrl = $sbScene['imageUrl'] ?? null;
            if (!$imageUrl) {
                $scenesSkipped++;
                continue;
            }

            // Skip if already generating
            if (($animScene['animationStatus'] ?? '') === 'generating') {
                continue;
            }

            // Queue this scene for animation
            $this->animateScene($index);
            $scenesQueued++;
        }

        if ($scenesQueued > 0) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __(':count scenes queued for video generation', ['count' => $scenesQueued])
            ]);
        } else {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => __('No scenes available for animation. Generate images first.')
            ]);
        }
    }

    /**
     * Remove voiceover from a scene.
     */
    public function removeVoiceover(int $sceneIndex): void
    {
        if (isset($this->animation['scenes'][$sceneIndex])) {
            unset($this->animation['scenes'][$sceneIndex]['voiceoverUrl']);
            unset($this->animation['scenes'][$sceneIndex]['assetId']);
            unset($this->animation['scenes'][$sceneIndex]['duration']);
            $this->saveProject();
        }
    }

    /**
     * Set animation type for a scene (ken_burns, talking_head, static).
     */
    public function setSceneAnimationType(int $sceneIndex, string $type): void
    {
        $validTypes = ['ken_burns', 'talking_head', 'static'];
        if (!in_array($type, $validTypes)) {
            return;
        }

        if (!isset($this->animation['scenes'][$sceneIndex])) {
            $this->animation['scenes'][$sceneIndex] = [];
        }

        $this->animation['scenes'][$sceneIndex]['animationType'] = $type;
        $this->saveProject();
    }

    /**
     * Toggle a camera movement for a scene (max 3 allowed).
     */
    public function toggleCameraMovement(int $sceneIndex, string $movement): void
    {
        $validMovements = [
            'Pan left', 'Pan right', 'Zoom in', 'Zoom out',
            'Push in', 'Pull out', 'Tilt up', 'Tilt down',
            'Tracking shot', 'Static shot'
        ];

        if (!in_array($movement, $validMovements)) {
            return;
        }

        if (!isset($this->animation['scenes'][$sceneIndex])) {
            $this->animation['scenes'][$sceneIndex] = [];
        }

        if (!isset($this->animation['scenes'][$sceneIndex]['cameraMovements'])) {
            $this->animation['scenes'][$sceneIndex]['cameraMovements'] = [];
        }

        $movements = &$this->animation['scenes'][$sceneIndex]['cameraMovements'];

        // If movement already selected, remove it
        $key = array_search($movement, $movements);
        if ($key !== false) {
            array_splice($movements, $key, 1);
        } else {
            // Add if under limit of 3
            if (count($movements) < 3) {
                $movements[] = $movement;
            }
        }

        $this->saveProject();
    }

    /**
     * Select a scene for detailed editing in Animation Studio.
     */
    public function selectSceneForAnimation(int $sceneIndex): void
    {
        $this->animation['selectedSceneIndex'] = $sceneIndex;
    }

    /**
     * Get step titles.
     */
    public function getStepTitles(): array
    {
        return [
            1 => 'Platform & Format',
            2 => 'Concept',
            3 => 'Script',
            4 => 'Storyboard',
            5 => 'Animation',
            6 => 'Assembly',
            7 => 'Export',
        ];
    }

    /**
     * Check if step is completed.
     */
    public function isStepCompleted(int $step): bool
    {
        return match ($step) {
            1 => !empty($this->platform) || !empty($this->format),
            2 => !empty($this->concept['rawInput']) || !empty($this->concept['refinedConcept']),
            3 => !empty($this->script['scenes']),
            4 => $this->hasStoryboardImages(),
            5 => $this->hasAnimationData(),
            6 => true, // Assembly is optional
            7 => false, // Export is never "completed" in this sense
            default => false,
        };
    }

    /**
     * Check if storyboard has images.
     */
    protected function hasStoryboardImages(): bool
    {
        foreach ($this->storyboard['scenes'] ?? [] as $scene) {
            if (!empty($scene['imageUrl'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if animation data exists.
     */
    protected function hasAnimationData(): bool
    {
        foreach ($this->animation['scenes'] ?? [] as $scene) {
            if (!empty($scene['voiceoverUrl']) || !empty($scene['videoUrl'])) {
                return true;
            }
        }
        return false;
    }

    // =========================================================================
    // STOCK MEDIA BROWSER METHODS
    // =========================================================================

    /**
     * Open stock media browser for a scene.
     */
    #[On('open-stock-browser')]
    public function openStockBrowser(int $sceneIndex): void
    {
        $this->stockBrowserSceneIndex = $sceneIndex;
        $this->showStockBrowser = true;
        $this->stockSearchQuery = '';
        $this->stockSearchResults = [];

        // Set default search query based on scene description
        $scene = $this->script['scenes'][$sceneIndex] ?? null;
        if ($scene) {
            // Extract keywords from visual description
            $description = $scene['visualDescription'] ?? $scene['title'] ?? '';
            $this->stockSearchQuery = $this->extractSearchKeywords($description);
        }

        // Set orientation based on aspect ratio
        $this->stockOrientation = match ($this->aspectRatio) {
            '9:16', '4:5' => 'portrait',
            '1:1' => 'square',
            default => 'landscape',
        };
    }

    /**
     * Extract search keywords from text.
     */
    protected function extractSearchKeywords(string $text): string
    {
        // Remove common words and keep meaningful keywords
        $stopWords = ['the', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'is', 'are', 'was', 'were'];
        $words = preg_split('/\s+/', strtolower($text));
        $keywords = array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));
        return implode(' ', array_slice($keywords, 0, 4));
    }

    // =========================================================================
    // EDIT PROMPT/SCENE METHODS
    // =========================================================================

    /**
     * Open edit prompt modal for a scene (full scene editing).
     */
    #[On('open-edit-prompt')]
    public function openEditPrompt(int $sceneIndex): void
    {
        $this->editPromptSceneIndex = $sceneIndex;
        $this->showEditPromptModal = true;

        // Load existing prompt or visual description
        $storyboardScene = $this->storyboard['scenes'][$sceneIndex] ?? null;
        $scriptScene = $this->script['scenes'][$sceneIndex] ?? null;

        $this->editPromptText = $storyboardScene['prompt']
            ?? $scriptScene['visualDescription']
            ?? $scriptScene['narration']
            ?? '';

        // Load scene properties for full scene editing
        $this->editSceneNarration = $scriptScene['narration'] ?? '';
        $this->editSceneDuration = (int) ($scriptScene['duration'] ?? 8);
        $this->editSceneTransition = $scriptScene['transition'] ?? 'cut';
    }

    /**
     * Close edit prompt modal.
     */
    public function closeEditPrompt(): void
    {
        $this->showEditPromptModal = false;
        $this->editPromptText = '';
        $this->editSceneNarration = '';
        $this->editSceneDuration = 8;
        $this->editSceneTransition = 'cut';
    }

    /**
     * Save scene properties only (without regenerating image).
     */
    public function saveSceneProperties(): void
    {
        // Update script scene properties
        if (isset($this->script['scenes'][$this->editPromptSceneIndex])) {
            $this->script['scenes'][$this->editPromptSceneIndex]['narration'] = $this->editSceneNarration;
            $this->script['scenes'][$this->editPromptSceneIndex]['duration'] = $this->editSceneDuration;
            $this->script['scenes'][$this->editPromptSceneIndex]['transition'] = $this->editSceneTransition;

            // Update visual description if provided
            if (!empty($this->editPromptText)) {
                $this->script['scenes'][$this->editPromptSceneIndex]['visualDescription'] = $this->editPromptText;
            }
        }

        // Store the custom prompt in storyboard
        if (!empty($this->editPromptText)) {
            if (!isset($this->storyboard['scenes'])) {
                $this->storyboard['scenes'] = [];
            }
            if (!isset($this->storyboard['scenes'][$this->editPromptSceneIndex])) {
                $this->storyboard['scenes'][$this->editPromptSceneIndex] = [];
            }
            $this->storyboard['scenes'][$this->editPromptSceneIndex]['prompt'] = $this->editPromptText;
        }

        $this->saveProject();
        $this->closeEditPrompt();
    }

    /**
     * Save edited prompt and regenerate image.
     */
    public function saveAndRegeneratePrompt(): void
    {
        if (empty($this->editPromptText)) {
            $this->error = __('Prompt cannot be empty');
            return;
        }

        // First save all scene properties
        if (isset($this->script['scenes'][$this->editPromptSceneIndex])) {
            $this->script['scenes'][$this->editPromptSceneIndex]['visualDescription'] = $this->editPromptText;
            $this->script['scenes'][$this->editPromptSceneIndex]['narration'] = $this->editSceneNarration;
            $this->script['scenes'][$this->editPromptSceneIndex]['duration'] = $this->editSceneDuration;
            $this->script['scenes'][$this->editPromptSceneIndex]['transition'] = $this->editSceneTransition;
        }

        // Store the custom prompt in storyboard
        if (!isset($this->storyboard['scenes'])) {
            $this->storyboard['scenes'] = [];
        }
        if (!isset($this->storyboard['scenes'][$this->editPromptSceneIndex])) {
            $this->storyboard['scenes'][$this->editPromptSceneIndex] = [];
        }
        $this->storyboard['scenes'][$this->editPromptSceneIndex]['prompt'] = $this->editPromptText;

        $this->closeEditPrompt();

        // Regenerate the image with the new prompt
        $scene = $this->script['scenes'][$this->editPromptSceneIndex] ?? null;
        if ($scene) {
            $this->generateImage($this->editPromptSceneIndex, $scene['id']);
        }
    }

    // =========================================================================
    // SCENE MEMORY METHODS (Style Bible, Character Bible, Location Bible)
    // =========================================================================

    /**
     * Toggle Style Bible.
     */
    public function toggleStyleBible(): void
    {
        $this->sceneMemory['styleBible']['enabled'] = !$this->sceneMemory['styleBible']['enabled'];

        // Sync to storyboard
        $this->storyboard['styleBible'] = $this->sceneMemory['styleBible'];
        $this->saveProject();
    }

    /**
     * Update Style Bible settings.
     */
    public function updateStyleBible(string $field, string $value): void
    {
        if (isset($this->sceneMemory['styleBible'][$field])) {
            $this->sceneMemory['styleBible'][$field] = $value;
            $this->storyboard['styleBible'] = $this->sceneMemory['styleBible'];
            $this->saveProject();
        }
    }

    /**
     * Toggle Character Bible.
     */
    public function toggleCharacterBible(): void
    {
        $this->sceneMemory['characterBible']['enabled'] = !$this->sceneMemory['characterBible']['enabled'];
        $this->saveProject();
    }

    /**
     * Add character to Character Bible.
     */
    public function addCharacter(string $name = '', string $description = ''): void
    {
        $this->sceneMemory['characterBible']['characters'][] = [
            'id' => uniqid('char_'),
            'name' => $name,
            'description' => $description,
            'role' => 'Supporting',
            'appliedScenes' => [],
            'traits' => [],
            'referenceImage' => null,
            'referenceImageBase64' => null,      // Base64 data for API calls (face consistency)
            'referenceImageMimeType' => null,    // MIME type (e.g., 'image/png')
            'referenceImageStatus' => 'none',    // 'none' | 'generating' | 'ready' | 'error'
        ];
        $this->saveProject();
    }

    /**
     * Add a trait to a character.
     */
    public function addCharacterTrait(int $characterIndex, string $trait = ''): void
    {
        $trait = trim($trait);
        if (empty($trait)) {
            return;
        }

        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex])) {
            return;
        }

        // Initialize traits array if not exists
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'])) {
            $this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'] = [];
        }

        // Avoid duplicates (case-insensitive)
        $existingTraits = array_map('strtolower', $this->sceneMemory['characterBible']['characters'][$characterIndex]['traits']);
        if (in_array(strtolower($trait), $existingTraits)) {
            return;
        }

        $this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'][] = $trait;
        $this->saveProject();
    }

    /**
     * Remove a trait from a character.
     */
    public function removeCharacterTrait(int $characterIndex, int $traitIndex): void
    {
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'][$traitIndex])) {
            return;
        }

        unset($this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'][$traitIndex]);
        $this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'] = array_values(
            $this->sceneMemory['characterBible']['characters'][$characterIndex]['traits']
        );
        $this->saveProject();
    }

    /**
     * Apply a preset trait set to a character based on archetype.
     */
    public function applyTraitPreset(int $characterIndex, string $preset): void
    {
        if (!isset($this->sceneMemory['characterBible']['characters'][$characterIndex])) {
            return;
        }

        $presets = [
            'hero' => ['confident', 'determined', 'courageous', 'charismatic'],
            'villain' => ['cunning', 'menacing', 'calculating', 'powerful'],
            'mentor' => ['wise', 'patient', 'experienced', 'supportive'],
            'comic' => ['witty', 'playful', 'energetic', 'quirky'],
            'mysterious' => ['enigmatic', 'reserved', 'observant', 'cryptic'],
            'professional' => ['competent', 'focused', 'reliable', 'articulate'],
            'creative' => ['imaginative', 'passionate', 'expressive', 'innovative'],
            'leader' => ['authoritative', 'decisive', 'inspiring', 'strategic'],
        ];

        if (!isset($presets[$preset])) {
            return;
        }

        // Merge with existing traits, avoiding duplicates
        $currentTraits = $this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'] ?? [];
        $currentTraitsLower = array_map('strtolower', $currentTraits);

        foreach ($presets[$preset] as $trait) {
            if (!in_array(strtolower($trait), $currentTraitsLower)) {
                $currentTraits[] = $trait;
                $currentTraitsLower[] = strtolower($trait);
            }
        }

        $this->sceneMemory['characterBible']['characters'][$characterIndex]['traits'] = $currentTraits;
        $this->saveProject();
    }

    /**
     * Remove character from Character Bible.
     */
    public function removeCharacter(int $index): void
    {
        if (isset($this->sceneMemory['characterBible']['characters'][$index])) {
            unset($this->sceneMemory['characterBible']['characters'][$index]);
            $this->sceneMemory['characterBible']['characters'] = array_values($this->sceneMemory['characterBible']['characters']);

            // Reset editing index if needed
            $count = count($this->sceneMemory['characterBible']['characters']);
            if ($this->editingCharacterIndex >= $count) {
                $this->editingCharacterIndex = max(0, $count - 1);
            }

            $this->saveProject();
        }
    }

    /**
     * Toggle Location Bible.
     */
    public function toggleLocationBible(): void
    {
        $this->sceneMemory['locationBible']['enabled'] = !$this->sceneMemory['locationBible']['enabled'];
        $this->saveProject();
    }

    /**
     * Add location to Location Bible.
     */
    public function addLocation(string $name = '', string $description = ''): void
    {
        $this->sceneMemory['locationBible']['locations'][] = [
            'id' => uniqid('loc_'),
            'name' => $name,
            'type' => 'exterior',
            'timeOfDay' => 'day',
            'weather' => 'clear',
            'atmosphere' => '',
            'description' => $description,
            'scenes' => [],
            'stateChanges' => [],
            'referenceImage' => null,
            'referenceImageBase64' => null,      // Base64 data for API calls (location consistency)
            'referenceImageMimeType' => null,    // MIME type (e.g., 'image/png')
            'referenceImageStatus' => 'none',    // 'none' | 'generating' | 'ready' | 'error'
        ];
        $this->saveProject();
    }

    /**
     * Add a state change to a location for a specific scene.
     */
    public function addLocationState(int $locationIndex, int $sceneIndex, string $state = ''): void
    {
        $state = trim($state);
        if (empty($state)) {
            return;
        }

        if (!isset($this->sceneMemory['locationBible']['locations'][$locationIndex])) {
            return;
        }

        // Initialize stateChanges array if not exists
        if (!isset($this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'])) {
            $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'] = [];
        }

        // Check if state already exists for this scene - update it
        $found = false;
        foreach ($this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'] as $idx => $change) {
            if (($change['scene'] ?? -1) === $sceneIndex) {
                $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'][$idx]['state'] = $state;
                $found = true;
                break;
            }
        }

        // Add new state change if not found
        if (!$found) {
            $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'][] = [
                'scene' => $sceneIndex,
                'state' => $state,
            ];

            // Sort by scene index
            usort(
                $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'],
                fn($a, $b) => ($a['scene'] ?? 0) <=> ($b['scene'] ?? 0)
            );
        }

        $this->saveProject();
    }

    /**
     * Remove a state change from a location.
     */
    public function removeLocationState(int $locationIndex, int $stateIndex): void
    {
        if (!isset($this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'][$stateIndex])) {
            return;
        }

        unset($this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'][$stateIndex]);
        $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'] = array_values(
            $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges']
        );
        $this->saveProject();
    }

    /**
     * Apply a preset state progression to a location.
     */
    public function applyLocationStatePreset(int $locationIndex, string $preset): void
    {
        if (!isset($this->sceneMemory['locationBible']['locations'][$locationIndex])) {
            return;
        }

        $scenes = $this->sceneMemory['locationBible']['locations'][$locationIndex]['scenes'] ?? [];
        if (count($scenes) < 2) {
            return; // Need at least 2 scenes for a state progression
        }

        // Sort scenes
        sort($scenes);
        $firstScene = $scenes[0];
        $lastScene = $scenes[count($scenes) - 1];

        $presets = [
            'destruction' => [
                ['state' => 'pristine, intact'],
                ['state' => 'damaged, destruction visible'],
            ],
            'time-of-day' => [
                ['state' => 'morning light, fresh atmosphere'],
                ['state' => 'evening, golden hour lighting'],
            ],
            'weather-change' => [
                ['state' => 'clear skies, bright'],
                ['state' => 'stormy, dramatic clouds'],
            ],
            'abandonment' => [
                ['state' => 'inhabited, active, signs of life'],
                ['state' => 'abandoned, dusty, overgrown'],
            ],
            'transformation' => [
                ['state' => 'ordinary, mundane'],
                ['state' => 'transformed, magical, ethereal'],
            ],
            'tension' => [
                ['state' => 'calm, peaceful'],
                ['state' => 'tense, foreboding'],
            ],
        ];

        if (!isset($presets[$preset])) {
            return;
        }

        // Apply first state to first scene, second state to last scene
        $this->sceneMemory['locationBible']['locations'][$locationIndex]['stateChanges'] = [
            ['scene' => $firstScene, 'state' => $presets[$preset][0]['state']],
            ['scene' => $lastScene, 'state' => $presets[$preset][1]['state']],
        ];

        $this->saveProject();
    }

    /**
     * Get the location state for a specific scene index.
     */
    protected function getLocationStateForScene(array $location, int $sceneIndex): ?string
    {
        $stateChanges = $location['stateChanges'] ?? [];
        if (empty($stateChanges)) {
            return null;
        }

        // Find the most recent state change at or before this scene
        $applicableState = null;
        foreach ($stateChanges as $change) {
            $changeScene = $change['scene'] ?? -1;
            if ($changeScene <= $sceneIndex) {
                $applicableState = $change['state'] ?? null;
            } else {
                break; // Since sorted, no need to continue
            }
        }

        return $applicableState;
    }

    /**
     * Remove location from Location Bible.
     */
    public function removeLocation(int $index): void
    {
        if (isset($this->sceneMemory['locationBible']['locations'][$index])) {
            unset($this->sceneMemory['locationBible']['locations'][$index]);
            $this->sceneMemory['locationBible']['locations'] = array_values($this->sceneMemory['locationBible']['locations']);

            // Reset editing index if needed
            $count = count($this->sceneMemory['locationBible']['locations']);
            if ($this->editingLocationIndex >= $count) {
                $this->editingLocationIndex = max(0, $count - 1);
            }

            $this->saveProject();
        }
    }

    // =========================================================================
    // RUNPOD POLLING METHODS
    // =========================================================================

    /**
     * Check status of pending RunPod jobs.
     */
    public function pollPendingJobs(): void
    {
        if (empty($this->pendingJobs)) {
            return;
        }

        $imageService = app(ImageGenerationService::class);

        foreach ($this->pendingJobs as $sceneIndex => $job) {
            try {
                $result = $imageService->checkRunPodJobStatus($job['jobId']);

                if ($result['status'] === 'COMPLETED') {
                    // Update storyboard scene with completed image
                    if (isset($this->storyboard['scenes'][$sceneIndex])) {
                        $this->storyboard['scenes'][$sceneIndex]['status'] = 'ready';
                        // Image URL should already be set
                    }

                    // Remove from pending
                    unset($this->pendingJobs[$sceneIndex]);
                    $this->saveProject();

                } elseif ($result['status'] === 'FAILED') {
                    // Mark as error
                    if (isset($this->storyboard['scenes'][$sceneIndex])) {
                        $this->storyboard['scenes'][$sceneIndex]['status'] = 'error';
                        $this->storyboard['scenes'][$sceneIndex]['error'] = $result['error'] ?? 'Generation failed';
                    }

                    unset($this->pendingJobs[$sceneIndex]);
                    $this->saveProject();
                }
                // If IN_QUEUE or IN_PROGRESS, keep polling

            } catch (\Exception $e) {
                \Log::error("Failed to poll job status: " . $e->getMessage());
            }
        }
    }

    /**
     * Get image models for display.
     */
    public function getImageModels(): array
    {
        return [
            'hidream' => [
                'name' => 'HiDream',
                'description' => 'Artistic & cinematic style',
                'tokenCost' => 2,
            ],
            'nanobanana-pro' => [
                'name' => 'NanoBanana Pro',
                'description' => 'High quality, fast generation',
                'tokenCost' => 3,
            ],
            'nanobanana' => [
                'name' => 'NanoBanana',
                'description' => 'Quick drafts, lower cost',
                'tokenCost' => 1,
            ],
        ];
    }

    // =========================================================================
    // PROMPT CHAIN METHODS
    // =========================================================================

    /**
     * Process prompt chain for all scenes.
     */
    public function processPromptChain(): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            $this->storyboard['promptChain']['status'] = 'processing';

            // Process each scene
            foreach ($this->script['scenes'] as $index => $scene) {
                $this->storyboard['promptChain']['scenes'][$index] = [
                    'sceneId' => $scene['id'],
                    'imagePrompt' => $this->buildScenePrompt($scene, $index),
                    'processed' => true,
                ];
            }

            $this->storyboard['promptChain']['status'] = 'ready';
            $this->storyboard['promptChain']['processedAt'] = now()->toIso8601String();

            $this->saveProject();

        } catch (\Exception $e) {
            $this->error = __('Failed to process prompt chain: ') . $e->getMessage();
            $this->storyboard['promptChain']['status'] = 'error';
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Apply a technical specs preset for quick configuration.
     */
    public function applyTechnicalSpecsPreset(string $preset): void
    {
        $presets = [
            'cinematic' => [
                'quality' => '4k',
                'positive' => 'cinematic, film grain, anamorphic lens, shallow depth of field, dramatic lighting, professional color grading, 8K resolution, HDR, masterpiece',
                'negative' => 'blurry, low quality, amateur, oversaturated, cartoon, anime, illustration, watermark, text, logo, nsfw, deformed',
            ],
            'photorealistic' => [
                'quality' => '4k',
                'positive' => 'photorealistic, ultra detailed, DSLR photo, natural lighting, sharp focus, 8K UHD, professional photography, realistic textures, high resolution',
                'negative' => 'cartoon, anime, illustration, 3D render, CGI, artificial, blurry, low quality, watermark, text, deformed, oversaturated',
            ],
            'artistic' => [
                'quality' => '2k',
                'positive' => 'artistic, stylized, vibrant colors, creative composition, expressive, beautiful artwork, detailed illustration, concept art quality',
                'negative' => 'blurry, low quality, pixelated, watermark, text, logo, amateur, poorly drawn, ugly',
            ],
            'documentary' => [
                'quality' => '4k',
                'positive' => 'documentary style, authentic, natural, candid, observational, informative, real-world, high quality footage, professional',
                'negative' => 'staged, artificial, cartoon, fantasy, blurry, low quality, watermark, text, logo, glamorous, stylized',
            ],
        ];

        if (!isset($presets[$preset])) {
            return;
        }

        $this->storyboard['technicalSpecs'] = array_merge(
            $this->storyboard['technicalSpecs'],
            $presets[$preset],
            ['enabled' => true]
        );

        $this->saveProject();
    }

    /**
     * Build comprehensive prompt for a scene integrating all Bibles.
     *
     * Prompt Chain Architecture (5 Layers):
     * 1. Style Bible - Visual DNA, style, color grade, atmosphere, camera
     * 2. Character Bible - Character descriptions for characters in this scene
     * 3. Location Bible - Location description for this scene
     * 4. Scene Content - Visual description + visual style settings
     * 5. Technical Specs - Quality and format specifications
     */
    protected function buildScenePrompt(array $scene, int $index): string
    {
        $parts = [];

        // =========================================================================
        // LAYER 1: STYLE BIBLE (Visual DNA)
        // =========================================================================
        if ($this->sceneMemory['styleBible']['enabled'] ?? false) {
            $styleBible = $this->sceneMemory['styleBible'];
            $styleParts = [];

            if (!empty($styleBible['style'])) {
                $styleParts[] = $styleBible['style'];
            }
            if (!empty($styleBible['colorGrade'])) {
                $styleParts[] = $styleBible['colorGrade'];
            }
            if (!empty($styleBible['atmosphere'])) {
                $styleParts[] = $styleBible['atmosphere'];
            }
            if (!empty($styleBible['camera'])) {
                $styleParts[] = $styleBible['camera'];
            }

            if (!empty($styleParts)) {
                $parts[] = 'STYLE: ' . implode(', ', $styleParts);
            }

            if (!empty($styleBible['visualDNA'])) {
                $parts[] = 'QUALITY: ' . $styleBible['visualDNA'];
            }
        }

        // =========================================================================
        // LAYER 2: CHARACTER BIBLE (Characters in this scene)
        // =========================================================================
        if ($this->sceneMemory['characterBible']['enabled'] ?? false) {
            $characters = $this->sceneMemory['characterBible']['characters'] ?? [];
            $sceneCharacters = $this->getCharactersForSceneIndex($characters, $index);

            if (!empty($sceneCharacters)) {
                $characterDescriptions = [];
                foreach ($sceneCharacters as $character) {
                    if (!empty($character['description'])) {
                        $name = $character['name'] ?? 'Character';
                        $charDesc = "{$name}: {$character['description']}";

                        // Include traits if available for personality/expression guidance
                        $traits = $character['traits'] ?? [];
                        if (!empty($traits)) {
                            $charDesc .= ' (personality: ' . implode(', ', array_slice($traits, 0, 4)) . ')';
                        }

                        $characterDescriptions[] = $charDesc;
                    }
                }
                if (!empty($characterDescriptions)) {
                    $parts[] = 'CHARACTERS: ' . implode('. ', $characterDescriptions);
                }
            }
        }

        // =========================================================================
        // LAYER 3: LOCATION BIBLE (Location for this scene)
        // =========================================================================
        if ($this->sceneMemory['locationBible']['enabled'] ?? false) {
            $locations = $this->sceneMemory['locationBible']['locations'] ?? [];
            $sceneLocation = $this->getLocationForSceneIndex($locations, $index);

            if ($sceneLocation) {
                $locationParts = [];

                $locName = $sceneLocation['name'] ?? '';
                $locType = $sceneLocation['type'] ?? '';
                if ($locName) {
                    $locationParts[] = $locName . ($locType ? " ({$locType})" : '');
                }

                if (!empty($sceneLocation['description'])) {
                    $locationParts[] = $sceneLocation['description'];
                }

                if (!empty($sceneLocation['timeOfDay'])) {
                    $locationParts[] = $sceneLocation['timeOfDay'];
                }

                if (!empty($sceneLocation['weather']) && $sceneLocation['weather'] !== 'clear') {
                    $locationParts[] = $sceneLocation['weather'] . ' weather';
                }

                // Include location state for this scene if available
                $locationState = $this->getLocationStateForScene($sceneLocation, $index);
                if ($locationState) {
                    $locationParts[] = 'current state: ' . $locationState;
                }

                if (!empty($locationParts)) {
                    $parts[] = 'LOCATION: ' . implode(', ', $locationParts);
                }
            }
        }

        // =========================================================================
        // LAYER 4: SCENE CONTENT (Visual description + Visual Style)
        // =========================================================================
        $visualStyle = $this->storyboard['visualStyle'] ?? [];
        $visualParts = [];

        if (!empty($visualStyle['mood'])) {
            $visualParts[] = $visualStyle['mood'] . ' mood';
        }
        if (!empty($visualStyle['lighting'])) {
            $visualParts[] = $visualStyle['lighting'] . ' lighting';
        }
        if (!empty($visualStyle['colorPalette'])) {
            $visualParts[] = $visualStyle['colorPalette'] . ' color palette';
        }
        if (!empty($visualStyle['composition'])) {
            $visualParts[] = $visualStyle['composition'] . ' shot';
        }

        if (!empty($visualParts)) {
            $parts[] = 'VISUAL: ' . implode(', ', $visualParts);
        }

        // Scene visual description
        $visualDescription = $scene['visualDescription'] ?? $scene['visual'] ?? $scene['narration'] ?? '';
        if (!empty($visualDescription)) {
            $parts[] = 'SCENE: ' . $visualDescription;
        }

        // =========================================================================
        // LAYER 5: TECHNICAL SPECS
        // =========================================================================
        if ($this->storyboard['technicalSpecs']['enabled'] ?? true) {
            $techSpecs = $this->storyboard['technicalSpecs']['positive'] ?? 'high quality, detailed, professional, 8K resolution';
            $parts[] = $techSpecs;
        }

        return implode('. ', array_filter($parts));
    }

    /**
     * Get characters that appear in a specific scene (for prompt building).
     */
    protected function getCharactersForSceneIndex(array $characters, int $sceneIndex): array
    {
        return array_filter($characters, function ($character) use ($sceneIndex) {
            $appliedScenes = $character['appliedScenes'] ?? $character['appearsInScenes'] ?? [];
            // Empty array means "applies to ALL scenes" (default behavior)
            // Non-empty array means "applies only to these specific scenes"
            return empty($appliedScenes) || in_array($sceneIndex, $appliedScenes);
        });
    }

    /**
     * Get the primary location for a specific scene (for prompt building).
     */
    protected function getLocationForSceneIndex(array $locations, int $sceneIndex): ?array
    {
        foreach ($locations as $location) {
            $scenes = $location['scenes'] ?? $location['appearsInScenes'] ?? [];
            // Empty array means "applies to ALL scenes" (default behavior)
            // Non-empty array means "applies only to these specific scenes"
            if (empty($scenes) || in_array($sceneIndex, $scenes)) {
                return $location;
            }
        }
        return null;
    }

    // =========================================================================
    // EDIT PROMPT MODAL METHODS
    // =========================================================================

    /**
     * Open edit prompt modal.
     */
    public function openEditPromptModal(int $sceneIndex): void
    {
        $this->editPromptSceneIndex = $sceneIndex;
        $scene = $this->script['scenes'][$sceneIndex] ?? null;
        $this->editPromptText = $scene['visualDescription'] ?? $scene['narration'] ?? '';
        $this->showEditPromptModal = true;

        $this->dispatch('open-edit-prompt-modal', ['sceneIndex' => $sceneIndex]);
    }

    /**
     * Append text to current prompt.
     */
    public function appendToPrompt(string $text): void
    {
        if (!empty($this->editPromptText)) {
            $this->editPromptText .= ', ' . $text;
        } else {
            $this->editPromptText = $text;
        }
    }

    // =========================================================================
    // PROJECT MANAGER METHODS
    // =========================================================================

    /**
     * Open project manager modal and load projects.
     */
    public function openProjectManager(): void
    {
        $this->loadProjectManagerProjects();
        $this->showProjectManager = true;
    }

    /**
     * Close project manager modal.
     */
    public function closeProjectManager(): void
    {
        $this->showProjectManager = false;
    }

    /**
     * Load projects for the project manager with pagination and filtering.
     */
    public function loadProjectManagerProjects(): void
    {
        $userId = auth()->id();
        $teamId = session('current_team_id', 0);

        // Base query for user's projects
        $baseQuery = WizardProject::where(function ($q) use ($userId, $teamId) {
            $q->where('user_id', $userId);
            if ($teamId) {
                $q->orWhere('team_id', $teamId);
            }
        });

        // Apply search filter to base query
        if (!empty($this->projectManagerSearch)) {
            $baseQuery->where('name', 'like', '%' . $this->projectManagerSearch . '%');
        }

        // Calculate status counts (before applying status filter)
        $this->calculateStatusCounts(clone $baseQuery);

        // Clone for filtered query
        $query = clone $baseQuery;

        // Apply status filter
        if ($this->projectManagerStatusFilter !== 'all') {
            $query->where('status', $this->projectManagerStatusFilter);
        }

        // Apply sorting with direction
        $query->orderBy($this->projectManagerSort, $this->projectManagerSortDirection);

        // Get total count for pagination (after status filter)
        $this->projectManagerTotal = $query->count();

        // Calculate offset for pagination
        $offset = ($this->projectManagerPage - 1) * $this->projectManagerPerPage;

        // Get paginated projects
        $projects = $query->skip($offset)->take($this->projectManagerPerPage)->get();

        $this->projectManagerProjects = $projects->map(function ($project) {
            // Calculate step progress (1-7 steps)
            $stepsCompleted = $this->calculateProjectStepProgress($project);

            return [
                'id' => $project->id,
                'name' => $project->name,
                'platform' => $project->platform,
                'status' => $project->status ?? $this->detectProjectStatus($project),
                'target_duration' => $project->target_duration,
                'script' => $project->script ?? [],
                'stepsCompleted' => $stepsCompleted,
                'created_at' => $project->created_at?->toIso8601String(),
                'updated_at' => $project->updated_at?->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Calculate status counts for filter tabs.
     */
    protected function calculateStatusCounts($query): void
    {
        // Get all projects to count statuses
        $allProjects = $query->get();

        $this->projectManagerStatusCounts = [
            'all' => $allProjects->count(),
            'draft' => $allProjects->where('status', 'draft')->count(),
            'in_progress' => $allProjects->where('status', 'in_progress')->count(),
            'complete' => $allProjects->where('status', 'complete')->count(),
        ];
    }

    /**
     * Calculate the step progress of a project.
     */
    protected function calculateProjectStepProgress($project): int
    {
        $steps = 0;

        // Step 1: Platform selected
        if (!empty($project->platform)) {
            $steps = 1;
        }

        // Step 2: Concept filled
        $concept = $project->concept ?? [];
        if (!empty($concept) && (!empty($concept['rawInput'] ?? '') || !empty($concept['refinedConcept'] ?? ''))) {
            $steps = 2;
        }

        // Step 3: Script has scenes
        $script = $project->script ?? [];
        if (!empty($script) && isset($script['scenes']) && count($script['scenes'] ?? []) > 0) {
            $steps = 3;
        }

        // Step 4: Storyboard has frames
        $storyboard = $project->storyboard ?? [];
        if (!empty($storyboard) && (isset($storyboard['frames']) || isset($storyboard['scenes']))) {
            $steps = 4;
        }

        // Step 5: Animation configured
        $animation = $project->animation ?? [];
        if (!empty($animation)) {
            $steps = 5;
        }

        // Step 6: Assembly configured
        $assembly = $project->assembly ?? [];
        if (!empty($assembly)) {
            $steps = 6;
        }

        // Step 7: Exported
        if (!empty($assembly) && isset($assembly['exported']) && $assembly['exported']) {
            $steps = 7;
        }

        return $steps;
    }

    /**
     * Detect project status based on its data.
     */
    protected function detectProjectStatus($project): string
    {
        $steps = $this->calculateProjectStepProgress($project);

        if ($steps >= 7) {
            return 'complete';
        } elseif ($steps >= 3) {
            return 'in_progress';
        }

        return 'draft';
    }

    /**
     * Go to a specific page in project manager.
     */
    public function projectManagerGoToPage(int $page): void
    {
        $totalPages = ceil($this->projectManagerTotal / $this->projectManagerPerPage);
        $this->projectManagerPage = max(1, min($page, $totalPages));
        $this->loadProjectManagerProjects();
    }

    /**
     * Go to next page in project manager.
     */
    public function projectManagerNextPage(): void
    {
        $totalPages = ceil($this->projectManagerTotal / $this->projectManagerPerPage);
        if ($this->projectManagerPage < $totalPages) {
            $this->projectManagerPage++;
            $this->loadProjectManagerProjects();
        }
    }

    /**
     * Go to previous page in project manager.
     */
    public function projectManagerPrevPage(): void
    {
        if ($this->projectManagerPage > 1) {
            $this->projectManagerPage--;
            $this->loadProjectManagerProjects();
        }
    }

    /**
     * Load a project from the project manager.
     */
    public function loadProjectFromManager(int $projectId): void
    {
        $userId = auth()->id();
        $teamId = session('current_team_id', 0);

        $project = WizardProject::where('id', $projectId)
            ->where(function ($q) use ($userId, $teamId) {
                $q->where('user_id', $userId);
                if ($teamId) {
                    $q->orWhere('team_id', $teamId);
                }
            })
            ->first();

        if (!$project) {
            $this->error = __('Project not found or access denied.');
            return;
        }

        // Load the project
        $this->loadProject($project);

        // Close modal and update URL
        $this->showProjectManager = false;
        $this->dispatch('update-browser-url', ['projectId' => $projectId]);
        $this->dispatch('project-loaded', ['projectId' => $projectId]);
    }

    /**
     * Delete a project from the project manager.
     */
    public function deleteProjectFromManager(int $projectId): void
    {
        $userId = auth()->id();
        $teamId = session('current_team_id', 0);

        $project = WizardProject::where('id', $projectId)
            ->where(function ($q) use ($userId, $teamId) {
                $q->where('user_id', $userId);
                if ($teamId) {
                    $q->orWhere('team_id', $teamId);
                }
            })
            ->first();

        if (!$project) {
            $this->error = __('Project not found or access denied.');
            return;
        }

        // Check if we're deleting the current project
        $isDeletingCurrent = $this->projectId === $projectId;

        // Delete associated assets and jobs
        $project->assets()->delete();
        $project->processingJobs()->delete();
        $project->delete();

        // If we deleted the current project, reset to new project
        if ($isDeletingCurrent) {
            $this->createNewProject();
        }

        // Refresh the projects list
        $this->loadProjectManagerProjects();

        $this->dispatch('project-deleted', ['projectId' => $projectId]);
    }

    /**
     * Create a new project (reset wizard state).
     */
    public function createNewProject(): void
    {
        // Reset all state to defaults
        $this->projectId = null;
        $this->projectName = 'Untitled Video';
        $this->currentStep = 1;
        $this->maxReachedStep = 1;

        $this->platform = null;
        $this->aspectRatio = '16:9';
        $this->targetDuration = 60;
        $this->format = null;
        $this->productionType = null;
        $this->productionSubtype = null;

        $this->concept = [
            'rawInput' => '',
            'refinedConcept' => '',
            'keywords' => [],
            'keyElements' => [],
            'logline' => '',
            'suggestedMood' => null,
            'suggestedTone' => null,
            'styleReference' => '',
            'avoidElements' => '',
            'targetAudience' => '',
        ];

        $this->characterIntelligence = [
            'enabled' => true,
            'narrationStyle' => 'voiceover',
            'characterCount' => 4,
            'suggestedCount' => 4,
            'characters' => [],
        ];

        $this->script = [
            'title' => '',
            'hook' => '',
            'scenes' => [],
            'cta' => '',
            'totalDuration' => 0,
            'totalNarrationTime' => 0,
        ];

        $this->voiceStatus = [
            'dialogueLines' => 0,
            'speakers' => 0,
            'voicesMapped' => 0,
            'scenesWithDialogue' => 0,
            'scenesWithVoiceover' => 0,
            'pendingVoices' => 0,
        ];

        $this->storyboard = [
            'scenes' => [],
            'styleBible' => null,
            'imageModel' => 'nanobanana',
            'visualStyle' => [
                'mood' => '',
                'lighting' => '',
                'colorPalette' => '',
                'composition' => '',
            ],
            'technicalSpecs' => [
                'enabled' => true,
                'quality' => '4k',
                'positive' => 'high quality, detailed, professional, 8K resolution, sharp focus',
                'negative' => 'blurry, low quality, ugly, distorted, watermark, nsfw, text, logo',
            ],
            'promptChain' => [
                'enabled' => true,
                'status' => 'pending',
                'processedAt' => null,
                'scenes' => [],
            ],
        ];

        $this->animation = [
            'scenes' => [],
            'voiceover' => [
                'voice' => 'nova',
                'speed' => 1.0,
            ],
        ];

        $this->assembly = [
            'transitions' => [],
            'defaultTransition' => 'fade',
            'music' => ['enabled' => false, 'trackId' => null, 'volume' => 30],
            'captions' => [
                'enabled' => true,
                'style' => 'karaoke',
                'position' => 'bottom',
                'size' => 1,
            ],
        ];

        $this->sceneMemory = [
            'styleBible' => [
                'enabled' => false,
                'style' => '',
                'colorGrade' => '',
                'atmosphere' => '',
                'visualDNA' => '',
            ],
            'characterBible' => [
                'enabled' => false,
                'characters' => [],
            ],
            'locationBible' => [
                'enabled' => false,
                'locations' => [],
            ],
        ];

        $this->multiShotMode = [
            'enabled' => false,
            'defaultShotCount' => 3,
        ];

        $this->conceptVariations = [];
        $this->selectedConceptIndex = 0;
        $this->pendingJobs = [];
        $this->error = null;

        // Close the modal
        $this->showProjectManager = false;

        // Update browser URL
        $this->dispatch('update-browser-url', ['projectId' => null]);
        $this->dispatch('project-created');
    }

    /**
     * React to search changes in project manager.
     */
    public function updatedProjectManagerSearch(): void
    {
        $this->projectManagerPage = 1; // Reset to first page when searching
        $this->loadProjectManagerProjects();
    }

    /**
     * React to sort changes in project manager.
     */
    public function updatedProjectManagerSort(): void
    {
        $this->projectManagerPage = 1; // Reset to first page when changing sort
        $this->loadProjectManagerProjects();
    }

    /**
     * Set status filter in project manager.
     */
    public function setProjectManagerStatusFilter(string $status): void
    {
        $this->projectManagerStatusFilter = $status;
        $this->projectManagerPage = 1; // Reset to first page when changing filter
        $this->loadProjectManagerProjects();
    }

    /**
     * Toggle sort direction in project manager.
     */
    public function toggleProjectManagerSortDirection(): void
    {
        $this->projectManagerSortDirection = $this->projectManagerSortDirection === 'asc' ? 'desc' : 'asc';
        $this->loadProjectManagerProjects();
    }

    /**
     * Toggle select mode in project manager.
     */
    public function toggleProjectManagerSelectMode(): void
    {
        $this->projectManagerSelectMode = !$this->projectManagerSelectMode;
        if (!$this->projectManagerSelectMode) {
            $this->projectManagerSelected = [];
        }
    }

    /**
     * Toggle selection of a project.
     */
    public function toggleProjectSelection(int $projectId): void
    {
        if (in_array($projectId, $this->projectManagerSelected)) {
            $this->projectManagerSelected = array_values(array_diff($this->projectManagerSelected, [$projectId]));
        } else {
            $this->projectManagerSelected[] = $projectId;
        }
    }

    /**
     * Select all visible projects.
     */
    public function selectAllProjects(): void
    {
        $this->projectManagerSelected = array_column($this->projectManagerProjects, 'id');
    }

    /**
     * Deselect all projects.
     */
    public function deselectAllProjects(): void
    {
        $this->projectManagerSelected = [];
    }

    /**
     * Delete selected projects.
     */
    public function deleteSelectedProjects(): void
    {
        try {
            if (empty($this->projectManagerSelected)) {
                return;
            }

            // Don't delete the currently loaded project
            $toDelete = array_filter($this->projectManagerSelected, fn($id) => $id !== $this->projectId);

            WizardProject::whereIn('id', $toDelete)->delete();

            // Reset selection
            $this->projectManagerSelected = [];
            $this->projectManagerSelectMode = false;

            // Reload the project list
            $this->loadProjectManagerProjects();

            $this->dispatch('projects-deleted', ['count' => count($toDelete)]);
        } catch (\Exception $e) {
            Log::error('Failed to delete selected projects: ' . $e->getMessage());
            $this->error = __('Failed to delete selected projects');
        }
    }

    /**
     * Export a project to JSON.
     */
    public function exportProject(int $projectId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $project = WizardProject::findOrFail($projectId);

        $exportData = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'project' => [
                'name' => $project->name,
                'platform' => $project->platform,
                'aspect_ratio' => $project->aspect_ratio,
                'target_duration' => $project->target_duration,
                'format' => $project->format,
                'production_type' => $project->production_type,
                'production_subtype' => $project->production_subtype,
                'status' => $project->status,
                'concept' => $project->concept,
                'script' => $project->script,
                'storyboard' => $project->storyboard,
                'animation' => $project->animation,
                'assembly' => $project->assembly,
            ],
        ];

        $filename = \Illuminate\Support\Str::slug($project->name) . '-' . now()->format('Y-m-d') . '.json';

        return response()->streamDownload(function () use ($exportData) {
            echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Get comprehensive debug snapshot of all wizard state.
     * Used for troubleshooting issues by capturing every setting and selection.
     */
    public function getDebugSnapshot(): array
    {
        return [
            '_meta' => [
                'version' => '2.0',
                'generated_at' => now()->toIso8601String(),
                'php_version' => phpversion(),
                'laravel_version' => app()->version(),
                'user_id' => auth()->id(),
                'team_id' => session('current_team_id'),
            ],
            'wizard_state' => [
                'project_id' => $this->projectId,
                'project_name' => $this->projectName,
                'current_step' => $this->currentStep,
                'max_reached_step' => $this->maxReachedStep,
                'is_loading' => $this->isLoading,
                'is_saving' => $this->isSaving,
                'error' => $this->error,
            ],
            'platform_settings' => [
                'platform' => $this->platform,
                'aspect_ratio' => $this->aspectRatio,
                'target_duration' => $this->targetDuration,
                'format' => $this->format,
                'production_type' => $this->productionType,
                'production_subtype' => $this->productionSubtype,
                'content_format_override' => $this->contentFormatOverride ?? null,
            ],
            'script_settings' => [
                'script_tone' => $this->scriptTone,
                'content_depth' => $this->contentDepth,
                'additional_instructions' => $this->additionalInstructions,
                'narrative_preset' => $this->narrativePreset,
                'story_arc' => $this->storyArc,
                'tension_curve' => $this->tensionCurve,
                'emotional_journey' => $this->emotionalJourney,
            ],
            'concept' => $this->concept,
            'script' => [
                'title' => $this->script['title'] ?? null,
                'hook' => $this->script['hook'] ?? null,
                'cta' => $this->script['cta'] ?? null,
                'scene_count' => count($this->script['scenes'] ?? []),
                'total_duration' => collect($this->script['scenes'] ?? [])->sum('duration'),
                'scenes_summary' => collect($this->script['scenes'] ?? [])->map(fn($s, $i) => [
                    'index' => $i,
                    'id' => $s['id'] ?? null,
                    'duration' => $s['duration'] ?? null,
                    'has_narration' => !empty($s['narration']),
                    'has_visual' => !empty($s['visualDescription']),
                ])->toArray(),
            ],
            'storyboard' => [
                'visual_style' => $this->storyboard['visualStyle'] ?? null,
                'image_model' => $this->storyboard['imageModel'] ?? null,
                'style_bible_enabled' => $this->storyboard['styleBible']['enabled'] ?? false,
                'prompt_chain_status' => $this->storyboard['promptChain']['status'] ?? null,
            ],
            'scene_memory' => [
                'style_bible_enabled' => $this->sceneMemory['styleBible']['enabled'] ?? false,
                'character_bible_enabled' => $this->sceneMemory['characterBible']['enabled'] ?? false,
                'character_count' => count($this->sceneMemory['characterBible']['characters'] ?? []),
                'location_bible_enabled' => $this->sceneMemory['locationBible']['enabled'] ?? false,
                'location_count' => count($this->sceneMemory['locationBible']['locations'] ?? []),
            ],
            'pending_jobs' => array_keys($this->pendingJobs ?? []),
            'config_snapshot' => [
                'platform_config' => config('appvideowizard.platforms.' . $this->platform) ?? null,
                'production_type_config' => $this->productionType
                    ? config('appvideowizard.production_types.' . $this->productionType)
                    : null,
            ],
        ];
    }

    /**
     * Export debug snapshot as downloadable JSON file.
     */
    public function exportDebugSnapshot(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $snapshot = $this->getDebugSnapshot();
        $filename = 'wizard-debug-' . ($this->projectId ?? 'new') . '-' . now()->format('Y-m-d-His') . '.json';

        return response()->streamDownload(function () use ($snapshot) {
            echo json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Dispatch debug snapshot to browser console (for development).
     */
    public function logDebugSnapshot(): void
    {
        $snapshot = $this->getDebugSnapshot();
        $this->dispatch('vw-debug', [
            'action' => 'debug-snapshot',
            'message' => 'Full wizard state snapshot',
            'data' => $snapshot,
        ]);
    }

    /**
     * Import a project from JSON file.
     */
    public function importProject($file): void
    {
        try {
            if (!$file) {
                $this->error = __('No file selected');
                return;
            }

            $content = file_get_contents($file->getRealPath());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error = __('Invalid JSON file');
                return;
            }

            if (!isset($data['project'])) {
                $this->error = __('Invalid project file format');
                return;
            }

            $projectData = $data['project'];

            // Create new project from imported data
            $project = new WizardProject();
            $project->user_id = auth()->id();
            $project->team_id = session('current_team_id', 0);
            $project->name = ($projectData['name'] ?? 'Imported Project') . ' (Imported)';
            $project->platform = $projectData['platform'] ?? null;
            $project->aspect_ratio = $projectData['aspect_ratio'] ?? '16:9';
            $project->target_duration = $projectData['target_duration'] ?? 60;
            $project->format = $projectData['format'] ?? null;
            $project->production_type = $projectData['production_type'] ?? null;
            $project->production_subtype = $projectData['production_subtype'] ?? null;
            $project->status = 'draft'; // Always start as draft
            $project->concept = $projectData['concept'] ?? [];
            $project->script = $projectData['script'] ?? [];
            $project->storyboard = $projectData['storyboard'] ?? [];
            $project->animation = $projectData['animation'] ?? [];
            $project->assembly = $projectData['assembly'] ?? [];
            $project->save();

            // Reload the project list
            $this->loadProjectManagerProjects();

            $this->dispatch('project-imported', ['projectId' => $project->id]);
        } catch (\Exception $e) {
            Log::error('Failed to import project: ' . $e->getMessage());
            $this->error = __('Failed to import project: ') . $e->getMessage();
        }
    }

    /**
     * Duplicate a project.
     */
    public function duplicateProject(int $projectId): void
    {
        try {
            $original = WizardProject::findOrFail($projectId);

            // Create a copy with a new name
            $copy = $original->replicate();
            $copy->name = $original->name . ' (Copy)';
            $copy->created_at = now();
            $copy->updated_at = now();
            $copy->save();

            // Reload the project list
            $this->loadProjectManagerProjects();

            $this->dispatch('project-duplicated', ['projectId' => $copy->id]);
        } catch (\Exception $e) {
            Log::error('Failed to duplicate project: ' . $e->getMessage());
            $this->error = __('Failed to duplicate project');
        }
    }

    /**
     * Rename a project.
     */
    public function renameProject(int $projectId, string $newName): void
    {
        try {
            $newName = trim($newName);
            if (empty($newName)) {
                $this->error = __('Project name cannot be empty');
                return;
            }

            $project = WizardProject::findOrFail($projectId);
            $project->name = $newName;
            $project->save();

            // If this is the currently loaded project, update local state
            if ($this->projectId === $projectId) {
                $this->projectName = $newName;
            }

            // Reload the project list
            $this->loadProjectManagerProjects();

            $this->dispatch('project-renamed', ['projectId' => $projectId, 'newName' => $newName]);
        } catch (\Exception $e) {
            Log::error('Failed to rename project: ' . $e->getMessage());
            $this->error = __('Failed to rename project');
        }
    }

    /**
     * Get the status of a project based on its data.
     */
    public function getProjectStatus(array $projectData): string
    {
        $concept = $projectData['concept'] ?? [];
        $script = $projectData['script'] ?? [];
        $storyboard = $projectData['storyboard'] ?? [];
        $animation = $projectData['animation'] ?? [];
        $assembly = $projectData['assembly'] ?? [];

        // Check if export/assembly is complete
        if (!empty($assembly) && isset($assembly['exported']) && $assembly['exported']) {
            return 'complete';
        }

        // Check if any work has been done beyond concept
        if (!empty($storyboard) || !empty($animation) || !empty($assembly)) {
            return 'in_progress';
        }

        // Check if script has scenes
        if (!empty($script) && isset($script['scenes']) && count($script['scenes'] ?? []) > 0) {
            return 'in_progress';
        }

        // Check if concept is filled
        if (!empty($concept) && !empty($concept['topic'] ?? '')) {
            return 'draft';
        }

        return 'draft';
    }

    // =========================================================================
    // STYLE TEMPLATE METHODS
    // =========================================================================

    /**
     * Apply a style template.
     */
    public function applyStyleTemplate(string $template): void
    {
        $templates = [
            'cinematic' => [
                'style' => 'Cinematic photorealistic photography, Hollywood blockbuster look, shot on ARRI Alexa',
                'colorGrade' => 'Teal and orange color grading, lifted blacks, cinematic LUT',
                'atmosphere' => 'Dramatic atmosphere, volumetric lighting, lens flares',
                'camera' => 'Anamorphic lenses, shallow depth of field, wide establishing shots',
                'visualDNA' => 'Epic scale, professional cinematography, Marvel quality visuals',
            ],
            'documentary' => [
                'style' => 'Documentary photography, authentic realism, natural lighting',
                'colorGrade' => 'Natural colors, slight desaturation, documentary grade',
                'atmosphere' => 'Authentic atmosphere, real-world environments',
                'camera' => 'Handheld camera feel, natural framing, observational style',
                'visualDNA' => 'Authentic, journalistic, National Geographic quality',
            ],
            'anime' => [
                'style' => 'Anime art style, cel-shaded, Japanese animation aesthetic',
                'colorGrade' => 'Vibrant saturated colors, anime color palette',
                'atmosphere' => 'Stylized atmosphere, dramatic lighting, expressive',
                'camera' => 'Dynamic angles, action lines, anime cinematography',
                'visualDNA' => 'Studio Ghibli quality, detailed backgrounds, expressive characters',
            ],
            'noir' => [
                'style' => 'Film noir style, black and white, high contrast',
                'colorGrade' => 'Monochrome, deep blacks, high contrast',
                'atmosphere' => 'Moody, mysterious, shadowy atmosphere',
                'camera' => 'Low-key lighting, dramatic shadows, Dutch angles',
                'visualDNA' => 'Classic film noir, 1940s aesthetic, detective movie quality',
            ],
            '3d' => [
                'style' => 'Pixar-style 3D animation, stylized 3D rendering',
                'colorGrade' => 'Vibrant colors, soft gradients, 3D render quality',
                'atmosphere' => 'Whimsical atmosphere, clean environments',
                'camera' => 'Smooth camera movements, 3D depth, cinematic framing',
                'visualDNA' => 'Pixar quality, Disney animation, high-end 3D render',
            ],
        ];

        if (isset($templates[$template])) {
            $this->sceneMemory['styleBible'] = array_merge(
                $this->sceneMemory['styleBible'],
                $templates[$template],
                ['enabled' => true]
            );
            $this->saveProject();
        }
    }

    // =========================================================================
    // STEP TRANSITION HOOK - AUTO-POPULATE SCENE MEMORY
    // =========================================================================

    /**
     * Auto-populate Scene Memory when entering Storyboard step.
     * This applies information from the Script step to Character Bible, Location Bible, and Style Bible.
     */
    protected function autoPopulateSceneMemory(): void
    {
        // Skip if already has characters or locations (don't override user edits)
        $hasExistingCharacters = !empty($this->sceneMemory['characterBible']['characters']);
        $hasExistingLocations = !empty($this->sceneMemory['locationBible']['locations']);
        $hasExistingStyle = !empty($this->sceneMemory['styleBible']['style']);

        // 1. Auto-populate Style Bible based on production type (if not already set)
        if (!$hasExistingStyle) {
            $this->transitionMessage = __('Setting up visual style...');
            $this->autoPopulateStyleBible();
        }

        // 2. Auto-detect characters from script (if none exist)
        if (!$hasExistingCharacters) {
            $this->transitionMessage = __('Detecting characters from script...');
            $this->autoDetectCharactersFromScript();
        }

        // 3. Auto-detect locations from script (if none exist)
        if (!$hasExistingLocations) {
            $this->transitionMessage = __('Identifying locations...');
            $this->autoDetectLocationsFromScript();
        }

        // Dispatch event to notify UI
        $this->dispatch('scene-memory-populated', [
            'characters' => count($this->sceneMemory['characterBible']['characters']),
            'locations' => count($this->sceneMemory['locationBible']['locations']),
            'styleBibleEnabled' => $this->sceneMemory['styleBible']['enabled'],
        ]);
    }

    /**
     * Auto-populate Style Bible based on production type, concept, and platform.
     * Creates comprehensive visual consistency settings for the entire video.
     */
    protected function autoPopulateStyleBible(): void
    {
        // Get base defaults from production type
        $styleDefaults = $this->getStyleBibleDefaultsForProductionType();

        // Enhance with concept data (mood, tone from AI concept refinement)
        $styleDefaults = $this->enhanceStyleWithConceptData($styleDefaults);

        // Add platform-specific optimizations
        $styleDefaults = $this->addPlatformOptimizations($styleDefaults);

        if (!empty($styleDefaults)) {
            $this->sceneMemory['styleBible'] = array_merge(
                $this->sceneMemory['styleBible'],
                $styleDefaults,
                ['enabled' => true]
            );

            // Also populate storyboard visualStyle for UI dropdowns
            $this->populateStoryboardVisualStyle($styleDefaults);
        }

        // Dispatch event for debugging
        $this->dispatch('vw-debug', [
            'type' => 'style_bible_populated',
            'productionType' => $this->productionType,
            'productionSubtype' => $this->productionSubtype,
            'platform' => $this->platform,
            'hasConcept' => !empty($this->concept['suggestedMood']),
        ]);
    }

    /**
     * Enhance style defaults with data from concept refinement.
     */
    protected function enhanceStyleWithConceptData(array $styleDefaults): array
    {
        $concept = $this->concept ?? [];

        // Apply suggested mood from concept
        if (!empty($concept['suggestedMood'])) {
            $moodStyles = $this->getMoodStyleEnhancements($concept['suggestedMood']);
            if (!empty($moodStyles)) {
                // Append mood-specific enhancements to atmosphere
                if (!empty($styleDefaults['atmosphere'])) {
                    $styleDefaults['atmosphere'] .= ', ' . $moodStyles['atmosphere'];
                } else {
                    $styleDefaults['atmosphere'] = $moodStyles['atmosphere'];
                }

                // Add mood-specific color adjustments
                if (!empty($moodStyles['colorAdjustment']) && !empty($styleDefaults['colorGrade'])) {
                    $styleDefaults['colorGrade'] .= ', ' . $moodStyles['colorAdjustment'];
                }
            }
        }

        // Apply suggested tone from concept
        if (!empty($concept['suggestedTone'])) {
            $toneStyles = $this->getToneStyleEnhancements($concept['suggestedTone']);
            if (!empty($toneStyles)) {
                // Enhance visual style with tone
                if (!empty($styleDefaults['style'])) {
                    $styleDefaults['style'] .= ', ' . $toneStyles['style'];
                }
            }
        }

        // Apply style reference from concept if available
        if (!empty($concept['styleReference'])) {
            $styleDefaults['visualDNA'] = ($styleDefaults['visualDNA'] ?? '') .
                ', inspired by: ' . $concept['styleReference'];
        }

        return $styleDefaults;
    }

    /**
     * Get mood-specific style enhancements.
     */
    protected function getMoodStyleEnhancements(string $mood): array
    {
        $mood = strtolower(trim($mood));

        $moodMap = [
            'inspiring' => [
                'atmosphere' => 'uplifting, hopeful, motivational lighting',
                'colorAdjustment' => 'warm golden tones, bright highlights',
                'lighting' => 'bright',
                'colorPalette' => 'warm',
            ],
            'mysterious' => [
                'atmosphere' => 'enigmatic, shadowy, intriguing',
                'colorAdjustment' => 'deep shadows, selective lighting',
                'lighting' => 'dramatic',
                'colorPalette' => 'cool',
            ],
            'energetic' => [
                'atmosphere' => 'dynamic, vibrant, high-energy',
                'colorAdjustment' => 'saturated colors, punchy contrast',
                'lighting' => 'bright',
                'colorPalette' => 'vibrant',
            ],
            'calm' => [
                'atmosphere' => 'peaceful, serene, meditative',
                'colorAdjustment' => 'soft pastels, gentle gradients',
                'lighting' => 'soft',
                'colorPalette' => 'pastel',
            ],
            'dramatic' => [
                'atmosphere' => 'intense, powerful, emotionally charged',
                'colorAdjustment' => 'high contrast, deep blacks',
                'lighting' => 'dramatic',
                'colorPalette' => 'rich',
            ],
            'playful' => [
                'atmosphere' => 'fun, whimsical, lighthearted',
                'colorAdjustment' => 'bright, cheerful colors',
                'lighting' => 'bright',
                'colorPalette' => 'vibrant',
            ],
            'nostalgic' => [
                'atmosphere' => 'warm memories, vintage feel, wistful',
                'colorAdjustment' => 'warm sepia tones, film grain effect',
                'lighting' => 'golden',
                'colorPalette' => 'warm',
            ],
            'professional' => [
                'atmosphere' => 'polished, confident, authoritative',
                'colorAdjustment' => 'clean, balanced colors',
                'lighting' => 'studio',
                'colorPalette' => 'neutral',
            ],
            'dark' => [
                'atmosphere' => 'moody, intense, brooding',
                'colorAdjustment' => 'desaturated, heavy shadows',
                'lighting' => 'low-key',
                'colorPalette' => 'dark',
            ],
            'romantic' => [
                'atmosphere' => 'intimate, warm, emotionally tender',
                'colorAdjustment' => 'soft warm tones, dreamy highlights',
                'lighting' => 'golden',
                'colorPalette' => 'warm',
            ],
        ];

        return $moodMap[$mood] ?? [];
    }

    /**
     * Get tone-specific style enhancements.
     */
    protected function getToneStyleEnhancements(string $tone): array
    {
        $tone = strtolower(trim($tone));

        $toneMap = [
            'professional' => ['style' => 'polished corporate aesthetic'],
            'casual' => ['style' => 'relaxed approachable visuals'],
            'humorous' => ['style' => 'playful bright comedic framing'],
            'serious' => ['style' => 'formal authoritative composition'],
            'engaging' => ['style' => 'dynamic attention-grabbing visuals'],
            'informative' => ['style' => 'clear educational presentation'],
            'conversational' => ['style' => 'friendly intimate framing'],
            'authoritative' => ['style' => 'commanding powerful presence'],
            'inspirational' => ['style' => 'uplifting heroic imagery'],
            'emotional' => ['style' => 'expressive intimate cinematography'],
        ];

        return $toneMap[$tone] ?? [];
    }

    /**
     * Add platform-specific optimizations to style.
     */
    protected function addPlatformOptimizations(array $styleDefaults): array
    {
        $platform = $this->platform ?? '';

        $platformOptimizations = [
            'youtube' => [
                'technicalNote' => 'optimized for YouTube, thumbnail-friendly compositions',
                'composition' => 'wide establishing shots, clear focal points',
            ],
            'instagram' => [
                'technicalNote' => 'Instagram-optimized, mobile-first visuals',
                'composition' => 'vertical-friendly framing, bold visuals',
                'colorAdjustment' => 'Instagram-aesthetic colors',
            ],
            'tiktok' => [
                'technicalNote' => 'TikTok-optimized, fast-paced visuals',
                'composition' => 'vertical format, dynamic movement',
                'colorAdjustment' => 'high contrast, trend-aware palette',
            ],
            'facebook' => [
                'technicalNote' => 'Facebook-optimized, feed-friendly',
                'composition' => 'clear focal points, text-safe zones',
            ],
            'linkedin' => [
                'technicalNote' => 'LinkedIn professional standards',
                'composition' => 'professional framing, business-appropriate',
                'colorAdjustment' => 'corporate color palette',
            ],
            'twitter' => [
                'technicalNote' => 'Twitter/X optimized, scroll-stopping',
                'composition' => 'impactful opening frames, clear messaging',
            ],
        ];

        if (isset($platformOptimizations[$platform])) {
            $opts = $platformOptimizations[$platform];

            // Add technical note to visualDNA
            if (!empty($opts['technicalNote'])) {
                $styleDefaults['visualDNA'] = ($styleDefaults['visualDNA'] ?? '') .
                    ', ' . $opts['technicalNote'];
            }

            // Store composition preference
            if (!empty($opts['composition'])) {
                $styleDefaults['platformComposition'] = $opts['composition'];
            }
        }

        return $styleDefaults;
    }

    /**
     * Populate storyboard visualStyle settings from Style Bible.
     */
    protected function populateStoryboardVisualStyle(array $styleDefaults): void
    {
        // Map Style Bible data to storyboard visualStyle dropdowns
        $concept = $this->concept ?? [];
        $suggestedMood = strtolower($concept['suggestedMood'] ?? '');

        // Get mood enhancements for dropdown values
        $moodEnhancements = $this->getMoodStyleEnhancements($suggestedMood);

        // Set mood dropdown
        if (!empty($moodEnhancements['lighting'])) {
            $this->storyboard['visualStyle']['lighting'] = $moodEnhancements['lighting'];
        }

        if (!empty($moodEnhancements['colorPalette'])) {
            $this->storyboard['visualStyle']['colorPalette'] = $moodEnhancements['colorPalette'];
        }

        // Set mood based on concept
        if (!empty($suggestedMood)) {
            $this->storyboard['visualStyle']['mood'] = $suggestedMood;
        }

        // Set composition from platform optimization
        if (!empty($styleDefaults['platformComposition'])) {
            $this->storyboard['visualStyle']['composition'] = $styleDefaults['platformComposition'];
        }
    }

    /**
     * Get Style Bible defaults based on production type.
     * Includes camera language and comprehensive visual settings.
     */
    protected function getStyleBibleDefaultsForProductionType(): array
    {
        $productionType = $this->productionType ?? '';
        $productionSubtype = $this->productionSubtype ?? '';

        $defaults = [
            'commercial' => [
                'style' => 'Professional commercial style, clean visuals, product-focused, high production value',
                'colorGrade' => 'Bright, vibrant colors, commercial quality, balanced exposure',
                'atmosphere' => 'Upbeat, modern, engaging atmosphere, aspirational',
                'camera' => 'Smooth dolly shots, product close-ups, clean compositions, studio lighting',
                'visualDNA' => 'High-end commercial production, Madison Avenue quality, broadcast-ready',
            ],
            'social_media' => [
                'style' => 'Dynamic social media style, eye-catching, trend-focused, thumb-stopping',
                'colorGrade' => 'High contrast, saturated colors, mobile-optimized, bold palette',
                'atmosphere' => 'Energetic, engaging, scroll-stopping, relatable',
                'camera' => 'Dynamic angles, quick cuts, selfie-style, handheld energy',
                'visualDNA' => 'Viral content quality, platform-native aesthetic, share-worthy',
            ],
            'educational' => [
                'style' => 'Clear educational style, informative visuals, well-organized, accessible',
                'colorGrade' => 'Neutral colors, good contrast for readability, balanced',
                'atmosphere' => 'Professional, trustworthy, accessible, approachable',
                'camera' => 'Steady shots, clear framing, presenter-focused, diagram-friendly',
                'visualDNA' => 'Documentary quality, educational content standard, TED-talk aesthetic',
            ],
            'entertainment' => [
                'style' => 'Cinematic entertainment style, dramatic visuals, theatrical quality',
                'colorGrade' => 'Film-quality color grading, Hollywood look, rich tones',
                'atmosphere' => 'Immersive, engaging, theatrical, emotionally resonant',
                'camera' => 'Cinematic movements, dramatic angles, depth of field, ARRI-style',
                'visualDNA' => 'Netflix quality, premium streaming standard, binge-worthy',
            ],
            'corporate' => [
                'style' => 'Professional corporate style, polished visuals, brand-aligned',
                'colorGrade' => 'Clean, professional color palette, brand-consistent',
                'atmosphere' => 'Trustworthy, sophisticated, business-appropriate, confident',
                'camera' => 'Steady corporate shots, executive framing, office environments',
                'visualDNA' => 'Fortune 500 quality, executive presentation standard, investor-ready',
            ],
            'music_video' => [
                'style' => 'Creative music video style, artistic visuals, rhythm-driven',
                'colorGrade' => 'Bold color choices, artistic grading, mood-driven palette',
                'atmosphere' => 'Rhythmic, expressive, genre-appropriate, visceral',
                'camera' => 'Creative movements, beat-synced, performance shots, artistic angles',
                'visualDNA' => 'MTV quality, artistic music visual standard, chart-topping aesthetic',
            ],
            'documentary' => [
                'style' => 'Documentary style, authentic visuals, journalistic integrity',
                'colorGrade' => 'Natural color grading, realistic tones, authentic look',
                'atmosphere' => 'Authentic, immersive, story-driven, truthful',
                'camera' => 'Handheld authenticity, interview setups, b-roll rich, observational',
                'visualDNA' => 'HBO Documentary quality, cinéma vérité standard, award-worthy',
            ],
            'animation' => [
                'style' => 'Animated style, stylized visuals, character-driven, expressive',
                'colorGrade' => 'Vibrant animation colors, stylized palette, bold choices',
                'atmosphere' => 'Whimsical, expressive, visually dynamic, imaginative',
                'camera' => 'Virtual camera movements, impossible angles, smooth transitions',
                'visualDNA' => 'Pixar quality, premium animation standard, family-friendly',
            ],
            'lifestyle' => [
                'style' => 'Lifestyle aesthetic, aspirational visuals, authentic moments',
                'colorGrade' => 'Warm, inviting colors, Instagram-worthy palette',
                'atmosphere' => 'Relatable, aspirational, warm, inviting',
                'camera' => 'Natural light preference, candid moments, lifestyle b-roll',
                'visualDNA' => 'Influencer quality, lifestyle brand aesthetic, Pinterest-worthy',
            ],
            'product' => [
                'style' => 'Product showcase style, detail-focused, premium presentation',
                'colorGrade' => 'Clean whites, accurate colors, studio quality',
                'atmosphere' => 'Premium, desirable, detailed, luxurious',
                'camera' => 'Macro details, 360 rotations, studio lighting, product hero shots',
                'visualDNA' => 'Apple-quality product visuals, e-commerce premium, catalog-ready',
            ],
            'testimonial' => [
                'style' => 'Authentic testimonial style, trustworthy, personal connection',
                'colorGrade' => 'Natural skin tones, warm and inviting, professional',
                'atmosphere' => 'Genuine, trustworthy, relatable, convincing',
                'camera' => 'Interview framing, eye-level connection, comfortable distance',
                'visualDNA' => 'Customer story quality, social proof aesthetic, trust-building',
            ],
        ];

        // Comprehensive subtype-specific overrides with camera
        $subtypeDefaults = [
            'action' => [
                'style' => 'High-energy action style, dynamic camera work, intense visuals',
                'colorGrade' => 'Desaturated with punchy highlights, action movie look',
                'atmosphere' => 'Intense, adrenaline-pumping, explosive',
                'camera' => 'Fast tracking shots, crash zooms, impact angles, shaky-cam energy',
            ],
            'comedy' => [
                'style' => 'Bright comedy style, well-lit, inviting, comedic timing',
                'colorGrade' => 'Warm, friendly colors, sitcom aesthetic',
                'atmosphere' => 'Light-hearted, fun, accessible',
                'camera' => 'Wide comedy frames, reaction shots, timing-focused cuts',
            ],
            'drama' => [
                'style' => 'Dramatic cinematic style, emotional lighting, character-focused',
                'colorGrade' => 'Rich, moody color grading, prestige TV look',
                'atmosphere' => 'Emotional, immersive, character-focused',
                'camera' => 'Intimate close-ups, slow reveals, emotional beats, shallow DOF',
            ],
            'horror' => [
                'style' => 'Dark horror style, unsettling visuals, tension-building',
                'colorGrade' => 'Desaturated, cold tones, high contrast shadows',
                'atmosphere' => 'Tense, unsettling, atmospheric dread',
                'camera' => 'Creeping movements, POV horror, jump scare setups, off-kilter angles',
            ],
            'sci-fi' => [
                'style' => 'Futuristic sci-fi style, high-tech visuals, otherworldly',
                'colorGrade' => 'Cool blues and teals, neon accents, tech aesthetic',
                'atmosphere' => 'Futuristic, immersive, technologically advanced',
                'camera' => 'Smooth glides, HUD overlays, vast establishing shots, tech details',
            ],
            'fantasy' => [
                'style' => 'Epic fantasy style, magical visuals, mythical grandeur',
                'colorGrade' => 'Rich saturated colors, ethereal tones, golden magic',
                'atmosphere' => 'Magical, epic, otherworldly',
                'camera' => 'Sweeping vistas, hero shots, magical reveals, epic scale',
            ],
            'thriller' => [
                'style' => 'Suspenseful thriller style, tension-building visuals',
                'colorGrade' => 'Cold, clinical tones with warm accent pops',
                'atmosphere' => 'Suspenseful, paranoid, edge-of-seat tension',
                'camera' => 'Slow push-ins, surveillance angles, claustrophobic framing',
            ],
            'romance' => [
                'style' => 'Romantic visual style, soft and dreamy, intimate',
                'colorGrade' => 'Warm, soft focus, romantic glow, skin-flattering',
                'atmosphere' => 'Intimate, warm, emotionally tender',
                'camera' => 'Soft focus close-ups, two-shots, golden hour preference',
            ],
            'sports' => [
                'style' => 'Dynamic sports style, high-energy, athletic',
                'colorGrade' => 'High contrast, energetic colors, broadcast quality',
                'atmosphere' => 'Competitive, exciting, triumphant',
                'camera' => 'Super slow-mo, tracking athletes, victory moments, wide action',
            ],
            'travel' => [
                'style' => 'Travel documentary style, wanderlust-inducing, exploration',
                'colorGrade' => 'Natural vibrant colors, location-authentic palette',
                'atmosphere' => 'Adventurous, inspiring, culturally rich',
                'camera' => 'Drone aerials, ground-level exploration, local details, golden hour',
            ],
        ];

        // Start with production type defaults
        $result = $defaults[$productionType] ?? $defaults['entertainment'];

        // Merge subtype overrides if available
        if ($productionSubtype && isset($subtypeDefaults[$productionSubtype])) {
            $result = array_merge($result, $subtypeDefaults[$productionSubtype]);
        }

        return $result;
    }

    /**
     * Auto-detect characters from script content using AI extraction.
     * Falls back to pattern matching if AI fails.
     */
    protected function autoDetectCharactersFromScript(): void
    {
        // Try AI-powered extraction first
        try {
            $service = app(CharacterExtractionService::class);

            $result = $service->extractCharacters($this->script, [
                'teamId' => session('current_team_id', 0),
                'genre' => $this->productionType ?? 'General',
                'productionType' => $this->productionType,
                'productionMode' => 'standard',
                'styleBible' => $this->sceneMemory['styleBible'] ?? null,
                'visualMode' => $this->getVisualMode(), // Master visual mode enforcement
            ]);

            if ($result['success'] && !empty($result['characters'])) {
                Log::info('CharacterExtraction: AI extraction successful', [
                    'count' => count($result['characters']),
                ]);

                // Add AI-extracted characters to Character Bible
                foreach ($result['characters'] as $character) {
                    // Check if already exists
                    $exists = collect($this->sceneMemory['characterBible']['characters'])
                        ->where('name', $character['name'])
                        ->isNotEmpty();

                    if (!$exists) {
                        $this->sceneMemory['characterBible']['characters'][] = [
                            'id' => $character['id'] ?? uniqid('char_'),
                            'name' => $character['name'],
                            'description' => $character['description'] ?? '',
                            'role' => $character['role'] ?? 'Supporting',
                            'appliedScenes' => $character['appearsInScenes'] ?? [],
                            'traits' => $character['traits'] ?? [],
                            'referenceImage' => null,
                            'autoDetected' => true,
                            'aiGenerated' => true,
                        ];
                    }
                }

                // Enable Character Bible if we detected any characters
                if (!empty($result['characters'])) {
                    $this->sceneMemory['characterBible']['enabled'] = true;
                }

                // Dispatch event for debugging
                $this->dispatch('vw-debug', [
                    'type' => 'character_extraction',
                    'method' => 'ai',
                    'count' => count($result['characters']),
                    'hasHumanCharacters' => $result['hasHumanCharacters'],
                ]);

                return; // AI extraction successful, no need for fallback
            }

            // If AI returned no characters but was successful, check if video has no human characters
            if ($result['success'] && empty($result['characters']) && !$result['hasHumanCharacters']) {
                Log::info('CharacterExtraction: AI determined no human characters in video');
                $this->dispatch('vw-debug', [
                    'type' => 'character_extraction',
                    'method' => 'ai',
                    'count' => 0,
                    'message' => 'No human characters detected',
                ]);
                return;
            }

        } catch (\Exception $e) {
            Log::warning('CharacterExtraction: AI extraction failed, falling back to pattern matching', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to pattern-based detection
        $this->autoDetectCharactersWithPatterns();
    }

    /**
     * Pattern-based character detection (fallback method).
     */
    protected function autoDetectCharactersWithPatterns(): void
    {
        $detectedCharacters = [];
        $characterScenes = []; // Track which scenes each character appears in

        // Common character indicators
        $characterPatterns = [
            // Named roles
            '/\b(the\s+)?(protagonist|hero|heroine|narrator|speaker|presenter|host|expert|customer|client|user|employee|manager|CEO|founder|leader|teacher|student|doctor|nurse|chef|artist)\b/i',
            // Personal pronouns with context (he, she, they followed by verbs)
            '/\b(he|she|they)\s+(is|are|was|were|walks?|runs?|speaks?|says?|looks?|stands?|sits?)\b/i',
            // A/The person descriptions
            '/\b(a|the)\s+(young|old|middle-aged|professional|business|confident|mysterious|elegant)\s+(man|woman|person|figure|individual)\b/i',
            // Proper names (capitalized words that could be names)
            '/\b([A-Z][a-z]+)\s+(says?|speaks?|walks?|looks?|appears?|enters?|exits?|stands?)\b/',
        ];

        foreach ($this->script['scenes'] as $sceneIndex => $scene) {
            $sceneText = '';

            // Combine all text sources
            if (!empty($scene['narration'])) {
                $sceneText .= ' ' . $scene['narration'];
            }
            if (!empty($scene['visualDescription'])) {
                $sceneText .= ' ' . $scene['visualDescription'];
            }
            if (!empty($scene['visual'])) {
                $sceneText .= ' ' . $scene['visual'];
            }

            // Check for dialogue speakers
            if (isset($scene['dialogue']) && is_array($scene['dialogue'])) {
                foreach ($scene['dialogue'] as $dialogue) {
                    $speaker = $dialogue['speaker'] ?? null;
                    if ($speaker) {
                        $normalizedName = ucfirst(strtolower(trim($speaker)));
                        if (!isset($detectedCharacters[$normalizedName])) {
                            $detectedCharacters[$normalizedName] = [
                                'name' => $normalizedName,
                                'description' => '',
                                'source' => 'dialogue',
                            ];
                            $characterScenes[$normalizedName] = [];
                        }
                        if (!in_array($sceneIndex, $characterScenes[$normalizedName])) {
                            $characterScenes[$normalizedName][] = $sceneIndex;
                        }
                    }
                }
            }

            // Detect characters from text patterns
            foreach ($characterPatterns as $pattern) {
                if (preg_match_all($pattern, $sceneText, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $characterName = $this->normalizeCharacterName($match[0]);
                        if ($characterName && strlen($characterName) > 2) {
                            if (!isset($detectedCharacters[$characterName])) {
                                $detectedCharacters[$characterName] = [
                                    'name' => $characterName,
                                    'description' => $this->inferCharacterDescription($sceneText, $characterName),
                                    'source' => 'pattern',
                                ];
                                $characterScenes[$characterName] = [];
                            }
                            if (!in_array($sceneIndex, $characterScenes[$characterName])) {
                                $characterScenes[$characterName][] = $sceneIndex;
                            }
                        }
                    }
                }
            }
        }

        // Add detected characters to Character Bible
        foreach ($detectedCharacters as $name => $data) {
            // Check if already exists
            $exists = collect($this->sceneMemory['characterBible']['characters'])
                ->where('name', $name)
                ->isNotEmpty();

            if (!$exists) {
                $this->sceneMemory['characterBible']['characters'][] = [
                    'id' => uniqid('char_'),
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'appliedScenes' => $characterScenes[$name] ?? [],
                    'referenceImage' => null,
                    'autoDetected' => true,
                    'patternMatched' => true,
                ];
            }
        }

        // Enable Character Bible if we detected any characters
        if (!empty($detectedCharacters)) {
            $this->sceneMemory['characterBible']['enabled'] = true;
        }

        // Dispatch event for debugging
        $this->dispatch('vw-debug', [
            'type' => 'character_extraction',
            'method' => 'pattern',
            'count' => count($detectedCharacters),
        ]);
    }

    /**
     * Normalize character name from pattern match.
     */
    protected function normalizeCharacterName(string $match): ?string
    {
        // Remove articles and clean up
        $name = preg_replace('/^(the|a|an)\s+/i', '', trim($match));
        $name = preg_replace('/\s+(says?|speaks?|walks?|looks?|appears?|enters?|exits?|stands?|sits?|is|are|was|were|runs?).*$/i', '', $name);
        $name = trim($name);

        // Capitalize properly
        $name = ucwords(strtolower($name));

        // Skip if too short or just a pronoun
        $skipWords = ['he', 'she', 'they', 'it', 'we', 'you', 'i'];
        if (strlen($name) < 3 || in_array(strtolower($name), $skipWords)) {
            return null;
        }

        return $name;
    }

    /**
     * Infer character description from context.
     */
    protected function inferCharacterDescription(string $text, string $characterName): string
    {
        // Look for descriptive phrases near the character name
        $patterns = [
            '/\b' . preg_quote($characterName, '/') . '\s*,?\s*(a\s+)?([\w\s]+(?:man|woman|person|figure))/i',
            '/\b([\w\s]+(?:man|woman|person))\s+(?:named|called)\s+' . preg_quote($characterName, '/') . '/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                return ucfirst(trim($match[0]));
            }
        }

        return ''; // Return empty if no description found
    }

    /**
     * Auto-detect locations from script content using AI extraction.
     * Falls back to pattern matching if AI fails.
     */
    protected function autoDetectLocationsFromScript(): void
    {
        // Try AI-powered extraction first
        try {
            $service = app(LocationExtractionService::class);

            $result = $service->extractLocations($this->script, [
                'teamId' => session('current_team_id', 0),
                'genre' => $this->productionType ?? 'General',
                'productionType' => $this->productionType,
                'productionMode' => 'standard',
                'styleBible' => $this->sceneMemory['styleBible'] ?? null,
                'visualMode' => $this->getVisualMode(), // Master visual mode enforcement
            ]);

            if ($result['success'] && !empty($result['locations'])) {
                Log::info('LocationExtraction: AI extraction successful', [
                    'count' => count($result['locations']),
                ]);

                // Add AI-extracted locations to Location Bible
                foreach ($result['locations'] as $location) {
                    // Check if already exists
                    $exists = collect($this->sceneMemory['locationBible']['locations'])
                        ->filter(fn($loc) => strtolower($loc['name'] ?? '') === strtolower($location['name']))
                        ->isNotEmpty();

                    if (!$exists) {
                        $this->sceneMemory['locationBible']['locations'][] = [
                            'id' => $location['id'] ?? uniqid('loc_'),
                            'name' => $location['name'],
                            'description' => $location['description'] ?? '',
                            'type' => $location['type'] ?? 'exterior',
                            'timeOfDay' => $location['timeOfDay'] ?? 'day',
                            'weather' => $location['weather'] ?? 'clear',
                            'atmosphere' => $location['atmosphere'] ?? '',
                            'scenes' => $location['scenes'] ?? [],
                            'stateChanges' => $location['stateChanges'] ?? [],
                            'referenceImage' => null,
                            'autoDetected' => true,
                            'aiGenerated' => true,
                        ];
                    }
                }

                // Enable Location Bible if we detected any locations
                if (!empty($result['locations'])) {
                    $this->sceneMemory['locationBible']['enabled'] = true;
                }

                // Dispatch event for debugging
                $this->dispatch('vw-debug', [
                    'type' => 'location_extraction',
                    'method' => 'ai',
                    'count' => count($result['locations']),
                ]);

                return; // AI extraction successful, no need for fallback
            }

            // If AI returned no locations but was successful, video might be abstract
            if ($result['success'] && empty($result['locations'])) {
                Log::info('LocationExtraction: AI determined no distinct locations in video');
                $this->dispatch('vw-debug', [
                    'type' => 'location_extraction',
                    'method' => 'ai',
                    'count' => 0,
                    'message' => 'No distinct locations detected',
                ]);
                return;
            }

        } catch (\Exception $e) {
            Log::warning('LocationExtraction: AI extraction failed, falling back to pattern matching', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to pattern-based detection
        $this->autoDetectLocationsWithPatterns();
    }

    /**
     * Pattern-based location detection (fallback method).
     */
    protected function autoDetectLocationsWithPatterns(): void
    {
        $locationMap = [];

        foreach ($this->script['scenes'] as $sceneIndex => $scene) {
            $visual = $scene['visualDescription'] ?? $scene['visual'] ?? '';
            $narration = $scene['narration'] ?? '';
            $fullText = $visual . ' ' . $narration;

            if (empty(trim($fullText))) {
                continue;
            }

            // Infer location from visual description
            $locationName = $this->inferLocationFromVisual($fullText);
            $normalizedName = strtolower(trim($locationName));

            if ($locationName && $locationName !== 'Unknown') {
                if (!isset($locationMap[$normalizedName])) {
                    $locationMap[$normalizedName] = [
                        'name' => $locationName,
                        'type' => $this->inferLocationType($fullText),
                        'timeOfDay' => $this->inferTimeOfDay($fullText),
                        'weather' => $this->inferWeather($fullText),
                        'description' => $this->extractLocationDescription($visual),
                        'scenes' => [],
                    ];
                }
                $locationMap[$normalizedName]['scenes'][] = $sceneIndex;
            }
        }

        // Add detected locations to Location Bible
        foreach ($locationMap as $normalizedName => $data) {
            // Check if already exists
            $exists = collect($this->sceneMemory['locationBible']['locations'])
                ->filter(fn($loc) => strtolower($loc['name'] ?? '') === $normalizedName)
                ->isNotEmpty();

            if (!$exists) {
                $this->sceneMemory['locationBible']['locations'][] = [
                    'id' => uniqid('loc_'),
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'timeOfDay' => $data['timeOfDay'],
                    'weather' => $data['weather'],
                    'description' => $data['description'],
                    'scenes' => $data['scenes'],
                    'referenceImage' => null,
                    'autoDetected' => true,
                    'patternMatched' => true,
                ];
            }
        }

        // Enable Location Bible if we detected any locations
        if (!empty($locationMap)) {
            $this->sceneMemory['locationBible']['enabled'] = true;
        }

        // Dispatch event for debugging
        $this->dispatch('vw-debug', [
            'type' => 'location_extraction',
            'method' => 'pattern',
            'count' => count($locationMap),
        ]);
    }

    /**
     * Infer location name from visual description using pattern matching.
     * Based on the original video-creation-wizard LOCATION_BIBLE_GENERATOR.inferLocationFromVisual
     */
    protected function inferLocationFromVisual(string $visual): string
    {
        if (empty($visual)) {
            return 'Unknown';
        }

        $v = strtolower($visual);

        // Location patterns (most specific first)
        $locationPatterns = [
            ['pattern' => '/\b(dojo|training hall|martial arts)\b/i', 'name' => 'The Dojo'],
            ['pattern' => '/\b(boardroom|conference room|meeting room)\b/i', 'name' => 'Boardroom'],
            ['pattern' => '/\b(office|corporate|workspace|desk)\b/i', 'name' => 'Office'],
            ['pattern' => '/\b(warehouse|factory|industrial|abandoned building)\b/i', 'name' => 'Warehouse'],
            ['pattern' => '/\b(forest|woods|trees|jungle|nature trail)\b/i', 'name' => 'Forest'],
            ['pattern' => '/\b(street|alley|urban|city|downtown|sidewalk)\b/i', 'name' => 'City Streets'],
            ['pattern' => '/\b(rooftop|roof|skyline|terrace)\b/i', 'name' => 'Rooftop'],
            ['pattern' => '/\b(beach|shore|coast|ocean|sea|waves)\b/i', 'name' => 'Beach'],
            ['pattern' => '/\b(lab|laboratory|research|science|experiment)\b/i', 'name' => 'Laboratory'],
            ['pattern' => '/\b(home|house|apartment|living room|bedroom|kitchen)\b/i', 'name' => 'Home Interior'],
            ['pattern' => '/\b(hospital|medical|clinic|emergency room)\b/i', 'name' => 'Hospital'],
            ['pattern' => '/\b(bar|pub|club|restaurant|cafe|coffee shop)\b/i', 'name' => 'Restaurant/Bar'],
            ['pattern' => '/\b(castle|fortress|palace|throne|medieval)\b/i', 'name' => 'Castle'],
            ['pattern' => '/\b(cave|cavern|underground|tunnel)\b/i', 'name' => 'Cave'],
            ['pattern' => '/\b(ship|boat|vessel|deck|yacht|cruise)\b/i', 'name' => 'Ship/Boat'],
            ['pattern' => '/\b(mountain|peak|summit|cliff|hiking)\b/i', 'name' => 'Mountain'],
            ['pattern' => '/\b(park|garden|lawn|outdoor|backyard)\b/i', 'name' => 'Park/Garden'],
            ['pattern' => '/\b(studio|stage|set|backdrop|production)\b/i', 'name' => 'Studio'],
            ['pattern' => '/\b(gym|fitness|workout|training|exercise)\b/i', 'name' => 'Gym'],
            ['pattern' => '/\b(school|classroom|university|campus|lecture)\b/i', 'name' => 'School/University'],
            ['pattern' => '/\b(airport|terminal|airplane|flight|gate)\b/i', 'name' => 'Airport'],
            ['pattern' => '/\b(hotel|lobby|reception|suite|resort)\b/i', 'name' => 'Hotel'],
            ['pattern' => '/\b(store|shop|retail|mall|shopping)\b/i', 'name' => 'Retail Store'],
            ['pattern' => '/\b(highway|road|driving|car|vehicle)\b/i', 'name' => 'Highway/Road'],
            ['pattern' => '/\b(desert|sand|dunes|arid|dry)\b/i', 'name' => 'Desert'],
            ['pattern' => '/\b(space|spacecraft|spaceship|stars|galaxy|cosmos)\b/i', 'name' => 'Space'],
            ['pattern' => '/\b(farm|barn|rural|countryside|fields|crops)\b/i', 'name' => 'Farm/Rural'],
        ];

        foreach ($locationPatterns as $item) {
            if (preg_match($item['pattern'], $v)) {
                return $item['name'];
            }
        }

        return 'General Location';
    }

    /**
     * Infer location type (interior/exterior) from text.
     */
    protected function inferLocationType(string $text): string
    {
        $t = strtolower($text);

        $interiorKeywords = ['inside', 'interior', 'indoor', 'room', 'office', 'home', 'building', 'house', 'apartment', 'studio', 'lab', 'hospital', 'hotel', 'restaurant', 'bar', 'store', 'mall'];
        $exteriorKeywords = ['outside', 'exterior', 'outdoor', 'street', 'park', 'beach', 'mountain', 'forest', 'ocean', 'sky', 'rooftop', 'desert', 'highway', 'road'];

        foreach ($interiorKeywords as $keyword) {
            if (strpos($t, $keyword) !== false) {
                return 'interior';
            }
        }

        foreach ($exteriorKeywords as $keyword) {
            if (strpos($t, $keyword) !== false) {
                return 'exterior';
            }
        }

        return 'exterior'; // Default to exterior
    }

    /**
     * Infer time of day from text context.
     * Based on original video-creation-wizard LOCATION_BIBLE_GENERATOR.inferTimeOfDay
     */
    protected function inferTimeOfDay(string $text): string
    {
        $t = strtolower($text);

        if (preg_match('/\b(dawn|sunrise|first light|early morning)\b/', $t)) {
            return 'dawn';
        }
        if (preg_match('/\b(morning|bright day|fresh day)\b/', $t)) {
            return 'day';
        }
        if (preg_match('/\b(noon|midday|harsh sun|overhead sun)\b/', $t)) {
            return 'day';
        }
        if (preg_match('/\b(afternoon|warm light|late day)\b/', $t)) {
            return 'day';
        }
        if (preg_match('/\b(golden hour|sunset|orange light|evening sun)\b/', $t)) {
            return 'golden-hour';
        }
        if (preg_match('/\b(dusk|twilight|fading light)\b/', $t)) {
            return 'dusk';
        }
        if (preg_match('/\b(night|darkness|moonlight|stars|neon|midnight|deep night)\b/', $t)) {
            return 'night';
        }

        return 'day'; // Default
    }

    /**
     * Infer weather from text context.
     * Based on original video-creation-wizard LOCATION_BIBLE_GENERATOR.inferWeather
     */
    protected function inferWeather(string $text): string
    {
        $t = strtolower($text);

        if (preg_match('/\b(storm|thunder|lightning)\b/', $t)) {
            return 'stormy';
        }
        if (preg_match('/\b(heavy rain|downpour|torrential)\b/', $t)) {
            return 'rainy';
        }
        if (preg_match('/\b(rain|drizzle|wet|raining)\b/', $t)) {
            return 'rainy';
        }
        if (preg_match('/\b(fog|mist|haze|foggy)\b/', $t)) {
            return 'foggy';
        }
        if (preg_match('/\b(snow|blizzard|frost|winter|cold)\b/', $t)) {
            return 'snowy';
        }
        if (preg_match('/\b(cloudy|overcast|grey sky)\b/', $t)) {
            return 'cloudy';
        }
        if (preg_match('/\b(sunny|bright|clear sky|blue sky)\b/', $t)) {
            return 'clear';
        }

        return 'clear'; // Default
    }

    /**
     * Extract location description from visual text.
     */
    protected function extractLocationDescription(string $visual): string
    {
        // Clean up and truncate for description
        $description = trim($visual);

        if (strlen($description) > 200) {
            $description = substr($description, 0, 197) . '...';
        }

        return $description;
    }

    // =========================================================================
    // CHARACTER BIBLE METHODS
    // =========================================================================

    /**
     * Auto-detect characters from script.
     */
    public function autoDetectCharacters(): void
    {
        // Use AI-powered extraction (with pattern fallback)
        $this->autoDetectCharactersFromScript();
        $this->saveProject();
    }

    /**
     * Edit a character.
     */
    public function editCharacter(int $index): void
    {
        $this->editingCharacterIndex = $index;
    }

    /**
     * Open Character Bible modal.
     */
    public function openCharacterBibleModal(): void
    {
        $this->showCharacterBibleModal = true;
        $this->editingCharacterIndex = 0;
    }

    /**
     * Close Character Bible modal.
     */
    public function closeCharacterBibleModal(): void
    {
        $this->showCharacterBibleModal = false;
    }

    /**
     * Toggle character scene assignment.
     */
    public function toggleCharacterScene(int $charIndex, int $sceneIndex): void
    {
        $appliedScenes = $this->sceneMemory['characterBible']['characters'][$charIndex]['appliedScenes'] ?? [];

        if (in_array($sceneIndex, $appliedScenes)) {
            $this->sceneMemory['characterBible']['characters'][$charIndex]['appliedScenes'] = array_values(
                array_diff($appliedScenes, [$sceneIndex])
            );
        } else {
            $this->sceneMemory['characterBible']['characters'][$charIndex]['appliedScenes'][] = $sceneIndex;
        }

        $this->saveProject();
    }

    /**
     * Apply character to all scenes.
     */
    public function applyCharacterToAllScenes(int $charIndex): void
    {
        $sceneCount = count($this->script['scenes'] ?? []);
        $this->sceneMemory['characterBible']['characters'][$charIndex]['appliedScenes'] = range(0, $sceneCount - 1);
        $this->saveProject();
    }

    /**
     * Remove character portrait.
     */
    public function removeCharacterPortrait(int $index): void
    {
        $this->sceneMemory['characterBible']['characters'][$index]['referenceImage'] = null;
        $this->sceneMemory['characterBible']['characters'][$index]['referenceImageBase64'] = null;
        $this->sceneMemory['characterBible']['characters'][$index]['referenceImageMimeType'] = null;
        $this->sceneMemory['characterBible']['characters'][$index]['referenceImageStatus'] = 'none';
        $this->saveProject();
    }

    /**
     * Upload a reference image for a character.
     */
    public function uploadCharacterPortrait(int $index): void
    {
        if (!$this->characterImageUpload) {
            return;
        }

        $this->validate([
            'characterImageUpload' => 'image|max:10240', // 10MB max
        ]);

        try {
            // Generate unique filename
            $filename = 'character_' . uniqid() . '_' . time() . '.' . $this->characterImageUpload->getClientOriginalExtension();

            // Store in public disk under wizard-assets
            $path = $this->characterImageUpload->storeAs('wizard-assets/characters', $filename, 'public');

            // Get the public URL
            $url = \Storage::disk('public')->url($path);

            // Read file as base64 for API calls (character face consistency)
            $base64Data = base64_encode(file_get_contents($this->characterImageUpload->getRealPath()));
            $mimeType = $this->characterImageUpload->getMimeType() ?? 'image/png';

            // Update character with the uploaded image
            $this->sceneMemory['characterBible']['characters'][$index]['referenceImage'] = $url;
            $this->sceneMemory['characterBible']['characters'][$index]['referenceImageSource'] = 'upload';
            $this->sceneMemory['characterBible']['characters'][$index]['referenceImageBase64'] = $base64Data;
            $this->sceneMemory['characterBible']['characters'][$index]['referenceImageMimeType'] = $mimeType;
            $this->sceneMemory['characterBible']['characters'][$index]['referenceImageStatus'] = 'ready';

            // Clear the upload
            $this->characterImageUpload = null;

            $this->saveProject();

            // Dispatch debug event
            $this->dispatch('vw-debug', [
                'type' => 'character_image_upload',
                'characterIndex' => $index,
                'filename' => $filename,
                'hasBase64' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Character image upload failed', ['error' => $e->getMessage()]);
            $this->error = __('Failed to upload image: ') . $e->getMessage();
        }
    }

    /**
     * Apply character template.
     */
    public function applyCharacterTemplate(int $index, string $template): void
    {
        $templates = [
            'action-hero' => 'Athletic build, confident stance, determined expression, wearing practical tactical clothing, strong jawline, focused eyes',
            'tech-pro' => 'Smart casual attire, clean-cut appearance, glasses optional, modern hairstyle, professional demeanor, laptop or tablet nearby',
            'mysterious' => 'Dark clothing, partially obscured face, enigmatic expression, shadows across features, subtle accessories, intriguing presence',
            'narrator' => 'Friendly approachable appearance, warm smile, neutral professional clothing, trustworthy expression, good lighting on face',
        ];

        if (isset($templates[$template])) {
            $this->sceneMemory['characterBible']['characters'][$index]['description'] = $templates[$template];
            $this->saveProject();
        }
    }

    /**
     * Generate character portrait.
     */
    public function generateCharacterPortrait(int $index): void
    {
        $character = $this->sceneMemory['characterBible']['characters'][$index] ?? null;
        if (!$character) return;

        $this->isLoading = true;
        $this->error = null;

        // Mark as generating
        $this->sceneMemory['characterBible']['characters'][$index]['referenceImageStatus'] = 'generating';

        try {
            $imageService = app(ImageGenerationService::class);

            // Build portrait-optimized prompt (matching reference implementation)
            $promptParts = [
                'Professional studio portrait photograph',
                $character['description'],
                'Standing pose facing camera',
                'Clean pure white background',
                'Professional studio lighting with soft shadows',
                'High quality, detailed, sharp focus',
                'Full body visible from head to feet',
                'Neutral expression, confident pose',
                'Fashion photography style, catalog quality',
            ];
            $prompt = implode('. ', $promptParts);

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    $result = $imageService->generateSceneImage($project, [
                        'id' => $character['id'],
                        'visualDescription' => $prompt,
                    ], [
                        'model' => 'nanobanana-pro',
                        'sceneIndex' => null, // Portraits don't belong to any scene
                    ]);

                    if ($result['success'] && isset($result['imageUrl'])) {
                        $imageUrl = $result['imageUrl'];
                        $this->sceneMemory['characterBible']['characters'][$index]['referenceImage'] = $imageUrl;
                        $this->sceneMemory['characterBible']['characters'][$index]['referenceImageSource'] = 'ai';

                        // Fetch image as base64 for face consistency in scene generation
                        try {
                            $imageContent = file_get_contents($imageUrl);
                            if ($imageContent !== false) {
                                $base64Data = base64_encode($imageContent);
                                // Detect MIME type from image content
                                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                                $mimeType = $finfo->buffer($imageContent) ?: 'image/png';

                                $this->sceneMemory['characterBible']['characters'][$index]['referenceImageBase64'] = $base64Data;
                                $this->sceneMemory['characterBible']['characters'][$index]['referenceImageMimeType'] = $mimeType;
                                $this->sceneMemory['characterBible']['characters'][$index]['referenceImageStatus'] = 'ready';

                                Log::info('Character portrait generated with base64', [
                                    'characterIndex' => $index,
                                    'base64Length' => strlen($base64Data),
                                    'mimeType' => $mimeType,
                                ]);
                            }
                        } catch (\Exception $fetchError) {
                            Log::warning('Could not fetch image as base64', ['error' => $fetchError->getMessage()]);
                            // Still mark as ready even if base64 fetch failed
                            $this->sceneMemory['characterBible']['characters'][$index]['referenceImageStatus'] = 'ready';
                        }

                        $this->saveProject();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error = __('Failed to generate portrait: ') . $e->getMessage();
            $this->sceneMemory['characterBible']['characters'][$index]['referenceImageStatus'] = 'error';
        } finally {
            $this->isLoading = false;
        }
    }

    // =========================================================================
    // STYLE BIBLE REFERENCE IMAGE METHODS
    // =========================================================================

    /**
     * Upload a reference image for Style Bible.
     */
    public function uploadStyleReference(): void
    {
        if (!$this->styleImageUpload) {
            return;
        }

        $this->validate([
            'styleImageUpload' => 'image|max:10240', // 10MB max
        ]);

        try {
            // Generate unique filename
            $filename = 'style_ref_' . uniqid() . '_' . time() . '.' . $this->styleImageUpload->getClientOriginalExtension();

            // Store in public disk under wizard-assets
            $path = $this->styleImageUpload->storeAs('wizard-assets/styles', $filename, 'public');

            // Get the public URL
            $url = \Storage::disk('public')->url($path);

            // Read file as base64 for API calls (style consistency)
            $base64Data = base64_encode(file_get_contents($this->styleImageUpload->getRealPath()));
            $mimeType = $this->styleImageUpload->getMimeType() ?? 'image/png';

            // Update Style Bible with the uploaded image
            $this->sceneMemory['styleBible']['referenceImage'] = $url;
            $this->sceneMemory['styleBible']['referenceImageSource'] = 'upload';
            $this->sceneMemory['styleBible']['referenceImageBase64'] = $base64Data;
            $this->sceneMemory['styleBible']['referenceImageMimeType'] = $mimeType;
            $this->sceneMemory['styleBible']['referenceImageStatus'] = 'ready';

            // Clear the upload
            $this->styleImageUpload = null;

            $this->saveProject();

            Log::info('Style Bible reference uploaded with base64', [
                'base64Length' => strlen($base64Data),
                'mimeType' => $mimeType,
            ]);
        } catch (\Exception $e) {
            $this->error = __('Failed to upload style reference: ') . $e->getMessage();
        }
    }

    /**
     * Generate style reference image based on Style Bible settings.
     */
    public function generateStyleReference(): void
    {
        $styleBible = $this->sceneMemory['styleBible'] ?? [];

        // Build prompt from style bible fields
        $parts = [];
        if (!empty($styleBible['style'])) {
            $parts[] = $styleBible['style'];
        }
        if (!empty($styleBible['colorGrade'])) {
            $parts[] = $styleBible['colorGrade'];
        }
        if (!empty($styleBible['atmosphere'])) {
            $parts[] = $styleBible['atmosphere'];
        }
        if (!empty($styleBible['camera'])) {
            $parts[] = $styleBible['camera'];
        }
        if (!empty($styleBible['visualDNA'])) {
            $parts[] = $styleBible['visualDNA'];
        }

        if (empty($parts)) {
            $this->error = __('Please fill in some style settings before generating a reference.');
            return;
        }

        $this->isGeneratingStyleRef = true;
        $this->error = null;

        // Mark as generating
        $this->sceneMemory['styleBible']['referenceImageStatus'] = 'generating';

        try {
            $imageService = app(ImageGenerationService::class);

            // Build style reference prompt
            $prompt = "Style reference, visual mood board, " . implode(', ', $parts);
            $prompt .= ", cinematic, artistic composition, reference image";

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    $result = $imageService->generateSceneImage($project, [
                        'id' => 'style_ref_' . uniqid(),
                        'visualDescription' => $prompt,
                    ], [
                        'model' => 'nanobanana-pro',
                        'sceneIndex' => null, // Style references don't belong to any scene
                    ]);

                    if ($result['success'] && isset($result['imageUrl'])) {
                        $imageUrl = $result['imageUrl'];
                        $this->sceneMemory['styleBible']['referenceImage'] = $imageUrl;
                        $this->sceneMemory['styleBible']['referenceImageSource'] = 'ai';

                        // Fetch image as base64 for style consistency in scene generation
                        try {
                            $imageContent = file_get_contents($imageUrl);
                            if ($imageContent !== false) {
                                $base64Data = base64_encode($imageContent);
                                // Detect MIME type from image content
                                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                                $mimeType = $finfo->buffer($imageContent) ?: 'image/png';

                                $this->sceneMemory['styleBible']['referenceImageBase64'] = $base64Data;
                                $this->sceneMemory['styleBible']['referenceImageMimeType'] = $mimeType;
                                $this->sceneMemory['styleBible']['referenceImageStatus'] = 'ready';

                                Log::info('Style Bible reference generated with base64', [
                                    'base64Length' => strlen($base64Data),
                                    'mimeType' => $mimeType,
                                ]);
                            }
                        } catch (\Exception $fetchError) {
                            Log::warning('Could not fetch style reference as base64', ['error' => $fetchError->getMessage()]);
                            // Still mark as ready even if base64 fetch failed
                            $this->sceneMemory['styleBible']['referenceImageStatus'] = 'ready';
                        }

                        $this->saveProject();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error = __('Failed to generate style reference: ') . $e->getMessage();
            $this->sceneMemory['styleBible']['referenceImageStatus'] = 'error';
        } finally {
            $this->isGeneratingStyleRef = false;
        }
    }

    /**
     * Remove style reference image.
     */
    public function removeStyleReference(): void
    {
        $this->sceneMemory['styleBible']['referenceImage'] = '';
        $this->sceneMemory['styleBible']['referenceImageSource'] = '';
        $this->sceneMemory['styleBible']['referenceImageBase64'] = null;
        $this->sceneMemory['styleBible']['referenceImageMimeType'] = null;
        $this->sceneMemory['styleBible']['referenceImageStatus'] = 'none';
        $this->saveProject();
    }

    // =========================================================================
    // LOCATION BIBLE METHODS
    // =========================================================================

    /**
     * Auto-detect locations from script.
     */
    public function autoDetectLocations(): void
    {
        // Use AI-powered extraction (with pattern fallback)
        $this->autoDetectLocationsFromScript();
        $this->saveProject();
    }

    /**
     * Apply location template.
     */
    public function applyLocationTemplate(string $template): void
    {
        $templates = [
            'urban' => [
                'name' => 'Urban City',
                'type' => 'exterior',
                'timeOfDay' => 'night',
                'weather' => 'clear',
                'mood' => 'energetic',
                'description' => 'Modern cityscape, tall buildings, neon lights, busy streets, urban environment',
                'lightingStyle' => 'Neon signs with wet surface reflections',
            ],
            'urban-night' => [
                'name' => 'Urban Night',
                'type' => 'exterior',
                'timeOfDay' => 'night',
                'weather' => 'clear',
                'mood' => 'mysterious',
                'description' => 'Dark city streets at night, neon signs, rain-slicked pavement, atmospheric fog, cyberpunk aesthetic',
                'lightingStyle' => 'Neon signs reflecting on wet surfaces, dramatic shadows, colorful lighting',
            ],
            'forest' => [
                'name' => 'Forest',
                'type' => 'exterior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'mood' => 'peaceful',
                'description' => 'Dense forest, tall trees, dappled sunlight, natural environment, lush vegetation',
                'lightingStyle' => 'Dappled sunlight through leaves, natural golden light',
            ],
            'tech-lab' => [
                'name' => 'Tech Lab',
                'type' => 'interior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'mood' => 'neutral',
                'description' => 'High-tech laboratory, advanced equipment, holographic displays, sterile white surfaces, futuristic design',
                'lightingStyle' => 'Clean LED lighting, blue accent lights, holographic glow',
            ],
            'desert' => [
                'name' => 'Desert Sunset',
                'type' => 'exterior',
                'timeOfDay' => 'golden-hour',
                'weather' => 'clear',
                'mood' => 'peaceful',
                'description' => 'Vast desert landscape, sand dunes, dramatic sunset sky, warm orange and red colors, endless horizon',
                'lightingStyle' => 'Golden hour warmth, long shadows, dramatic sky colors',
            ],
            'industrial' => [
                'name' => 'Industrial',
                'type' => 'interior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'mood' => 'tense',
                'description' => 'Industrial factory interior, metal structures, pipes, machinery, warehouse atmosphere, gritty textures',
                'lightingStyle' => 'Harsh overhead lighting, dramatic shadows, dust particles in light',
            ],
            'space' => [
                'name' => 'Space Station',
                'type' => 'interior',
                'timeOfDay' => 'night',
                'weather' => 'clear',
                'mood' => 'mysterious',
                'description' => 'Futuristic space station interior, curved corridors, control panels, view of stars through windows, zero-gravity elements',
                'lightingStyle' => 'Ambient blue-white lighting, starlight through windows, holographic displays',
            ],
            'office' => [
                'name' => 'Office',
                'type' => 'interior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'mood' => 'neutral',
                'description' => 'Modern office interior, clean design, glass walls, professional workspace',
                'lightingStyle' => 'Soft diffused lighting, natural light from windows',
            ],
            'studio' => [
                'name' => 'Studio',
                'type' => 'interior',
                'timeOfDay' => 'day',
                'weather' => 'clear',
                'mood' => 'neutral',
                'description' => 'Professional studio setup, controlled lighting, clean backdrop, production environment',
                'lightingStyle' => 'Three-point lighting setup, controlled studio lights',
            ],
        ];

        if (isset($templates[$template])) {
            $this->sceneMemory['locationBible']['locations'][] = array_merge(
                ['id' => uniqid('loc_'), 'referenceImage' => null, 'referenceImageStatus' => null],
                $templates[$template]
            );
            $this->editingLocationIndex = count($this->sceneMemory['locationBible']['locations']) - 1;
            $this->saveProject();
        }
    }

    /**
     * Edit a location.
     */
    public function editLocation(int $index): void
    {
        $this->editingLocationIndex = $index;
    }

    /**
     * Open Location Bible modal.
     */
    public function openLocationBibleModal(): void
    {
        $this->showLocationBibleModal = true;
        if (empty($this->sceneMemory['locationBible']['locations'])) {
            $this->addLocation('New Location', '');
        }
        $this->editingLocationIndex = 0;
    }

    /**
     * Close Location Bible modal.
     */
    public function closeLocationBibleModal(): void
    {
        $this->showLocationBibleModal = false;
        $this->saveProject();
    }

    /**
     * Toggle location assignment to a scene.
     */
    public function toggleLocationScene(int $locIndex, int $sceneIndex): void
    {
        if (!isset($this->sceneMemory['locationBible']['locations'][$locIndex])) {
            return;
        }

        $scenes = $this->sceneMemory['locationBible']['locations'][$locIndex]['scenes'] ?? [];

        if (in_array($sceneIndex, $scenes)) {
            $scenes = array_values(array_filter($scenes, fn($s) => $s !== $sceneIndex));
        } else {
            $scenes[] = $sceneIndex;
        }

        $this->sceneMemory['locationBible']['locations'][$locIndex]['scenes'] = $scenes;
        $this->saveProject();
    }

    /**
     * Apply location to all scenes.
     */
    public function applyLocationToAllScenes(int $locIndex): void
    {
        if (!isset($this->sceneMemory['locationBible']['locations'][$locIndex])) {
            return;
        }

        $sceneCount = count($this->storyboard['scenes'] ?? []);
        $this->sceneMemory['locationBible']['locations'][$locIndex]['scenes'] = range(0, $sceneCount - 1);
        $this->saveProject();
    }

    /**
     * Remove location reference image.
     */
    public function removeLocationReference(int $index): void
    {
        if (isset($this->sceneMemory['locationBible']['locations'][$index])) {
            $this->sceneMemory['locationBible']['locations'][$index]['referenceImage'] = null;
            $this->sceneMemory['locationBible']['locations'][$index]['referenceImageBase64'] = null;
            $this->sceneMemory['locationBible']['locations'][$index]['referenceImageMimeType'] = null;
            $this->sceneMemory['locationBible']['locations'][$index]['referenceImageStatus'] = 'none';
            $this->saveProject();
        }
    }

    /**
     * Upload a reference image for a location.
     */
    public function uploadLocationReference(int $index): void
    {
        if (!$this->locationImageUpload) {
            return;
        }

        $this->validate([
            'locationImageUpload' => 'image|max:10240', // 10MB max
        ]);

        try {
            // Generate unique filename
            $filename = 'location_' . uniqid() . '_' . time() . '.' . $this->locationImageUpload->getClientOriginalExtension();

            // Store in public disk under wizard-assets
            $path = $this->locationImageUpload->storeAs('wizard-assets/locations', $filename, 'public');

            // Get the public URL
            $url = \Storage::disk('public')->url($path);

            // Read file as base64 for API calls (location visual consistency)
            $base64Data = base64_encode(file_get_contents($this->locationImageUpload->getRealPath()));
            $mimeType = $this->locationImageUpload->getMimeType() ?? 'image/png';

            // Update location with the uploaded image
            $this->sceneMemory['locationBible']['locations'][$index]['referenceImage'] = $url;
            $this->sceneMemory['locationBible']['locations'][$index]['referenceImageSource'] = 'upload';
            $this->sceneMemory['locationBible']['locations'][$index]['referenceImageBase64'] = $base64Data;
            $this->sceneMemory['locationBible']['locations'][$index]['referenceImageMimeType'] = $mimeType;
            $this->sceneMemory['locationBible']['locations'][$index]['referenceImageStatus'] = 'ready';

            // Clear the upload
            $this->locationImageUpload = null;

            $this->saveProject();

            // Dispatch debug event
            $this->dispatch('vw-debug', [
                'type' => 'location_image_upload',
                'locationIndex' => $index,
                'filename' => $filename,
                'hasBase64' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Location image upload failed', ['error' => $e->getMessage()]);
            $this->error = __('Failed to upload image: ') . $e->getMessage();
        }
    }

    /**
     * Generate location reference.
     */
    public function generateLocationReference(int $index): void
    {
        $location = $this->sceneMemory['locationBible']['locations'][$index] ?? null;
        if (!$location) return;

        $this->isLoading = true;
        $this->error = null;

        // Mark as generating
        $this->sceneMemory['locationBible']['locations'][$index]['referenceImageStatus'] = 'generating';

        try {
            $imageService = app(ImageGenerationService::class);

            // Build location-optimized prompt
            $promptParts = [
                'Cinematic establishing shot',
                $location['description'],
                ucfirst($location['type']) . ' setting',
                ucfirst($location['timeOfDay']) . ' lighting',
            ];

            if (!empty($location['weather']) && $location['weather'] !== 'clear') {
                $promptParts[] = ucfirst($location['weather']) . ' weather conditions';
            }

            if (!empty($location['mood'])) {
                $promptParts[] = ucfirst($location['mood']) . ' atmosphere';
            }

            $promptParts = array_merge($promptParts, [
                'Wide angle composition',
                'High production value',
                'Professional cinematography',
                'No people or characters',
                'Empty environment reference shot',
            ]);
            $prompt = implode('. ', $promptParts);

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    $result = $imageService->generateSceneImage($project, [
                        'id' => $location['id'],
                        'visualDescription' => $prompt,
                    ], [
                        'model' => 'nanobanana-pro',
                        'sceneIndex' => null, // Location references don't belong to any scene
                    ]);

                    if ($result['success'] && isset($result['imageUrl'])) {
                        $imageUrl = $result['imageUrl'];
                        $this->sceneMemory['locationBible']['locations'][$index]['referenceImage'] = $imageUrl;
                        $this->sceneMemory['locationBible']['locations'][$index]['referenceImageSource'] = 'ai';

                        // Fetch image as base64 for location consistency in scene generation
                        try {
                            $imageContent = file_get_contents($imageUrl);
                            if ($imageContent !== false) {
                                $base64Data = base64_encode($imageContent);
                                // Detect MIME type from image content
                                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                                $mimeType = $finfo->buffer($imageContent) ?: 'image/png';

                                $this->sceneMemory['locationBible']['locations'][$index]['referenceImageBase64'] = $base64Data;
                                $this->sceneMemory['locationBible']['locations'][$index]['referenceImageMimeType'] = $mimeType;
                                $this->sceneMemory['locationBible']['locations'][$index]['referenceImageStatus'] = 'ready';

                                Log::info('Location reference generated with base64', [
                                    'locationIndex' => $index,
                                    'base64Length' => strlen($base64Data),
                                    'mimeType' => $mimeType,
                                ]);
                            }
                        } catch (\Exception $fetchError) {
                            Log::warning('Could not fetch location image as base64', ['error' => $fetchError->getMessage()]);
                            // Still mark as ready even if base64 fetch failed
                            $this->sceneMemory['locationBible']['locations'][$index]['referenceImageStatus'] = 'ready';
                        }

                        $this->saveProject();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error = __('Failed to generate location reference: ') . $e->getMessage();
            $this->sceneMemory['locationBible']['locations'][$index]['referenceImageStatus'] = 'error';
        } finally {
            $this->isLoading = false;
        }
    }

    // =========================================================================
    // MULTI-SHOT MODE METHODS
    // =========================================================================

    /**
     * Toggle multi-shot mode.
     */
    public function toggleMultiShotMode(): void
    {
        $this->multiShotMode['enabled'] = !$this->multiShotMode['enabled'];
        $this->saveProject();
    }

    /**
     * Set default shot count for multi-shot mode.
     */
    public function setMultiShotCount(int $count): void
    {
        $this->multiShotMode['defaultShotCount'] = max(2, min(6, $count));
        $this->multiShotCount = $this->multiShotMode['defaultShotCount'];
        $this->saveProject();
    }

    /**
     * Open multi-shot decomposition modal.
     */
    public function openMultiShotModal(int $sceneIndex): void
    {
        $this->multiShotSceneIndex = $sceneIndex;
        $this->multiShotCount = $this->multiShotMode['defaultShotCount'];
        $this->showMultiShotModal = true;
    }

    /**
     * Close multi-shot modal.
     */
    public function closeMultiShotModal(): void
    {
        $this->showMultiShotModal = false;
    }

    /**
     * Decompose scene into multiple shots.
     * Uses Hollywood Math: shots = sceneDuration / clipDuration
     */
    public function decomposeScene(int $sceneIndex): void
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            $scene = $this->script['scenes'][$sceneIndex] ?? null;
            if (!$scene) {
                throw new \Exception(__('Scene not found'));
            }

            // Get visual description for decomposition
            $visualDescription = $scene['visualDescription'] ?? $scene['visual'] ?? $scene['narration'] ?? '';

            // Hollywood-style timing from Phase 1
            $clipDuration = $this->getClipDuration(); // 5s, 6s, or 10s
            $sceneDuration = $scene['duration'] ?? ($this->script['timing']['sceneDuration'] ?? 35);

            // Hollywood Math: Calculate shot count based on scene duration and clip duration
            // For a 35s scene with 10s clips = ~4 shots
            $calculatedShotCount = max(2, min(6, (int) ceil($sceneDuration / $clipDuration)));

            // Use calculated count or user-selected count (whichever is appropriate)
            $shotCount = $this->multiShotCount > 0 ? $this->multiShotCount : $calculatedShotCount;
            $baseShotDuration = $clipDuration;

            $shots = [];
            $sceneId = $scene['id'] ?? 'scene_' . $sceneIndex;

            for ($i = 0; $i < $shotCount; $i++) {
                // Pass scene for narrative-based shot selection
                $shotType = $this->getShotTypeForIndex($i, $shotCount, $scene);
                $cameraMovement = $this->getCameraMovementForShot($shotType['type'], $i);

                // Build comprehensive shot structure matching original wizard schema
                $shots[] = [
                    // Identification
                    'id' => "shot-{$sceneId}-{$i}",
                    'sceneId' => $sceneId,
                    'index' => $i,

                    // Shot type and description
                    'type' => $shotType['type'],
                    'shotType' => $shotType['type'],
                    'description' => $shotType['description'],
                    'purpose' => $shotType['purpose'] ?? 'narrative',
                    'lens' => $shotType['lens'] ?? 'standard 50mm',

                    // Prompts for generation
                    'imagePrompt' => $this->buildShotPrompt($visualDescription, $shotType, $i),
                    'videoPrompt' => $this->getMotionDescriptionForShot($shotType['type'], $cameraMovement, $visualDescription),
                    'prompt' => $this->buildShotPrompt($visualDescription, $shotType, $i), // Legacy compat

                    // Image state
                    'imageUrl' => null,
                    'imageStatus' => 'pending',  // 'pending' | 'generating' | 'ready' | 'error'
                    'status' => 'pending',       // Legacy compat

                    // Video state
                    'videoUrl' => null,
                    'videoStatus' => 'pending',  // 'pending' | 'generating' | 'ready' | 'error'

                    // Frame chain (Hollywood-style shot continuity)
                    'fromSceneImage' => $i === 0,  // Shot 1 uses scene's main image
                    'fromFrameCapture' => $i > 0,  // Shots 2+ use previous shot's last frame
                    'capturedFrameUrl' => null,    // URL of captured frame for chaining

                    // Timing
                    'duration' => $baseShotDuration,
                    'selectedDuration' => $baseShotDuration,
                    'durationClass' => $baseShotDuration <= 5 ? 'short' : ($baseShotDuration <= 6 ? 'standard' : 'cinematic'),

                    // Camera movement
                    'cameraMovement' => $cameraMovement,

                    // Audio layer (for dialogue distribution)
                    'dialogue' => $this->getDialogueForShot($scene, $i, $shotCount),
                    'speakingCharacters' => [],

                    // Motion/Action description
                    'narrativeBeat' => [
                        'motionDescription' => $this->getMotionDescriptionForShot($shotType['type'], $cameraMovement, $visualDescription),
                    ],
                ];
            }

            // Calculate total duration for all shots
            $totalDuration = array_sum(array_column($shots, 'duration'));

            // Store decomposed scene with Hollywood-style structure
            $this->multiShotMode['decomposedScenes'][$sceneIndex] = [
                'sceneId' => $sceneId,
                'sceneIndex' => $sceneIndex,
                'shots' => $shots,
                'shotCount' => count($shots),
                'totalDuration' => $totalDuration,
                'selectedShot' => 0,
                'status' => 'ready',  // 'pending' | 'decomposing' | 'ready' | 'error'
                'consistencyAnchors' => [
                    'style' => $this->sceneMemory['styleBible']['style'] ?? '',
                    'characters' => $this->getCharactersForScene($sceneIndex),
                    'location' => $this->getLocationForScene($sceneIndex),
                ],
                // Scene metadata for reference
                'sceneTitle' => $scene['title'] ?? '',
                'sceneNarration' => $scene['narration'] ?? '',
            ];

            // If scene already has an image, use it for first shot
            $storyboardScene = $this->storyboard['scenes'][$sceneIndex] ?? null;
            if ($storyboardScene && !empty($storyboardScene['imageUrl'])) {
                $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][0]['imageUrl'] = $storyboardScene['imageUrl'];
                $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][0]['imageStatus'] = 'ready';
                $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][0]['status'] = 'ready';
            }

            $this->saveProject();
            $this->showMultiShotModal = false;

        } catch (\Exception $e) {
            $this->error = __('Failed to decompose scene: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Get shot type configuration based on index, total count, and narrative context.
     * Uses professional Hollywood shot sequencing patterns.
     */
    protected function getShotTypeForIndex(int $index, int $total, ?array $scene = null): array
    {
        // Professional shot type definitions with cinematic descriptions
        $shotTypes = [
            'establishing' => [
                'type' => 'establishing',
                'description' => 'Wide establishing shot showing full environment',
                'purpose' => 'context',
                'lens' => 'wide-angle 24mm',
            ],
            'wide' => [
                'type' => 'wide',
                'description' => 'Wide shot revealing full scene context',
                'purpose' => 'scope',
                'lens' => 'ultra-wide 16mm',
            ],
            'medium' => [
                'type' => 'medium',
                'description' => 'Medium shot focusing on main subject',
                'purpose' => 'narrative',
                'lens' => 'standard 50mm',
            ],
            'medium-close' => [
                'type' => 'medium-close',
                'description' => 'Medium close-up for dialogue and connection',
                'purpose' => 'intimacy',
                'lens' => '85mm portrait',
            ],
            'close-up' => [
                'type' => 'close-up',
                'description' => 'Close-up emphasizing emotion and detail',
                'purpose' => 'emotion',
                'lens' => 'telephoto 85mm, shallow depth of field',
            ],
            'extreme-close-up' => [
                'type' => 'extreme-close-up',
                'description' => 'Extreme close-up on critical detail',
                'purpose' => 'emphasis',
                'lens' => 'macro lens, extreme detail',
            ],
            'reaction' => [
                'type' => 'reaction',
                'description' => 'Reaction shot capturing emotional response',
                'purpose' => 'response',
                'lens' => '50mm standard',
            ],
            'detail' => [
                'type' => 'detail',
                'description' => 'Detail shot highlighting specific elements',
                'purpose' => 'focus',
                'lens' => 'macro or telephoto',
            ],
            'over-shoulder' => [
                'type' => 'over-shoulder',
                'description' => 'Over-the-shoulder shot for dialogue',
                'purpose' => 'conversation',
                'lens' => '35mm cinematic',
            ],
            'pov' => [
                'type' => 'pov',
                'description' => 'Point-of-view shot from character perspective',
                'purpose' => 'immersion',
                'lens' => 'wide 28mm',
            ],
        ];

        // Get scene emotional beat if available
        $emotionalBeat = $scene['emotionalBeat'] ?? $scene['mood'] ?? null;
        $hasDialogue = !empty($scene['dialogue']) || !empty($scene['narration']);
        $pacing = $this->content['pacing'] ?? 'balanced';

        // PROFESSIONAL SHOT SEQUENCING PATTERNS
        // Based on Hollywood cinematography principles

        // First shot: Always establish context
        if ($index === 0) {
            return $shotTypes['establishing'];
        }

        // Last shot: Close with resolution (wide for scope, or close-up for emotion)
        if ($index === $total - 1) {
            // Emotional endings get close-ups, others get wide shots
            if (in_array($emotionalBeat, ['climax', 'revelation', 'emotional', 'intimate'])) {
                return $shotTypes['close-up'];
            }
            return $shotTypes['wide'];
        }

        // For 2-shot scenes: establishing → close-up
        if ($total === 2) {
            return $shotTypes['close-up'];
        }

        // For 3-shot scenes: establishing → medium → close-up/wide
        if ($total === 3) {
            return $index === 1 ? $shotTypes['medium'] : $shotTypes['close-up'];
        }

        // For 4+ shots: Use narrative-driven selection
        $position = $index / ($total - 1); // 0.0 to 1.0 position in sequence

        // Select based on emotional beat and position
        if ($emotionalBeat) {
            return $this->getShotForEmotionalBeat($emotionalBeat, $position, $shotTypes, $hasDialogue);
        }

        // Default professional sequence for 4+ shots
        // Pattern: establishing → medium → close-up → reaction/detail → medium → wide
        $defaultSequence = match(true) {
            $position < 0.25 => $shotTypes['medium'],           // Early: medium shots
            $position < 0.5 => $shotTypes['close-up'],          // Building: close-ups
            $position < 0.75 => $hasDialogue ? $shotTypes['reaction'] : $shotTypes['detail'],
            default => $shotTypes['medium'],                     // Late: return to medium
        };

        return $defaultSequence;
    }

    /**
     * Get shot type based on emotional beat of the scene.
     * Professional cinematographers match shots to emotional beats.
     */
    protected function getShotForEmotionalBeat(string $beat, float $position, array $shotTypes, bool $hasDialogue): array
    {
        return match($beat) {
            // Tension scenes: tight shots, build pressure
            'tension', 'suspense', 'tense' => match(true) {
                $position < 0.33 => $shotTypes['medium'],
                $position < 0.66 => $shotTypes['close-up'],
                default => $shotTypes['extreme-close-up'],
            },

            // Action scenes: dynamic variety
            'action', 'energetic', 'dynamic' => match(true) {
                $position < 0.25 => $shotTypes['wide'],
                $position < 0.5 => $shotTypes['medium'],
                $position < 0.75 => $shotTypes['close-up'],
                default => $shotTypes['wide'],
            },

            // Emotional/dramatic scenes: focus on faces
            'emotional', 'dramatic', 'intimate' => match(true) {
                $position < 0.33 => $shotTypes['medium-close'],
                $position < 0.66 => $shotTypes['close-up'],
                default => $shotTypes['reaction'],
            },

            // Dialogue scenes: over-shoulder and reactions
            'dialogue', 'conversation' => match(true) {
                $position < 0.33 => $hasDialogue ? $shotTypes['over-shoulder'] : $shotTypes['medium'],
                $position < 0.66 => $shotTypes['medium-close'],
                default => $shotTypes['reaction'],
            },

            // Reveal/discovery scenes: build to detail
            'reveal', 'discovery', 'mystery' => match(true) {
                $position < 0.5 => $shotTypes['medium'],
                $position < 0.75 => $shotTypes['close-up'],
                default => $shotTypes['detail'],
            },

            // Climax scenes: escalating intensity
            'climax', 'peak' => match(true) {
                $position < 0.33 => $shotTypes['wide'],
                $position < 0.66 => $shotTypes['close-up'],
                default => $shotTypes['extreme-close-up'],
            },

            // Contemplative/peaceful scenes: breathing room
            'contemplative', 'peaceful', 'calm' => match(true) {
                $position < 0.5 => $shotTypes['wide'],
                default => $shotTypes['medium'],
            },

            // Default fallback
            default => $shotTypes['medium'],
        };
    }

    /**
     * Build prompt for a specific shot.
     * Creates professional AI image prompts following Sora 2 / Runway best practices:
     * - Optimal length: 50-100 words (2-4 sentences)
     * - Include: subject, environment, camera specs, lighting, style
     * - Be specific, avoid vague terms like "beautiful"
     */
    protected function buildShotPrompt(string $baseDescription, array $shotType, int $index): string
    {
        $parts = [];

        // 1. Subject and environment (from base description)
        $parts[] = $baseDescription;

        // 2. Shot framing with purpose
        $purpose = $shotType['purpose'] ?? 'narrative';
        $parts[] = "{$shotType['type']} shot for {$purpose}";

        // 3. Lens specification (critical for AI video quality)
        if (!empty($shotType['lens'])) {
            $parts[] = $shotType['lens'];
        }

        // 4. Genre-specific styling (uses database-backed CinematographyService)
        $genrePreset = $this->getGenrePreset();

        // Add genre color grade
        if (!empty($genrePreset['colorGrade'])) {
            $parts[] = $genrePreset['colorGrade'];
        }

        // Add genre lighting (first element only to keep prompt concise)
        if (!empty($genrePreset['lighting'])) {
            $lightingElements = explode(',', $genrePreset['lighting']);
            $parts[] = trim($lightingElements[0]);
        }

        // 5. Style from Style Bible if enabled (override genre if set)
        if ($this->sceneMemory['styleBible']['enabled'] && !empty($this->sceneMemory['styleBible']['style'])) {
            $parts[] = $this->sceneMemory['styleBible']['style'];
        } elseif (!empty($genrePreset['style'])) {
            $parts[] = $genrePreset['style'];
        }

        // 6. Technical quality specs
        if ($this->storyboard['technicalSpecs']['enabled']) {
            $parts[] = $this->storyboard['technicalSpecs']['positive'];
        }

        // Join with proper punctuation for optimal AI processing
        $prompt = implode('. ', $parts);

        // Ensure prompt stays under ~100 words for optimal AI processing
        $words = str_word_count($prompt);
        if ($words > 120) {
            // Trim to core elements if too long
            $prompt = implode('. ', array_slice($parts, 0, 5));
        }

        return $prompt;
    }

    /**
     * Get camera movement for a shot based on type and genre.
     * Uses genre-specific camera language from database-backed CinematographyService.
     */
    protected function getCameraMovementForShot(string $shotType, int $index): string
    {
        // Get genre-specific camera movements (uses database-backed service)
        $genrePreset = $this->getGenrePreset();

        // Genre-specific movements for each shot type
        $genreMovements = $this->parseGenreCameraLanguage($genrePreset['camera'] ?? '');

        // Default movements per shot type
        $defaultMovements = [
            'establishing' => 'slow pan',
            'wide' => 'drift',
            'medium' => 'subtle movement',
            'medium-close' => 'gentle push in',
            'close-up' => 'slow push in',
            'extreme-close-up' => 'static with subtle breathing',
            'reaction' => 'quick responsive cut',
            'detail' => 'slow zoom',
            'over-shoulder' => 'slight drift',
            'pov' => 'handheld subtle movement',
        ];

        // First check if genre has a specific movement for this shot type
        if (!empty($genreMovements[$shotType])) {
            return $genreMovements[$shotType];
        }

        // Otherwise use genre's primary camera style combined with shot defaults
        $primaryGenreMovement = $this->getPrimaryGenreMovement($genrePreset['camera'] ?? '');
        if ($primaryGenreMovement && in_array($shotType, ['establishing', 'wide', 'medium'])) {
            return $primaryGenreMovement;
        }

        return $defaultMovements[$shotType] ?? 'static';
    }

    /**
     * Parse genre camera language into shot-type movements.
     */
    protected function parseGenreCameraLanguage(string $cameraLanguage): array
    {
        $movements = [];
        $language = strtolower($cameraLanguage);

        // Map camera language keywords to shot types
        if (str_contains($language, 'slow dolly') || str_contains($language, 'smooth dolly')) {
            $movements['establishing'] = 'slow dolly forward';
            $movements['medium'] = 'subtle dolly';
        }
        if (str_contains($language, 'dutch angle')) {
            $movements['close-up'] = 'dutch angle, slight tilt';
            $movements['reaction'] = 'dutch angle cut';
        }
        if (str_contains($language, 'tracking')) {
            $movements['wide'] = 'tracking shot';
            $movements['medium'] = 'gentle tracking';
        }
        if (str_contains($language, 'handheld')) {
            $movements['close-up'] = 'handheld intimacy';
            $movements['reaction'] = 'handheld responsive';
        }
        if (str_contains($language, 'crane') || str_contains($language, 'rising')) {
            $movements['establishing'] = 'rising crane shot';
            $movements['wide'] = 'crane descent';
        }
        if (str_contains($language, 'push-in') || str_contains($language, 'push in')) {
            $movements['close-up'] = 'slow push in';
            $movements['medium-close'] = 'creeping push in';
        }
        if (str_contains($language, 'static')) {
            $movements['medium'] = 'static, stable frame';
        }
        if (str_contains($language, 'quick cut')) {
            $movements['reaction'] = 'quick cut';
            $movements['detail'] = 'quick insert cut';
        }

        return $movements;
    }

    /**
     * Get primary camera movement from genre language.
     */
    protected function getPrimaryGenreMovement(string $cameraLanguage): ?string
    {
        $language = strtolower($cameraLanguage);

        // Extract the first/primary movement mentioned
        $movements = [
            'slow dolly' => 'slow dolly',
            'smooth tracking' => 'smooth tracking',
            'dutch angle' => 'dutch angle',
            'handheld' => 'handheld',
            'crane shot' => 'crane movement',
            'static' => 'static',
            'elegant slow' => 'elegant slow movement',
            'observational' => 'observational',
        ];

        foreach ($movements as $keyword => $movement) {
            if (str_contains($language, $keyword)) {
                return $movement;
            }
        }

        return null;
    }

    /**
     * Get motion description for video generation based on shot type and genre.
     * Creates professional AI video prompts following Sora 2 / Runway best practices.
     */
    protected function getMotionDescriptionForShot(string $shotType, string $cameraMovement, string $visualDescription): string
    {
        // Get genre preset for additional context (uses database-backed service)
        $genrePreset = $this->getGenrePreset();

        // Professional motion descriptions per shot type
        $baseDescriptions = [
            'establishing' => 'Slow pan across the scene, establishing the environment and atmosphere',
            'wide' => 'Expansive view with subtle camera drift revealing the full scene context',
            'medium' => 'Subtle movement focusing on the main subject with gentle camera motion',
            'medium-close' => 'Gentle push in for intimacy, maintaining connection with subject',
            'close-up' => 'Slight push in emphasizing details and expressions, shallow depth of field',
            'extreme-close-up' => 'Near-static with subtle breathing movement, extreme detail focus',
            'reaction' => 'Quick responsive movement capturing emotional response',
            'detail' => 'Slow zoom highlighting specific important elements',
            'over-shoulder' => 'Subtle lateral drift maintaining dialogue perspective',
            'pov' => 'Handheld movement from character perspective, immersive',
        ];

        $base = $baseDescriptions[$shotType] ?? 'Natural subtle movement maintaining visual interest';

        // Build professional prompt following best practices
        $parts = [];

        // 1. Base movement description
        $parts[] = $base;

        // 2. Camera movement specification
        if (!empty($cameraMovement) && $cameraMovement !== 'static') {
            $parts[] = "Camera: {$cameraMovement}";
        }

        // 3. Genre atmosphere
        if (!empty($genrePreset['atmosphere'])) {
            $parts[] = $genrePreset['atmosphere'];
        }

        // 4. Genre lighting feel
        if (!empty($genrePreset['lighting'])) {
            $lightingHint = explode(',', $genrePreset['lighting'])[0]; // First lighting element
            $parts[] = trim($lightingHint);
        }

        // 5. Visual context snippet (keep under 100 chars for optimal AI processing)
        if (strlen($visualDescription) > 20) {
            $contextSnippet = substr($visualDescription, 0, 80);
            $parts[] = $contextSnippet;
        }

        return implode('. ', $parts);
    }

    /**
     * Get current genre preset configuration.
     * Uses CinematographyService for database-backed presets with fallback to constants.
     */
    public function getGenrePreset(): array
    {
        $genre = $this->content['genre'] ?? $this->content['productionMode'] ?? 'standard';

        // Try database-backed service first
        try {
            $service = app(CinematographyService::class);
            return $service->getGenrePreset($genre);
        } catch (\Exception $e) {
            // Fallback to constants
            return self::GENRE_PRESETS[$genre] ?? self::GENRE_PRESETS['standard'];
        }
    }

    /**
     * Set content genre and apply preset.
     * Uses CinematographyService for database-backed presets with fallback to constants.
     * FIXED: Now applies ALL preset fields, not just 'style'.
     */
    public function setGenre(string $genre): void
    {
        // Get preset from service or constants
        $preset = null;
        try {
            $service = app(CinematographyService::class);
            $preset = $service->getGenrePreset($genre);
        } catch (\Exception $e) {
            // Fallback to constants
            if (isset(self::GENRE_PRESETS[$genre])) {
                $preset = self::GENRE_PRESETS[$genre];
            }
        }

        if ($preset) {
            $this->content['genre'] = $genre;

            // Apply ALL preset fields to Style Bible (not just 'style')
            // This ensures camera language, color grade, atmosphere are all consistent
            if (!empty($preset['style'])) {
                $this->sceneMemory['styleBible']['style'] = $preset['style'];
            }
            if (!empty($preset['camera'])) {
                $this->sceneMemory['styleBible']['camera'] = $preset['camera'];
            }
            if (!empty($preset['colorGrade'])) {
                $this->sceneMemory['styleBible']['colorGrade'] = $preset['colorGrade'];
            }
            if (!empty($preset['atmosphere'])) {
                $this->sceneMemory['styleBible']['atmosphere'] = $preset['atmosphere'];
            }
            if (!empty($preset['lighting'])) {
                $this->sceneMemory['styleBible']['lighting'] = $preset['lighting'];
            }

            // Mark Style Bible as enabled since we have a preset
            $this->sceneMemory['styleBible']['enabled'] = true;

            $this->saveProject();
        }
    }

    /**
     * Set the master visual mode.
     * This is the TOP-LEVEL style authority that overrides everything.
     */
    public function setVisualMode(string $mode): void
    {
        if (isset(self::VISUAL_MODES[$mode])) {
            $this->content['visualMode'] = $mode;
            $this->saveProject();
        }
    }

    /**
     * Get current visual mode with full definition.
     */
    public function getVisualMode(): array
    {
        $mode = $this->content['visualMode'] ?? 'cinematic-realistic';
        return self::VISUAL_MODES[$mode] ?? self::VISUAL_MODES['cinematic-realistic'];
    }

    /**
     * Get visual mode enforcement text for AI prompts.
     * This MUST be included in all AI generation prompts.
     */
    public function getVisualModeEnforcement(): string
    {
        $mode = $this->getVisualMode();
        $enforcement = $mode['enforcement'] ?? '';
        $keywords = $mode['keywords'] ?? '';
        $forbidden = $mode['forbidden'] ?? '';

        $text = "=== MASTER VISUAL STYLE (MANDATORY) ===\n";
        $text .= $enforcement . "\n";
        $text .= "Required visual keywords: {$keywords}\n";
        if (!empty($forbidden)) {
            $text .= "FORBIDDEN styles (never use): {$forbidden}\n";
        }

        return $text;
    }

    /**
     * Auto-detect visual mode from production type and subtype.
     * Called when production type is set.
     */
    public function autoDetectVisualMode(): void
    {
        $productionType = $this->productionType ?? '';
        $productionSubtype = $this->productionSubtype ?? '';

        // Animation types always get stylized mode
        if ($productionType === 'animation' ||
            str_contains(strtolower($productionSubtype), 'anime') ||
            str_contains(strtolower($productionSubtype), 'cartoon') ||
            str_contains(strtolower($productionSubtype), '3d') ||
            str_contains(strtolower($productionSubtype), '2d')) {
            $this->content['visualMode'] = 'stylized-animation';
        }
        // Live-action types get cinematic-realistic
        elseif (in_array($productionType, ['entertainment', 'documentary', 'commercial', 'corporate', 'testimonial', 'product'])) {
            $this->content['visualMode'] = 'cinematic-realistic';
        }
        // Default to cinematic-realistic for safety
        else {
            $this->content['visualMode'] = 'cinematic-realistic';
        }
    }

    /**
     * Get available genres for UI display.
     * Uses CinematographyService for database-backed presets with fallback to constants.
     */
    public function getAvailableGenres(): array
    {
        // Try database-backed service first
        try {
            $service = app(CinematographyService::class);
            $presets = $service->getAllGenrePresets();

            // Convert to expected format
            $genres = [];
            foreach ($presets as $preset) {
                $key = $preset['slug'] ?? $preset['id'];
                $genres[$key] = [
                    'id' => $key,
                    'name' => $preset['name'] ?? ucwords(str_replace('-', ' ', $key)),
                    'camera' => $preset['camera'] ?? '',
                    'style' => $preset['style'] ?? '',
                    'category' => $preset['category'] ?? 'standard',
                ];
            }
            return $genres;
        } catch (\Exception $e) {
            // Fallback to constants
            $genres = [];
            foreach (array_keys(self::GENRE_PRESETS) as $key) {
                $genres[$key] = [
                    'id' => $key,
                    'name' => ucwords(str_replace('-', ' ', $key)),
                    'camera' => self::GENRE_PRESETS[$key]['camera'] ?? '',
                    'style' => self::GENRE_PRESETS[$key]['style'] ?? '',
                ];
            }
            return $genres;
        }
    }

    /**
     * Get dialogue portion for a specific shot.
     * Distributes scene narration across shots.
     */
    protected function getDialogueForShot(array $scene, int $shotIndex, int $totalShots): ?string
    {
        $narration = $scene['narration'] ?? '';
        if (empty($narration)) {
            return null;
        }

        // Split narration into sentences
        $sentences = preg_split('/(?<=[.!?])\s+/', trim($narration), -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);

        if ($sentenceCount === 0) {
            return null;
        }

        // Distribute sentences across shots
        $sentencesPerShot = max(1, (int) ceil($sentenceCount / $totalShots));
        $start = $shotIndex * $sentencesPerShot;
        $end = min($start + $sentencesPerShot, $sentenceCount);

        if ($start >= $sentenceCount) {
            return null;
        }

        return implode(' ', array_slice($sentences, $start, $end - $start));
    }

    /**
     * Enable multi-shot mode and auto-decompose all scenes.
     */
    public function enableMultiShotModeForAll(): void
    {
        $this->multiShotMode['enabled'] = true;
        $this->multiShotMode['autoDecompose'] = true;

        // Decompose all existing scenes
        $this->decomposeAllScenes();
    }

    /**
     * Decompose all scenes into shots.
     */
    public function decomposeAllScenes(): void
    {
        $sceneCount = count($this->script['scenes'] ?? []);
        if ($sceneCount === 0) {
            return;
        }

        $this->isLoading = true;
        $decomposed = 0;

        try {
            foreach ($this->script['scenes'] as $index => $scene) {
                // Skip already decomposed scenes
                if (isset($this->multiShotMode['decomposedScenes'][$index])) {
                    continue;
                }

                // Decompose this scene
                $this->decomposeScene($index);
                $decomposed++;
            }

            $this->saveProject();
            $this->dispatch('scenes-decomposed', ['count' => $decomposed]);

        } catch (\Exception $e) {
            $this->error = __('Failed to decompose all scenes: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Clear all shot decompositions and reset to scene-only mode.
     */
    public function clearAllDecompositions(): void
    {
        $this->multiShotMode['decomposedScenes'] = [];
        $this->multiShotMode['enabled'] = false;
        $this->saveProject();
    }

    /**
     * Get shot statistics for display.
     */
    public function getShotStatistics(): array
    {
        $totalShots = 0;
        $shotsWithImages = 0;
        $shotsWithVideos = 0;
        $decomposedScenes = 0;

        foreach ($this->multiShotMode['decomposedScenes'] as $decomposed) {
            if (isset($decomposed['shots']) && is_array($decomposed['shots'])) {
                $decomposedScenes++;
                $totalShots += count($decomposed['shots']);

                foreach ($decomposed['shots'] as $shot) {
                    if (!empty($shot['imageUrl'])) {
                        $shotsWithImages++;
                    }
                    if (!empty($shot['videoUrl'])) {
                        $shotsWithVideos++;
                    }
                }
            }
        }

        return [
            'totalScenes' => count($this->script['scenes'] ?? []),
            'decomposedScenes' => $decomposedScenes,
            'totalShots' => $totalShots,
            'shotsWithImages' => $shotsWithImages,
            'shotsWithVideos' => $shotsWithVideos,
            'imageProgress' => $totalShots > 0 ? round(($shotsWithImages / $totalShots) * 100) : 0,
            'videoProgress' => $totalShots > 0 ? round(($shotsWithVideos / $totalShots) * 100) : 0,
        ];
    }

    /**
     * Check if a scene has been decomposed.
     */
    public function isSceneDecomposed(int $sceneIndex): bool
    {
        return isset($this->multiShotMode['decomposedScenes'][$sceneIndex])
            && !empty($this->multiShotMode['decomposedScenes'][$sceneIndex]['shots']);
    }

    /**
     * Get shots for a decomposed scene.
     */
    public function getShotsForScene(int $sceneIndex): array
    {
        return $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'] ?? [];
    }

    /**
     * Get characters applied to a scene.
     */
    protected function getCharactersForScene(int $sceneIndex): array
    {
        $characters = [];
        foreach ($this->sceneMemory['characterBible']['characters'] as $character) {
            if (in_array($sceneIndex, $character['appliedScenes'] ?? [])) {
                $characters[] = $character;
            }
        }
        return $characters;
    }

    /**
     * Get location for a scene.
     */
    protected function getLocationForScene(int $sceneIndex): ?array
    {
        foreach ($this->sceneMemory['locationBible']['locations'] as $location) {
            if (in_array($sceneIndex, $location['appliedScenes'] ?? [])) {
                return $location;
            }
        }
        return null;
    }

    /**
     * Generate image for a specific shot.
     * Implements Hollywood-style frame chain:
     * - Shot 1: Uses scene's main image (auto-sync)
     * - Shots 2+: Uses previous shot's last frame OR generates fresh
     */
    public function generateShotImage(int $sceneIndex, int $shotIndex): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$shotIndex])) {
            $this->error = __('Shot not found');
            return;
        }

        $shot = $decomposed['shots'][$shotIndex];

        // FRAME CHAIN LOGIC: Shot 1 should use scene's existing image
        if ($shotIndex === 0) {
            $sceneImage = $this->getSceneImage($sceneIndex);
            if ($sceneImage) {
                // Auto-sync scene image to Shot 1
                $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][0]['imageUrl'] = $sceneImage;
                $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][0]['imageStatus'] = 'ready';
                $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][0]['status'] = 'ready';
                $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][0]['fromSceneImage'] = true;
                $this->saveProject();
                return;
            }
        }

        // FRAME CHAIN LOGIC: Shots 2+ can use captured frame from previous shot
        if ($shotIndex > 0 && !empty($shot['capturedFrameUrl'])) {
            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['imageUrl'] = $shot['capturedFrameUrl'];
            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['imageStatus'] = 'ready';
            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['status'] = 'ready';
            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['fromFrameCapture'] = true;
            $this->saveProject();
            return;
        }

        // Generate fresh image for this shot
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['status'] = 'generating';
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['imageStatus'] = 'generating';

        $this->isLoading = true;
        $this->error = null;

        try {
            $imageService = app(ImageGenerationService::class);

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    // Use enhanced prompt with shot context
                    $enhancedPrompt = $this->buildEnhancedShotImagePrompt($sceneIndex, $shotIndex);

                    $result = $imageService->generateSceneImage($project, [
                        'id' => $shot['id'],
                        'visualDescription' => $enhancedPrompt,
                    ], [
                        'model' => $this->storyboard['imageModel'] ?? 'hidream',
                        'sceneIndex' => $sceneIndex,
                    ]);

                    if ($result['success']) {
                        if (isset($result['imageUrl'])) {
                            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['imageUrl'] = $result['imageUrl'];
                            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['imageStatus'] = 'ready';
                            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['status'] = 'ready';
                        } elseif (isset($result['jobId'])) {
                            // Async job - store for polling
                            $this->pendingJobs["shot_{$sceneIndex}_{$shotIndex}"] = [
                                'jobId' => $result['jobId'],
                                'type' => 'shot',
                                'sceneIndex' => $sceneIndex,
                                'shotIndex' => $shotIndex,
                            ];
                            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['imageStatus'] = 'processing';
                        }
                        $this->saveProject();
                    } else {
                        throw new \Exception($result['error'] ?? __('Generation failed'));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['status'] = 'error';
            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['imageStatus'] = 'error';
            $this->error = __('Failed to generate shot image: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Get scene's main image URL.
     */
    protected function getSceneImage(int $sceneIndex): ?string
    {
        $storyboardScene = $this->storyboard['scenes'][$sceneIndex] ?? null;
        return $storyboardScene['imageUrl'] ?? null;
    }

    /**
     * Build enhanced prompt for shot image generation.
     * Includes scene context, shot type, and consistency anchors.
     */
    protected function buildEnhancedShotImagePrompt(int $sceneIndex, int $shotIndex): string
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$shotIndex])) {
            return '';
        }

        $shot = $decomposed['shots'][$shotIndex];
        $scene = $this->script['scenes'][$sceneIndex] ?? [];

        // Start with the shot's image prompt
        $prompt = $shot['imagePrompt'] ?? $shot['prompt'] ?? '';

        // Add shot type context
        $shotType = $shot['type'] ?? 'medium';
        $shotTypeDescriptions = [
            'establishing' => 'Wide establishing shot showing full environment',
            'medium' => 'Medium shot focusing on main subject',
            'close-up' => 'Close-up emphasizing details and expressions',
            'reaction' => 'Reaction shot capturing emotional response',
            'detail' => 'Detail shot highlighting specific elements',
            'wide' => 'Wide shot revealing full context',
        ];
        $prompt .= '. ' . ($shotTypeDescriptions[$shotType] ?? '');

        // Add consistency anchors
        $anchors = $decomposed['consistencyAnchors'] ?? [];
        if (!empty($anchors['style'])) {
            $prompt .= '. Style: ' . $anchors['style'];
        }

        // Add scene mood if available
        if (!empty($scene['mood'])) {
            $prompt .= '. Mood: ' . $scene['mood'];
        }

        return $prompt;
    }

    /**
     * Generate all shots for a scene.
     */
    public function generateAllShots(int $sceneIndex): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed) {
            $this->error = __('Scene not decomposed');
            return;
        }

        foreach ($decomposed['shots'] as $shotIndex => $shot) {
            if ($shot['status'] !== 'ready') {
                $this->generateShotImage($sceneIndex, $shotIndex);
            }
        }
    }

    /**
     * Select a shot for the scene.
     */
    public function selectShot(int $sceneIndex, int $shotIndex): void
    {
        if (isset($this->multiShotMode['decomposedScenes'][$sceneIndex])) {
            $this->multiShotMode['decomposedScenes'][$sceneIndex]['selectedShot'] = $shotIndex;
            $this->saveProject();
        }
    }

    /**
     * Open shot preview modal.
     */
    public function openShotPreviewModal(int $sceneIndex, int $shotIndex): void
    {
        $this->shotPreviewSceneIndex = $sceneIndex;
        $this->shotPreviewShotIndex = $shotIndex;
        $this->shotPreviewTab = 'image';
        $this->showShotPreviewModal = true;
    }

    /**
     * Close shot preview modal.
     */
    public function closeShotPreviewModal(): void
    {
        $this->showShotPreviewModal = false;
    }

    /**
     * Switch tab in shot preview modal.
     */
    public function switchShotPreviewTab(string $tab): void
    {
        $this->shotPreviewTab = $tab;
    }

    /**
     * Open frame capture modal.
     */
    public function openFrameCaptureModal(int $sceneIndex, int $shotIndex): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$shotIndex])) {
            $this->error = __('Shot not found');
            return;
        }

        $shot = $decomposed['shots'][$shotIndex];
        if (empty($shot['videoUrl'])) {
            $this->error = __('Generate video first to capture frames');
            return;
        }

        $this->frameCaptureSceneIndex = $sceneIndex;
        $this->frameCaptureShotIndex = $shotIndex;
        $this->capturedFrame = null;
        $this->showFrameCaptureModal = true;
    }

    /**
     * Close frame capture modal.
     */
    public function closeFrameCaptureModal(): void
    {
        $this->showFrameCaptureModal = false;
        $this->capturedFrame = null;
    }

    /**
     * Set captured frame from JavaScript.
     */
    public function setCapturedFrame(string $frameDataUrl): void
    {
        $this->capturedFrame = $frameDataUrl;
    }

    /**
     * Transfer captured frame to next shot.
     * Part of the Hollywood-style frame chain workflow.
     */
    public function transferFrameToNextShot(): void
    {
        if (!$this->capturedFrame) {
            $this->error = __('No frame captured');
            return;
        }

        $sceneIndex = $this->frameCaptureSceneIndex;
        $shotIndex = $this->frameCaptureShotIndex;
        $nextShotIndex = $shotIndex + 1;

        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$nextShotIndex])) {
            $this->error = __('Next shot not found');
            return;
        }

        try {
            $filename = "frame_capture_{$sceneIndex}_{$shotIndex}_" . time() . '.png';

            // Decode base64 and save
            $frameData = preg_replace('/^data:image\/\w+;base64,/', '', $this->capturedFrame);
            $frameData = base64_decode($frameData);

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    $path = "wizard-projects/{$project->id}/frames/{$filename}";
                    Storage::disk('public')->put($path, $frameData);
                    $imageUrl = Storage::disk('public')->url($path);

                    // Update next shot with transferred frame (Hollywood frame chain)
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['imageUrl'] = $imageUrl;
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['capturedFrameUrl'] = $imageUrl;
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['imageStatus'] = 'ready';
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['status'] = 'ready';
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['fromFrameCapture'] = true;
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['transferredFrom'] = $shotIndex;

                    // Store the frame reference on the source shot too
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['lastFrameUrl'] = $imageUrl;

                    $this->saveProject();
                    $this->closeFrameCaptureModal();

                    $this->dispatch('frame-transferred', [
                        'sceneIndex' => $sceneIndex,
                        'fromShot' => $shotIndex,
                        'toShot' => $nextShotIndex,
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->error = __('Failed to transfer frame: ') . $e->getMessage();
        }
    }

    /**
     * Auto-capture last frame from video and transfer to next shot.
     * Called automatically after video generation completes.
     */
    public function autoCaptureLastFrame(int $sceneIndex, int $shotIndex, string $frameDataUrl): void
    {
        $nextShotIndex = $shotIndex + 1;

        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$nextShotIndex])) {
            return; // No next shot to transfer to
        }

        // Already has an image
        if (!empty($decomposed['shots'][$nextShotIndex]['imageUrl'])) {
            return;
        }

        try {
            $filename = "auto_frame_{$sceneIndex}_{$shotIndex}_" . time() . '.png';
            $frameData = preg_replace('/^data:image\/\w+;base64,/', '', $frameDataUrl);
            $frameData = base64_decode($frameData);

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    $path = "wizard-projects/{$project->id}/frames/{$filename}";
                    Storage::disk('public')->put($path, $frameData);
                    $imageUrl = Storage::disk('public')->url($path);

                    // Auto-transfer to next shot
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['imageUrl'] = $imageUrl;
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['capturedFrameUrl'] = $imageUrl;
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['imageStatus'] = 'ready';
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['status'] = 'ready';
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['fromFrameCapture'] = true;
                    $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$nextShotIndex]['autoTransferred'] = true;

                    $this->saveProject();
                }
            }
        } catch (\Exception $e) {
            Log::error('Auto frame capture failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if shot chain is ready (all shots have images).
     */
    public function isShotChainReady(int $sceneIndex): bool
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || empty($decomposed['shots'])) {
            return false;
        }

        foreach ($decomposed['shots'] as $shot) {
            if (empty($shot['imageUrl'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if all shots have videos.
     */
    public function areAllShotVideosReady(int $sceneIndex): bool
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || empty($decomposed['shots'])) {
            return false;
        }

        foreach ($decomposed['shots'] as $shot) {
            if (empty($shot['videoUrl'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get shot chain status for display.
     */
    public function getShotChainStatus(int $sceneIndex): array
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || empty($decomposed['shots'])) {
            return [
                'totalShots' => 0,
                'imagesReady' => 0,
                'videosReady' => 0,
                'imageProgress' => 0,
                'videoProgress' => 0,
                'isImageChainComplete' => false,
                'isVideoChainComplete' => false,
            ];
        }

        $totalShots = count($decomposed['shots']);
        $imagesReady = 0;
        $videosReady = 0;

        foreach ($decomposed['shots'] as $shot) {
            if (!empty($shot['imageUrl'])) $imagesReady++;
            if (!empty($shot['videoUrl'])) $videosReady++;
        }

        return [
            'totalShots' => $totalShots,
            'imagesReady' => $imagesReady,
            'videosReady' => $videosReady,
            'imageProgress' => $totalShots > 0 ? round(($imagesReady / $totalShots) * 100) : 0,
            'videoProgress' => $totalShots > 0 ? round(($videosReady / $totalShots) * 100) : 0,
            'isImageChainComplete' => $imagesReady === $totalShots,
            'isVideoChainComplete' => $videosReady === $totalShots,
        ];
    }

    /**
     * Generate shot chain sequentially (for proper frame chaining).
     * Generates Shot 1 video, captures last frame, transfers to Shot 2, etc.
     */
    public function generateShotChain(int $sceneIndex): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || empty($decomposed['shots'])) {
            $this->error = __('Scene not decomposed');
            return;
        }

        // First, ensure all shots have images
        if (!$this->isShotChainReady($sceneIndex)) {
            // Generate images first
            $this->generateAllShots($sceneIndex);
            return;
        }

        // Then generate videos sequentially
        foreach ($decomposed['shots'] as $shotIndex => $shot) {
            if (empty($shot['videoUrl']) && ($shot['videoStatus'] ?? 'pending') !== 'generating') {
                $this->generateShotVideo($sceneIndex, $shotIndex);
                // Note: In a real async system, we'd wait for completion before next
                // For now, we queue all - the frame chain works via captured frames
            }
        }
    }

    /**
     * Set shot duration.
     */
    public function setShotDuration(int $sceneIndex, int $shotIndex, int $duration): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$shotIndex])) {
            return;
        }

        // Validate duration (5s, 6s, or 10s for video models)
        $validDurations = [5, 6, 10];
        if (!in_array($duration, $validDurations)) {
            $duration = 10; // Default to cinematic
        }

        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['selectedDuration'] = $duration;
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['duration'] = $duration;

        // Update duration class
        $durationClass = match($duration) {
            5 => 'short',
            6 => 'standard',
            10 => 'cinematic',
            default => 'standard',
        };
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['durationClass'] = $durationClass;

        // Recalculate total scene duration
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['totalDuration'] = $this->calculateSceneTotalDuration($sceneIndex);

        $this->saveProject();
    }

    /**
     * Set shot camera movement.
     */
    public function setShotCameraMovement(int $sceneIndex, int $shotIndex, string $movement): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$shotIndex])) {
            return;
        }

        $validMovements = [
            'Pan left', 'Pan right', 'Zoom in', 'Zoom out',
            'Push in', 'Pull out', 'Tilt up', 'Tilt down',
            'Tracking shot', 'Static shot', 'slow pan', 'static',
            'push in', 'quick cut', 'slow zoom', 'drift'
        ];

        if (!in_array($movement, $validMovements)) {
            return;
        }

        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['cameraMovement'] = $movement;

        // Update video prompt to include new camera movement
        $shot = $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex];
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['videoPrompt'] =
            $this->getMotionDescriptionForShot($shot['type'] ?? 'medium', $movement, $shot['imagePrompt'] ?? '');

        $this->saveProject();
    }

    /**
     * Update shot image prompt.
     */
    public function updateShotPrompt(int $sceneIndex, int $shotIndex, string $prompt): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$shotIndex])) {
            return;
        }

        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['imagePrompt'] = $prompt;
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['prompt'] = $prompt;

        // Clear existing image so it can be regenerated
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['imageUrl'] = null;
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['imageStatus'] = 'pending';
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['status'] = 'pending';

        $this->saveProject();
    }

    /**
     * Regenerate a shot image.
     */
    public function regenerateShotImage(int $sceneIndex, int $shotIndex): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$shotIndex])) {
            return;
        }

        // Clear existing image data
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['imageUrl'] = null;
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['imageStatus'] = 'pending';
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['status'] = 'pending';
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['fromSceneImage'] = false;
        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['fromFrameCapture'] = false;

        // Generate new image
        $this->generateShotImage($sceneIndex, $shotIndex);
    }

    /**
     * Calculate total duration of all shots in a decomposed scene.
     */
    public function calculateSceneTotalDuration(int $sceneIndex): int
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || empty($decomposed['shots'])) {
            return 0;
        }

        $total = 0;
        foreach ($decomposed['shots'] as $shot) {
            $total += $shot['selectedDuration'] ?? $shot['duration'] ?? 6;
        }
        return $total;
    }

    /**
     * Reset decomposition for a scene.
     */
    public function resetDecomposition(int $sceneIndex): void
    {
        if (isset($this->multiShotMode['decomposedScenes'][$sceneIndex])) {
            unset($this->multiShotMode['decomposedScenes'][$sceneIndex]);
            $this->saveProject();
        }
    }

    /**
     * Generate video for a specific shot.
     */
    public function generateShotVideo(int $sceneIndex, int $shotIndex): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed || !isset($decomposed['shots'][$shotIndex])) {
            $this->error = __('Shot not found');
            return;
        }

        $shot = $decomposed['shots'][$shotIndex];
        if (empty($shot['imageUrl'])) {
            $this->error = __('Generate image first');
            return;
        }

        $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['videoStatus'] = 'generating';
        $this->isLoading = true;
        $this->error = null;

        try {
            $animationService = app(\Starter\AppVideoWizard\Services\AnimationService::class);
            $duration = $shot['selectedDuration'] ?? $shot['duration'] ?? 6;

            if ($this->projectId) {
                $project = WizardProject::find($this->projectId);
                if ($project) {
                    // Build motion description for the shot
                    $motionPrompt = $this->buildShotMotionPrompt($shot);

                    $result = $animationService->generateAnimation($project, [
                        'imageUrl' => $shot['imageUrl'],
                        'prompt' => $motionPrompt,
                        'model' => $this->animation['model'] ?? 'minimax',
                        'duration' => $duration,
                    ]);

                    if ($result['success']) {
                        if (isset($result['videoUrl'])) {
                            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['videoUrl'] = $result['videoUrl'];
                            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['videoStatus'] = 'ready';
                        } elseif (isset($result['taskId'])) {
                            // Async job - store for polling
                            $this->pendingJobs["shot_video_{$sceneIndex}_{$shotIndex}"] = [
                                'taskId' => $result['taskId'],
                                'type' => 'shot_video',
                                'sceneIndex' => $sceneIndex,
                                'shotIndex' => $shotIndex,
                            ];
                            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['videoStatus'] = 'processing';
                            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['videoTaskId'] = $result['taskId'];
                        }
                        $this->saveProject();
                    } else {
                        throw new \Exception($result['error'] ?? __('Animation failed'));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->multiShotMode['decomposedScenes'][$sceneIndex]['shots'][$shotIndex]['videoStatus'] = 'error';
            $this->error = __('Failed to generate shot video: ') . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Build motion prompt for a shot.
     */
    protected function buildShotMotionPrompt(array $shot): string
    {
        $prompt = '';

        // Use narrative beat motion description if available
        if (!empty($shot['narrativeBeat']['motionDescription'])) {
            $prompt = $shot['narrativeBeat']['motionDescription'];
        } elseif (!empty($shot['description'])) {
            $prompt = $shot['description'];
        } else {
            // Build from shot type
            $shotType = $shot['type'] ?? 'medium';
            $cameraMovement = $shot['cameraMovement'] ?? 'static';

            $movements = [
                'establishing' => 'slow pan across the scene, establishing the environment',
                'medium' => 'subtle movement focusing on the subject',
                'close-up' => 'slight push in, emphasizing details',
                'reaction' => 'quick cut, capturing the reaction',
                'detail' => 'slow zoom on key details',
                'wide' => 'expansive view with subtle camera drift',
            ];

            $prompt = $movements[$shotType] ?? 'natural subtle movement';
        }

        return $prompt;
    }

    /**
     * Generate all shot videos for a decomposed scene.
     */
    public function generateAllShotVideos(int $sceneIndex): void
    {
        $decomposed = $this->multiShotMode['decomposedScenes'][$sceneIndex] ?? null;
        if (!$decomposed) {
            $this->error = __('Scene not decomposed');
            return;
        }

        foreach ($decomposed['shots'] as $shotIndex => $shot) {
            if (!empty($shot['imageUrl']) && ($shot['videoStatus'] ?? 'pending') !== 'ready') {
                $this->generateShotVideo($sceneIndex, $shotIndex);
            }
        }
    }

    // =========================================================================
    // PHASE 5: SHOT-BASED VIDEO ASSEMBLY METHODS
    // =========================================================================

    /**
     * Collect all shot videos in scene/shot order for assembly.
     * Hollywood workflow: Scenes contain shots, shots have videos.
     *
     * @return array List of video URLs in playback order
     */
    public function collectAllShotVideos(): array
    {
        $videos = [];
        $sceneClips = [];

        // If multi-shot mode is enabled, collect from decomposed scenes
        if ($this->multiShotMode['enabled'] && !empty($this->multiShotMode['decomposedScenes'])) {
            foreach ($this->multiShotMode['decomposedScenes'] as $sceneIndex => $decomposed) {
                $sceneVideos = [];

                foreach ($decomposed['shots'] ?? [] as $shotIndex => $shot) {
                    if (!empty($shot['videoUrl'])) {
                        $videoEntry = [
                            'url' => $shot['videoUrl'],
                            'sceneIndex' => $sceneIndex,
                            'shotIndex' => $shotIndex,
                            'duration' => $shot['selectedDuration'] ?? $shot['duration'] ?? 6,
                            'type' => $shot['type'] ?? 'medium',
                            'cameraMovement' => $shot['cameraMovement'] ?? 'static',
                        ];
                        $videos[] = $videoEntry;
                        $sceneVideos[] = $videoEntry;
                    }
                }

                if (!empty($sceneVideos)) {
                    $sceneClips[$sceneIndex] = [
                        'sceneIndex' => $sceneIndex,
                        'sceneTitle' => $this->script['scenes'][$sceneIndex]['title'] ?? "Scene " . ($sceneIndex + 1),
                        'clips' => $sceneVideos,
                        'clipCount' => count($sceneVideos),
                        'totalDuration' => array_sum(array_column($sceneVideos, 'duration')),
                    ];
                }
            }
        } else {
            // Fallback: Collect from standard animation scenes (non-multi-shot mode)
            foreach ($this->animation['scenes'] ?? [] as $sceneIndex => $animScene) {
                if (!empty($animScene['videoUrl'])) {
                    $videoEntry = [
                        'url' => $animScene['videoUrl'],
                        'sceneIndex' => $sceneIndex,
                        'shotIndex' => 0,
                        'duration' => $animScene['clipDuration'] ?? 6,
                        'type' => 'full_scene',
                        'cameraMovement' => $animScene['motion'] ?? 'standard',
                    ];
                    $videos[] = $videoEntry;
                    $sceneClips[$sceneIndex] = [
                        'sceneIndex' => $sceneIndex,
                        'sceneTitle' => $this->script['scenes'][$sceneIndex]['title'] ?? "Scene " . ($sceneIndex + 1),
                        'clips' => [$videoEntry],
                        'clipCount' => 1,
                        'totalDuration' => $videoEntry['duration'],
                    ];
                }
            }
        }

        // Update assembly state
        $this->assembly['collectedVideos'] = $videos;
        $this->assembly['sceneClips'] = $sceneClips;
        $this->assembly['totalDuration'] = array_sum(array_column($videos, 'duration'));
        $this->assembly['shotBased'] = $this->multiShotMode['enabled'];

        return $videos;
    }

    /**
     * Get assembly readiness status.
     * Checks if all shots/scenes have videos ready for assembly.
     *
     * @return array Readiness status with details
     */
    public function getAssemblyReadiness(): array
    {
        $totalScenes = count($this->script['scenes'] ?? []);
        $totalShots = 0;
        $readyShots = 0;
        $pendingShots = [];
        $scenesWithAllVideos = 0;

        if ($this->multiShotMode['enabled'] && !empty($this->multiShotMode['decomposedScenes'])) {
            // Multi-shot mode: Count individual shots
            foreach ($this->multiShotMode['decomposedScenes'] as $sceneIndex => $decomposed) {
                $sceneReady = true;

                foreach ($decomposed['shots'] ?? [] as $shotIndex => $shot) {
                    $totalShots++;

                    if (!empty($shot['videoUrl'])) {
                        $readyShots++;
                    } else {
                        $sceneReady = false;
                        $pendingShots[] = [
                            'sceneIndex' => $sceneIndex,
                            'shotIndex' => $shotIndex,
                            'type' => $shot['type'] ?? 'unknown',
                            'status' => $shot['videoStatus'] ?? 'pending',
                        ];
                    }
                }

                if ($sceneReady && !empty($decomposed['shots'])) {
                    $scenesWithAllVideos++;
                }
            }
        } else {
            // Standard mode: Each scene = 1 video
            foreach ($this->animation['scenes'] ?? [] as $sceneIndex => $animScene) {
                $totalShots++;

                if (!empty($animScene['videoUrl'])) {
                    $readyShots++;
                    $scenesWithAllVideos++;
                } else {
                    $pendingShots[] = [
                        'sceneIndex' => $sceneIndex,
                        'shotIndex' => 0,
                        'type' => 'full_scene',
                        'status' => $animScene['videoStatus'] ?? 'pending',
                    ];
                }
            }
        }

        $isReady = $totalShots > 0 && $readyShots === $totalShots;
        $progress = $totalShots > 0 ? round(($readyShots / $totalShots) * 100) : 0;

        return [
            'isReady' => $isReady,
            'totalScenes' => $totalScenes,
            'totalShots' => $totalShots,
            'readyShots' => $readyShots,
            'pendingShots' => $pendingShots,
            'pendingCount' => count($pendingShots),
            'scenesWithAllVideos' => $scenesWithAllVideos,
            'progress' => $progress,
            'mode' => $this->multiShotMode['enabled'] ? 'multi-shot' : 'standard',
        ];
    }

    /**
     * Prepare assembly for export/rendering.
     * Collects all videos and validates readiness.
     *
     * @return array Preparation result with status
     */
    public function prepareForExport(): array
    {
        $this->assembly['assemblyStatus'] = 'collecting';

        // Collect all videos
        $videos = $this->collectAllShotVideos();

        // Check readiness
        $readiness = $this->getAssemblyReadiness();

        if (!$readiness['isReady']) {
            $this->assembly['assemblyStatus'] = 'pending';
            return [
                'success' => false,
                'error' => __('Not all videos are ready'),
                'readiness' => $readiness,
                'pendingCount' => $readiness['pendingCount'],
            ];
        }

        $this->assembly['assemblyStatus'] = 'ready';
        $this->saveProject();

        return [
            'success' => true,
            'videoCount' => count($videos),
            'totalDuration' => $this->assembly['totalDuration'],
            'sceneCount' => count($this->assembly['sceneClips']),
            'videos' => $videos,
            'readiness' => $readiness,
        ];
    }

    /**
     * Get all video URLs in order for final rendering.
     *
     * @return array List of video URLs only
     */
    public function getVideoUrlsForRender(): array
    {
        $this->collectAllShotVideos();
        return array_column($this->assembly['collectedVideos'], 'url');
    }

    /**
     * Enable or disable shot-based assembly mode.
     */
    public function setShotBasedAssembly(bool $enabled): void
    {
        $this->assembly['shotBased'] = $enabled;

        if ($enabled) {
            // Re-collect videos to update state
            $this->collectAllShotVideos();
        } else {
            // Clear shot-based data
            $this->assembly['collectedVideos'] = [];
            $this->assembly['sceneClips'] = [];
            $this->assembly['totalDuration'] = 0;
        }

        $this->saveProject();
    }

    /**
     * Reset assembly state.
     */
    public function resetAssembly(): void
    {
        $this->assembly['collectedVideos'] = [];
        $this->assembly['sceneClips'] = [];
        $this->assembly['totalDuration'] = 0;
        $this->assembly['assemblyStatus'] = 'pending';
        $this->assembly['renderProgress'] = 0;
        $this->assembly['finalVideoUrl'] = null;

        $this->saveProject();
    }

    /**
     * Set global transition type for all scenes.
     */
    public function setGlobalTransition(string $transition): void
    {
        $validTransitions = ['cut', 'fade', 'slide-left', 'slide-right', 'zoom-in', 'zoom-out'];

        if (!in_array($transition, $validTransitions)) {
            return;
        }

        $this->assembly['transitions']['global'] = $transition;
        $this->assembly['defaultTransition'] = $transition;

        // Also update the default transition for compatibility
        $this->saveProject();

        // Dispatch event for preview engine refresh
        $this->dispatch('transition-updated');
    }

    /**
     * Apply a caption preset with predefined styling.
     */
    public function applyCaptionPreset(string $presetId): void
    {
        $presets = [
            'karaoke' => [
                'style' => 'karaoke',
                'fillColor' => '#FFFFFF',
                'strokeColor' => '#000000',
                'strokeWidth' => 2,
                'highlightColor' => '#FBBF24',
                'fontFamily' => 'Montserrat',
                'textTransform' => 'none',
                'shadowEnabled' => true,
                'shadowBlur' => 4,
                'glowEnabled' => false,
            ],
            'beasty' => [
                'style' => 'beasty',
                'fillColor' => '#FFFFFF',
                'strokeColor' => '#000000',
                'strokeWidth' => 4,
                'highlightColor' => '#EF4444',
                'fontFamily' => 'Anton',
                'textTransform' => 'uppercase',
                'shadowEnabled' => true,
                'shadowBlur' => 6,
                'glowEnabled' => false,
            ],
            'hormozi' => [
                'style' => 'hormozi',
                'fillColor' => '#FFFFFF',
                'strokeColor' => '#000000',
                'strokeWidth' => 0,
                'highlightColor' => '#FBBF24',
                'fontFamily' => 'Poppins',
                'textTransform' => 'none',
                'backgroundEnabled' => false,
                'shadowEnabled' => false,
                'glowEnabled' => false,
            ],
            'ali' => [
                'style' => 'ali',
                'fillColor' => '#FFFFFF',
                'strokeColor' => '#000000',
                'strokeWidth' => 1,
                'highlightColor' => '#8B5CF6',
                'fontFamily' => 'Montserrat',
                'textTransform' => 'none',
                'shadowEnabled' => false,
                'glowEnabled' => true,
                'glowColor' => '#8B5CF6',
                'glowIntensity' => 15,
            ],
            'minimal' => [
                'style' => 'minimal',
                'fillColor' => '#FFFFFF',
                'strokeColor' => '#000000',
                'strokeWidth' => 0,
                'highlightColor' => '#FFFFFF',
                'fontFamily' => 'Inter',
                'textTransform' => 'none',
                'shadowEnabled' => false,
                'glowEnabled' => false,
            ],
            'neon' => [
                'style' => 'neon',
                'fillColor' => '#FFFFFF',
                'strokeColor' => '#8B5CF6',
                'strokeWidth' => 1,
                'highlightColor' => '#06B6D4',
                'fontFamily' => 'Bebas Neue',
                'textTransform' => 'uppercase',
                'shadowEnabled' => false,
                'glowEnabled' => true,
                'glowColor' => '#8B5CF6',
                'glowIntensity' => 20,
            ],
        ];

        if (!isset($presets[$presetId])) {
            return;
        }

        $preset = $presets[$presetId];

        // Apply all preset values to captions settings
        foreach ($preset as $key => $value) {
            $this->assembly['captions'][$key] = $value;
        }

        $this->saveProject();

        // Dispatch event for preview engine refresh
        $this->dispatch('caption-preset-applied', ['preset' => $presetId]);
    }

    /**
     * Apply a voice processing preset.
     */
    public function applyVoicePreset(string $presetId): void
    {
        $presets = [
            'natural' => [
                'voicePreset' => 'natural',
                'normalize' => true,
                'noiseReduction' => false,
                'voiceEnhance' => false,
                'voiceVolume' => 100,
            ],
            'broadcast' => [
                'voicePreset' => 'broadcast',
                'normalize' => true,
                'noiseReduction' => true,
                'voiceEnhance' => true,
                'voiceVolume' => 100,
            ],
            'warm' => [
                'voicePreset' => 'warm',
                'normalize' => true,
                'noiseReduction' => false,
                'voiceEnhance' => true,
                'voiceVolume' => 95,
            ],
        ];

        if (!isset($presets[$presetId])) {
            return;
        }

        $preset = $presets[$presetId];

        // Apply all preset values to audioMix settings
        foreach ($preset as $key => $value) {
            $this->assembly['audioMix'][$key] = $value;
        }

        $this->saveProject();

        // Dispatch event for audio refresh
        $this->dispatch('voice-preset-applied', ['preset' => $presetId]);
    }

    /**
     * Timeline undo action - Phase 5.
     */
    public function timelineUndo(): void
    {
        // Get history from assembly state
        $history = $this->assembly['timelineHistory'] ?? [];
        $historyIndex = $this->assembly['timelineHistoryIndex'] ?? -1;

        if ($historyIndex > 0) {
            $historyIndex--;
            $this->assembly['timelineHistoryIndex'] = $historyIndex;

            // Restore state from history
            if (isset($history[$historyIndex])) {
                $state = $history[$historyIndex];
                // Apply the state restoration logic here
                // This would restore scene durations, positions, etc.
            }

            $this->saveProject();
            $this->dispatch('timeline-state-restored');
        }
    }

    /**
     * Timeline redo action - Phase 5.
     */
    public function timelineRedo(): void
    {
        $history = $this->assembly['timelineHistory'] ?? [];
        $historyIndex = $this->assembly['timelineHistoryIndex'] ?? -1;

        if ($historyIndex < count($history) - 1) {
            $historyIndex++;
            $this->assembly['timelineHistoryIndex'] = $historyIndex;

            // Restore state from history
            if (isset($history[$historyIndex])) {
                $state = $history[$historyIndex];
                // Apply the state restoration logic here
            }

            $this->saveProject();
            $this->dispatch('timeline-state-restored');
        }
    }

    /**
     * Save current timeline state to history - Phase 5.
     */
    public function saveTimelineState(string $action = 'edit'): void
    {
        $history = $this->assembly['timelineHistory'] ?? [];
        $historyIndex = $this->assembly['timelineHistoryIndex'] ?? -1;

        // Remove any future states if not at the end
        if ($historyIndex < count($history) - 1) {
            $history = array_slice($history, 0, $historyIndex + 1);
        }

        // Create state snapshot
        $state = [
            'timestamp' => now()->timestamp,
            'action' => $action,
            'scenes' => $this->storyboard['scenes'] ?? [],
            'assembly' => [
                'captions' => $this->assembly['captions'] ?? [],
                'music' => $this->assembly['music'] ?? [],
                'transitions' => $this->assembly['transitions'] ?? [],
            ],
        ];

        $history[] = $state;

        // Limit history to 50 entries
        if (count($history) > 50) {
            array_shift($history);
        } else {
            $historyIndex++;
        }

        $this->assembly['timelineHistory'] = $history;
        $this->assembly['timelineHistoryIndex'] = $historyIndex;

        $this->saveProject();
    }

    /**
     * Trim clip start time - Phase 5.
     */
    public function trimClipStart(string $track, int $clipIndex, float $newStart): void
    {
        $this->saveTimelineState('trim-start');

        if ($track === 'video' && isset($this->storyboard['scenes'][$clipIndex])) {
            // Calculate the difference and adjust duration
            $currentDuration = $this->storyboard['scenes'][$clipIndex]['duration'] ?? 5;
            // Implement trim logic
            $this->saveProject();
            $this->dispatch('clip-trimmed', ['track' => $track, 'index' => $clipIndex]);
        }
    }

    /**
     * Trim clip end time - Phase 5.
     */
    public function trimClipEnd(string $track, int $clipIndex, float $newDuration): void
    {
        $this->saveTimelineState('trim-end');

        if ($track === 'video' && isset($this->storyboard['scenes'][$clipIndex])) {
            $this->storyboard['scenes'][$clipIndex]['duration'] = max(0.5, $newDuration);
            $this->saveProject();
            $this->dispatch('clip-trimmed', ['track' => $track, 'index' => $clipIndex]);
        }
    }

    // =========================================================================
    // PHASE 6: EXPORT METHODS
    // =========================================================================

    /**
     * Update export setting - Phase 6.
     */
    public function updateExportSetting(string $key, mixed $value): void
    {
        if (!isset($this->assembly['export'])) {
            $this->assembly['export'] = $this->getDefaultExportSettings();
        }

        $this->assembly['export'][$key] = $value;

        // If platform changed, apply platform preset
        if ($key === 'platform' && $value !== 'custom') {
            $preset = $this->getExportPlatformPreset($value);
            if ($preset) {
                $this->assembly['export']['quality'] = $preset['quality'] ?? '1080p';
                $this->assembly['export']['fps'] = $preset['fps'] ?? 30;
                $this->assembly['export']['bitrate'] = $preset['bitrate'] ?? 'auto';
            }
        }

        $this->saveProject();
    }

    /**
     * Get default export settings - Phase 6.
     */
    public function getDefaultExportSettings(): array
    {
        return [
            'platform' => 'youtube',
            'quality' => '1080p',
            'format' => 'mp4',
            'codec' => 'h264',
            'fps' => 30,
            'bitrate' => 'auto',
            'audioCodec' => 'aac',
            'audioBitrate' => 192,
        ];
    }

    /**
     * Get platform preset for export - Phase 6.
     */
    public function getExportPlatformPreset(string $platform): ?array
    {
        $presets = [
            'youtube' => [
                'quality' => '1080p',
                'fps' => 30,
                'bitrate' => '12000',
                'aspectRatio' => '16:9',
                'maxDuration' => 43200,
            ],
            'tiktok' => [
                'quality' => '1080p',
                'fps' => 30,
                'bitrate' => '6000',
                'aspectRatio' => '9:16',
                'maxDuration' => 600,
            ],
            'instagram_reels' => [
                'quality' => '1080p',
                'fps' => 30,
                'bitrate' => '8000',
                'aspectRatio' => '9:16',
                'maxDuration' => 90,
            ],
            'instagram_feed' => [
                'quality' => '1080p',
                'fps' => 30,
                'bitrate' => '8000',
                'aspectRatio' => '1:1',
                'maxDuration' => 60,
            ],
            'twitter' => [
                'quality' => '720p',
                'fps' => 30,
                'bitrate' => '5000',
                'aspectRatio' => '16:9',
                'maxDuration' => 140,
            ],
            'facebook' => [
                'quality' => '1080p',
                'fps' => 30,
                'bitrate' => '8000',
                'aspectRatio' => '16:9',
                'maxDuration' => 14400,
            ],
            'linkedin' => [
                'quality' => '1080p',
                'fps' => 30,
                'bitrate' => '8000',
                'aspectRatio' => '16:9',
                'maxDuration' => 600,
            ],
        ];

        return $presets[$platform] ?? null;
    }

    /**
     * Start video export - Phase 6 + Phase 7.
     */
    public function startVideoExport(): void
    {
        // Validate export readiness
        $readiness = $this->getAssemblyReadiness();
        if (!$readiness['isReady']) {
            $this->dispatch('export-error', [
                'message' => 'Video is not ready for export. Some scenes are still pending.',
            ]);
            return;
        }

        // Generate unique job ID
        $jobId = \Illuminate\Support\Str::uuid()->toString();

        // Set assembly status to rendering
        $this->assembly['assemblyStatus'] = 'rendering';
        $this->assembly['renderProgress'] = 0;
        $this->assembly['exportJobId'] = $jobId;
        $this->saveProject();

        // Get export settings
        $exportSettings = $this->assembly['export'] ?? $this->getDefaultExportSettings();

        // Build export manifest
        $manifest = $this->buildExportManifest();

        // Determine if we should use Cloud Run (based on config)
        $useCloudRun = !empty(config('services.video_processor.url'))
            && config('services.video_processor.parallel_scenes');

        // Dispatch the export job
        \Modules\AppVideoWizard\Jobs\VideoExportJob::dispatch(
            $this->project->id,
            $jobId,
            $manifest,
            $exportSettings,
            auth()->id(),
            $useCloudRun
        );

        $this->dispatch('export-started', [
            'jobId' => $jobId,
            'settings' => $exportSettings,
            'totalScenes' => count($this->script['scenes'] ?? []),
        ]);
    }

    /**
     * Build export manifest from current project data - Phase 7.
     */
    protected function buildExportManifest(): array
    {
        $scenes = [];
        $scriptScenes = $this->script['scenes'] ?? [];
        $storyboardScenes = $this->storyboard['scenes'] ?? [];

        foreach ($scriptScenes as $index => $scriptScene) {
            $storyboardScene = $storyboardScenes[$index] ?? [];

            $scenes[] = [
                'index' => $index,
                'narration' => $scriptScene['narration'] ?? '',
                'imageUrl' => $storyboardScene['imageUrl'] ?? $storyboardScene['finalImageUrl'] ?? null,
                'voiceoverUrl' => $storyboardScene['voiceoverUrl'] ?? null,
                'duration' => $storyboardScene['duration'] ?? $scriptScene['duration'] ?? 5,
                'voiceoverOffset' => $storyboardScene['voiceoverOffset'] ?? 0,
                'transition' => $storyboardScene['transition'] ?? $this->assembly['defaultTransition'] ?? 'fade',
                'kenBurns' => [
                    'startScale' => $storyboardScene['kenBurns']['startScale'] ?? 1.0,
                    'endScale' => $storyboardScene['kenBurns']['endScale'] ?? 1.2,
                    'startX' => $storyboardScene['kenBurns']['startX'] ?? 0.5,
                    'startY' => $storyboardScene['kenBurns']['startY'] ?? 0.5,
                    'endX' => $storyboardScene['kenBurns']['endX'] ?? 0.5,
                    'endY' => $storyboardScene['kenBurns']['endY'] ?? 0.5,
                ],
            ];
        }

        return [
            'scenes' => $scenes,
            'output' => $this->assembly['export'] ?? $this->getDefaultExportSettings(),
            'music' => [
                'enabled' => $this->assembly['music']['enabled'] ?? false,
                'url' => $this->assembly['music']['url'] ?? null,
                'volume' => ($this->assembly['music']['volume'] ?? 30) / 100,
            ],
            'captions' => [
                'enabled' => $this->assembly['captions']['enabled'] ?? true,
                'style' => $this->assembly['captions']['style'] ?? 'karaoke',
                'position' => $this->assembly['captions']['position'] ?? 'bottom',
                'size' => $this->assembly['captions']['size'] ?? 1.0,
                'fontFamily' => $this->assembly['captions']['fontFamily'] ?? 'Arial',
                'fillColor' => $this->assembly['captions']['fillColor'] ?? '#FFFFFF',
            ],
            'aspectRatio' => $this->aspectRatio ?? '16:9',
        ];
    }

    /**
     * Get export status - Phase 7.
     * Called via polling from frontend.
     */
    public function getExportStatus(): array
    {
        $jobId = $this->assembly['exportJobId'] ?? null;

        if (!$jobId) {
            return [
                'status' => $this->assembly['assemblyStatus'] ?? 'pending',
                'progress' => $this->assembly['renderProgress'] ?? 0,
                'message' => 'No export in progress',
            ];
        }

        // Get status from cache
        $status = \Illuminate\Support\Facades\Cache::get("video_export_status_{$jobId}");

        if ($status) {
            // If completed, update local state
            if ($status['status'] === 'completed' && !empty($status['outputUrl'])) {
                $this->assembly['assemblyStatus'] = 'complete';
                $this->assembly['renderProgress'] = 100;
                $this->assembly['finalVideoUrl'] = $status['outputUrl'];
                $this->assembly['exported'] = true;
                $this->saveProject();
            }

            return $status;
        }

        return [
            'status' => $this->assembly['assemblyStatus'] ?? 'pending',
            'progress' => $this->assembly['renderProgress'] ?? 0,
            'message' => 'Processing...',
        ];
    }

    /**
     * Poll export status and dispatch event - Phase 7.
     */
    public function pollExportStatus(): void
    {
        $status = $this->getExportStatus();

        $this->dispatch('export-progress', [
            'progress' => $status['progress'] ?? 0,
            'currentScene' => $status['currentScene'] ?? 0,
            'complete' => ($status['status'] ?? '') === 'completed',
            'videoUrl' => $status['outputUrl'] ?? null,
            'message' => $status['message'] ?? 'Processing...',
        ]);
    }

    /**
     * Cancel video export - Phase 6.
     */
    public function cancelVideoExport(): void
    {
        $this->assembly['assemblyStatus'] = 'ready';
        $this->assembly['renderProgress'] = 0;
        $this->saveProject();

        $this->dispatch('export-cancelled');
    }

    /**
     * Update export progress (called from job) - Phase 6.
     */
    public function updateExportProgress(int $progress, int $currentScene = 0): void
    {
        $this->assembly['renderProgress'] = $progress;
        $this->saveProject();

        $this->dispatch('export-progress', [
            'progress' => $progress,
            'currentScene' => $currentScene,
            'complete' => $progress >= 100,
            'videoUrl' => $progress >= 100 ? $this->assembly['finalVideoUrl'] : null,
        ]);
    }

    /**
     * Complete export (called from job) - Phase 6.
     */
    public function completeExport(string $videoUrl): void
    {
        $this->assembly['assemblyStatus'] = 'complete';
        $this->assembly['renderProgress'] = 100;
        $this->assembly['finalVideoUrl'] = $videoUrl;
        $this->assembly['exported'] = true;
        $this->assembly['exportedAt'] = now()->toIso8601String();
        $this->saveProject();

        $this->dispatch('export-progress', [
            'progress' => 100,
            'currentScene' => count($this->script['scenes'] ?? []),
            'complete' => true,
            'videoUrl' => $videoUrl,
        ]);
    }

    /**
     * Get total video duration in seconds.
     */
    public function getTotalDuration(): float
    {
        $total = 0;
        foreach ($this->storyboard['scenes'] ?? [] as $scene) {
            $total += $scene['duration'] ?? 5;
        }
        return $total;
    }

    // =========================================================================
    // END PHASE 6: EXPORT METHODS
    // =========================================================================

    /**
     * Get assembly statistics for display.
     */
    public function getAssemblyStats(): array
    {
        $this->collectAllShotVideos();
        $readiness = $this->getAssemblyReadiness();

        return [
            'mode' => $this->assembly['shotBased'] ? 'multi-shot' : 'standard',
            'status' => $this->assembly['assemblyStatus'],
            'videoCount' => count($this->assembly['collectedVideos']),
            'sceneCount' => count($this->assembly['sceneClips']),
            'totalDuration' => $this->assembly['totalDuration'],
            'formattedDuration' => $this->formatDuration($this->assembly['totalDuration']),
            'isReady' => $readiness['isReady'],
            'progress' => $readiness['progress'],
            'pendingShots' => $readiness['pendingCount'],
        ];
    }

    /**
     * Get scenes data formatted for VideoPreviewEngine.
     *
     * Returns an array of scene objects with all data needed for
     * canvas-based preview rendering including images, videos,
     * voiceovers, captions, and transition settings.
     */
    public function getPreviewScenes(): array
    {
        $scenes = [];
        $scriptScenes = $this->script['scenes'] ?? [];
        $storyboardScenes = $this->storyboard['scenes'] ?? [];
        $animationScenes = $this->animation['scenes'] ?? [];

        foreach ($scriptScenes as $index => $scene) {
            $sceneId = $scene['id'] ?? "scene-{$index}";

            // Find corresponding storyboard and animation data
            $storyboard = collect($storyboardScenes)->firstWhere('sceneId', $sceneId);
            $animation = collect($animationScenes)->firstWhere('sceneId', $sceneId);

            // Calculate duration - prefer visualDuration if set, else scene duration
            $duration = $scene['visualDuration'] ?? $scene['duration'] ?? 8;

            // Get transition for this scene
            $transition = $this->assembly['transitions'][$sceneId] ?? [
                'type' => $this->assembly['defaultTransition'] ?? 'fade',
                'duration' => 0.5
            ];

            // Ken Burns effect parameters (randomized for natural movement)
            $kenBurns = [
                'startScale' => 1.0,
                'endScale' => 1.05 + (rand(0, 10) / 100), // 1.05-1.15
                'startX' => 0.5,
                'startY' => 0.5,
                'endX' => 0.5 + (rand(-8, 8) / 100), // 0.42-0.58
                'endY' => 0.5 + (rand(-8, 8) / 100), // 0.42-0.58
            ];

            // Build caption object from narration
            $caption = null;
            if (!empty($scene['narration'])) {
                $caption = [
                    'text' => $scene['narration'],
                    'wordTimings' => $animation['wordTimings'] ?? null,
                ];
            }

            $scenes[] = [
                'id' => $sceneId,
                'index' => $index,
                'duration' => $duration,
                'visualDuration' => $duration,
                'imageUrl' => $storyboard['imageUrl'] ?? null,
                'videoUrl' => $animation['videoUrl'] ?? null,
                'voiceoverUrl' => $animation['voiceoverUrl'] ?? null,
                'voiceoverDuration' => $animation['voiceoverDuration'] ?? null,
                'voiceoverOffset' => $animation['voiceoverOffset'] ?? 0,
                'caption' => $caption,
                'transition' => $transition,
                'kenBurns' => $kenBurns,
            ];
        }

        return $scenes;
    }

    /**
     * Get preview initialization data for Alpine.js controller.
     */
    public function getPreviewInitData(): array
    {
        return [
            'aspectRatio' => $this->aspectRatio,
            'captionsEnabled' => $this->assembly['captions']['enabled'] ?? true,
            'captionStyle' => $this->assembly['captions']['style'] ?? 'karaoke',
            'captionPosition' => $this->assembly['captions']['position'] ?? 'bottom',
            'captionSize' => $this->assembly['captions']['size'] ?? 1.0,
            'musicEnabled' => $this->assembly['music']['enabled'] ?? false,
            'musicVolume' => $this->assembly['music']['volume'] ?? 30,
            'musicUrl' => $this->assembly['music']['url'] ?? null,
        ];
    }

    /**
     * Format duration in seconds to MM:SS.
     */
    protected function formatDuration(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $secs);
    }

    // =========================================================================
    // UPSCALE METHODS
    // =========================================================================

    /**
     * Open upscale quality modal.
     */
    public function openUpscaleModal(int $sceneIndex): void
    {
        $this->upscaleSceneIndex = $sceneIndex;
        $this->upscaleQuality = 'hd';
        $this->showUpscaleModal = true;
    }

    /**
     * Close upscale modal.
     */
    public function closeUpscaleModal(): void
    {
        $this->showUpscaleModal = false;
    }

    /**
     * Upscale scene image.
     */
    public function upscaleImage(): void
    {
        $storyboardScene = $this->storyboard['scenes'][$this->upscaleSceneIndex] ?? null;
        if (!$storyboardScene || empty($storyboardScene['imageUrl'])) {
            $this->error = __('No image to upscale');
            return;
        }

        $this->isUpscaling = true;
        $this->error = null;

        try {
            $imageService = app(ImageGenerationService::class);

            $result = $imageService->upscaleImage(
                $storyboardScene['imageUrl'],
                $this->upscaleQuality
            );

            if ($result['success'] && isset($result['imageUrl'])) {
                $this->storyboard['scenes'][$this->upscaleSceneIndex]['imageUrl'] = $result['imageUrl'];
                $this->storyboard['scenes'][$this->upscaleSceneIndex]['upscaled'] = true;
                $this->storyboard['scenes'][$this->upscaleSceneIndex]['upscaleQuality'] = $this->upscaleQuality;
                $this->saveProject();
                $this->showUpscaleModal = false;
            } else {
                throw new \Exception($result['error'] ?? __('Upscale failed'));
            }
        } catch (\Exception $e) {
            $this->error = __('Failed to upscale image: ') . $e->getMessage();
        } finally {
            $this->isUpscaling = false;
        }
    }

    // =========================================================================
    // AI EDIT WITH MASK METHODS
    // =========================================================================

    /**
     * Open AI edit modal.
     */
    public function openAIEditModal(int $sceneIndex): void
    {
        $this->aiEditSceneIndex = $sceneIndex;
        $this->aiEditPrompt = '';
        $this->aiEditBrushSize = 30;
        $this->showAIEditModal = true;
    }

    /**
     * Close AI edit modal.
     */
    public function closeAIEditModal(): void
    {
        $this->showAIEditModal = false;
    }

    /**
     * Set AI edit brush size.
     */
    public function setAIEditBrushSize(int $size): void
    {
        $this->aiEditBrushSize = max(10, min(100, $size));
    }

    /**
     * Apply AI edit with mask.
     */
    public function applyAIEdit(string $maskData): void
    {
        $storyboardScene = $this->storyboard['scenes'][$this->aiEditSceneIndex] ?? null;
        if (!$storyboardScene || empty($storyboardScene['imageUrl'])) {
            $this->error = __('No image to edit');
            return;
        }

        if (empty($this->aiEditPrompt)) {
            $this->error = __('Please describe what you want to change');
            return;
        }

        $this->isApplyingEdit = true;
        $this->error = null;

        try {
            $imageService = app(ImageGenerationService::class);

            $result = $imageService->editImageWithMask(
                $storyboardScene['imageUrl'],
                $maskData,
                $this->aiEditPrompt
            );

            if ($result['success'] && isset($result['imageUrl'])) {
                $this->storyboard['scenes'][$this->aiEditSceneIndex]['imageUrl'] = $result['imageUrl'];
                $this->storyboard['scenes'][$this->aiEditSceneIndex]['edited'] = true;
                $this->storyboard['scenes'][$this->aiEditSceneIndex]['editHistory'][] = [
                    'prompt' => $this->aiEditPrompt,
                    'timestamp' => now()->toIso8601String(),
                ];
                $this->saveProject();
                $this->showAIEditModal = false;
            } else {
                throw new \Exception($result['error'] ?? __('Edit failed'));
            }
        } catch (\Exception $e) {
            $this->error = __('Failed to apply AI edit: ') . $e->getMessage();
        } finally {
            $this->isApplyingEdit = false;
        }
    }

    /**
     * Check for pending jobs on page load.
     */
    #[On('check-pending-jobs')]
    public function checkPendingJobs(): void
    {
        if (!empty($this->pendingJobs)) {
            $this->dispatch('poll-status', ['pendingJobs' => count($this->pendingJobs)]);
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('appvideowizard::livewire.video-wizard', [
            'platforms' => config('appvideowizard.platforms'),
            'formats' => config('appvideowizard.formats'),
            'productionTypes' => config('appvideowizard.production_types'),
            'captionStyles' => config('appvideowizard.caption_styles'),
            'stepTitles' => $this->getStepTitles(),
        ]);
    }
}
