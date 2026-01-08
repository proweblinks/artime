<?php

return [
    'name' => 'AppVideoWizard',

    /*
    |--------------------------------------------------------------------------
    | Platform Presets
    |--------------------------------------------------------------------------
    | Video platform configurations with resolution, duration limits, and settings
    */
    'platforms' => [
        'youtube-long' => [
            'id' => 'youtube-long',
            'name' => 'YouTube Long-form',
            'icon' => 'fa-brands fa-youtube',
            'formats' => ['16:9'],
            'defaultFormat' => '16:9',
            'resolution' => ['width' => 1920, 'height' => 1080],
            'maxDuration' => 300,
            'minDuration' => 60,
            'fps' => 30,
            'bitrate' => '8M',
            'description' => 'Standard videos up to 5 min',
        ],
        'youtube-shorts' => [
            'id' => 'youtube-shorts',
            'name' => 'YouTube Shorts',
            'icon' => 'fa-brands fa-youtube',
            'formats' => ['9:16'],
            'defaultFormat' => '9:16',
            'resolution' => ['width' => 1080, 'height' => 1920],
            'maxDuration' => 60,
            'minDuration' => 15,
            'fps' => 30,
            'bitrate' => '4M',
            'description' => 'Vertical shorts up to 60s',
        ],
        'tiktok' => [
            'id' => 'tiktok',
            'name' => 'TikTok',
            'icon' => 'fa-brands fa-tiktok',
            'formats' => ['9:16', '16:9', '1:1'],
            'defaultFormat' => '9:16',
            'resolution' => ['width' => 1080, 'height' => 1920],
            'maxDuration' => 180,
            'minDuration' => 15,
            'fps' => 30,
            'bitrate' => '4M',
            'description' => 'Viral content up to 3 min',
        ],
        'instagram-reels' => [
            'id' => 'instagram-reels',
            'name' => 'Instagram Reels',
            'icon' => 'fa-brands fa-instagram',
            'formats' => ['9:16'],
            'defaultFormat' => '9:16',
            'resolution' => ['width' => 1080, 'height' => 1920],
            'maxDuration' => 180,
            'minDuration' => 15,
            'fps' => 30,
            'bitrate' => '4M',
            'description' => 'Reels up to 3 min',
        ],
        'instagram-feed' => [
            'id' => 'instagram-feed',
            'name' => 'Instagram Feed',
            'icon' => 'fa-brands fa-instagram',
            'formats' => ['1:1', '4:5', '16:9'],
            'defaultFormat' => '1:1',
            'resolution' => ['width' => 1080, 'height' => 1080],
            'maxDuration' => 60,
            'minDuration' => 3,
            'fps' => 30,
            'bitrate' => '4M',
            'description' => 'Feed videos up to 60s',
        ],
        'facebook-reels' => [
            'id' => 'facebook-reels',
            'name' => 'Facebook Reels',
            'icon' => 'fa-brands fa-facebook',
            'formats' => ['9:16'],
            'defaultFormat' => '9:16',
            'resolution' => ['width' => 1080, 'height' => 1920],
            'maxDuration' => 90,
            'minDuration' => 3,
            'fps' => 30,
            'bitrate' => '4M',
            'description' => 'Reels up to 90s',
        ],
        'facebook-feed' => [
            'id' => 'facebook-feed',
            'name' => 'Facebook Feed',
            'icon' => 'fa-brands fa-facebook',
            'formats' => ['16:9', '1:1', '9:16'],
            'defaultFormat' => '16:9',
            'resolution' => ['width' => 1920, 'height' => 1080],
            'maxDuration' => 300,
            'minDuration' => 3,
            'fps' => 30,
            'bitrate' => '6M',
            'description' => 'Feed videos up to 5 min',
        ],
        'linkedin' => [
            'id' => 'linkedin',
            'name' => 'LinkedIn',
            'icon' => 'fa-brands fa-linkedin',
            'formats' => ['16:9', '1:1', '4:5'],
            'defaultFormat' => '4:5',
            'resolution' => ['width' => 1080, 'height' => 1350],
            'maxDuration' => 300,
            'minDuration' => 3,
            'fps' => 30,
            'bitrate' => '5M',
            'description' => 'Professional videos up to 5 min',
        ],
        'multi-platform' => [
            'id' => 'multi-platform',
            'name' => 'Multi-Platform',
            'icon' => 'fa-solid fa-globe',
            'formats' => ['9:16', '16:9', '1:1'],
            'defaultFormat' => '9:16',
            'resolution' => ['width' => 1080, 'height' => 1920],
            'maxDuration' => 60,
            'minDuration' => 15,
            'fps' => 30,
            'bitrate' => '4M',
            'description' => 'Optimized for all platforms',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Format Presets
    |--------------------------------------------------------------------------
    | Aspect ratio configurations with resolutions
    */
    'formats' => [
        'widescreen' => [
            'id' => 'widescreen',
            'name' => 'Widescreen',
            'icon' => 'fa-solid fa-desktop',
            'aspectRatio' => '16:9',
            'description' => 'YouTube, TV, Movies',
            'resolution' => ['width' => 1920, 'height' => 1080],
            'resolution4k' => ['width' => 3840, 'height' => 2160],
        ],
        'vertical' => [
            'id' => 'vertical',
            'name' => 'Vertical',
            'icon' => 'fa-solid fa-mobile-screen',
            'aspectRatio' => '9:16',
            'description' => 'TikTok, Reels, Shorts',
            'resolution' => ['width' => 1080, 'height' => 1920],
            'resolution4k' => ['width' => 2160, 'height' => 3840],
        ],
        'square' => [
            'id' => 'square',
            'name' => 'Square',
            'icon' => 'fa-solid fa-square',
            'aspectRatio' => '1:1',
            'description' => 'Instagram Feed',
            'resolution' => ['width' => 1080, 'height' => 1080],
            'resolution4k' => ['width' => 2160, 'height' => 2160],
        ],
        'tall' => [
            'id' => 'tall',
            'name' => 'Tall',
            'icon' => 'fa-solid fa-rectangle-vertical',
            'aspectRatio' => '4:5',
            'description' => 'Instagram Portrait',
            'resolution' => ['width' => 1080, 'height' => 1350],
            'resolution4k' => ['width' => 2160, 'height' => 2700],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Production Types
    |--------------------------------------------------------------------------
    | Content production type configurations
    */
    'production_types' => [
        'social' => [
            'id' => 'social',
            'name' => 'Social Content',
            'icon' => 'fa-solid fa-mobile',
            'description' => 'Short-form content for social platforms',
            'subTypes' => [
                'viral' => [
                    'id' => 'viral',
                    'name' => 'Viral/Trending',
                    'icon' => 'fa-solid fa-fire',
                    'description' => 'Quick hook, shareable content',
                    'characteristics' => ['quick-hook', 'shareable', 'trend-based'],
                    'defaultNarration' => 'voiceover',
                    'suggestedDuration' => ['min' => 15, 'max' => 60],
                ],
                'educational-short' => [
                    'id' => 'educational-short',
                    'name' => 'Quick Explainer',
                    'icon' => 'fa-solid fa-lightbulb',
                    'description' => 'Informative, concise explanations',
                    'characteristics' => ['informative', 'concise', 'visual'],
                    'defaultNarration' => 'voiceover',
                    'suggestedDuration' => ['min' => 30, 'max' => 180],
                ],
                'story-short' => [
                    'id' => 'story-short',
                    'name' => 'Story/Narrative',
                    'icon' => 'fa-solid fa-book-open',
                    'description' => 'Engaging storytelling format',
                    'characteristics' => ['narrative', 'emotional', 'engaging'],
                    'defaultNarration' => 'voiceover',
                    'suggestedDuration' => ['min' => 30, 'max' => 120],
                ],
                'product' => [
                    'id' => 'product',
                    'name' => 'Product Showcase',
                    'icon' => 'fa-solid fa-box',
                    'description' => 'Product demonstrations and reviews',
                    'characteristics' => ['promotional', 'visual', 'persuasive'],
                    'defaultNarration' => 'voiceover',
                    'suggestedDuration' => ['min' => 15, 'max' => 90],
                ],
                'lifestyle' => [
                    'id' => 'lifestyle',
                    'name' => 'Lifestyle',
                    'icon' => 'fa-solid fa-heart',
                    'description' => 'Day-in-the-life, routines, tips',
                    'characteristics' => ['relatable', 'aspirational', 'personal'],
                    'defaultNarration' => 'voiceover',
                    'suggestedDuration' => ['min' => 30, 'max' => 180],
                ],
                'meme-comedy' => [
                    'id' => 'meme-comedy',
                    'name' => 'Meme/Comedy',
                    'icon' => 'fa-solid fa-face-laugh',
                    'description' => 'Funny, meme-based content',
                    'characteristics' => ['humorous', 'relatable', 'shareable'],
                    'defaultNarration' => 'voiceover',
                    'suggestedDuration' => ['min' => 10, 'max' => 60],
                ],
            ],
        ],
        'movie' => [
            'id' => 'movie',
            'name' => 'Movie/Film',
            'icon' => 'fa-solid fa-film',
            'description' => 'Cinematic narrative storytelling',
            'subTypes' => [
                'action' => [
                    'id' => 'action',
                    'name' => 'Action',
                    'icon' => '💥',
                    'description' => 'High-octane, physical, thrilling',
                    'characteristics' => ['dynamic', 'fast-paced', 'exciting'],
                    'defaultNarration' => 'dialogue',
                    'suggestedDuration' => ['min' => 120, 'max' => 600],
                ],
                'drama' => [
                    'id' => 'drama',
                    'name' => 'Drama',
                    'icon' => '🎭',
                    'description' => 'Character-driven, emotional storytelling',
                    'characteristics' => ['emotional', 'character-focused', 'narrative'],
                    'defaultNarration' => 'dialogue',
                    'suggestedDuration' => ['min' => 120, 'max' => 600],
                ],
                'thriller' => [
                    'id' => 'thriller',
                    'name' => 'Thriller/Suspense',
                    'icon' => '🔮',
                    'description' => 'Tension, mystery, psychological depth',
                    'characteristics' => ['suspenseful', 'mysterious', 'engaging'],
                    'defaultNarration' => 'dialogue',
                    'suggestedDuration' => ['min' => 120, 'max' => 600],
                ],
                'horror' => [
                    'id' => 'horror',
                    'name' => 'Horror',
                    'icon' => '👻',
                    'description' => 'Fear, dread, supernatural terror',
                    'characteristics' => ['scary', 'atmospheric', 'suspense'],
                    'defaultNarration' => 'dialogue',
                    'suggestedDuration' => ['min' => 90, 'max' => 600],
                ],
                'scifi' => [
                    'id' => 'scifi',
                    'name' => 'Sci-Fi',
                    'icon' => '🚀',
                    'description' => 'Futuristic, speculative, technological',
                    'characteristics' => ['speculative', 'technology', 'wonder'],
                    'defaultNarration' => 'dialogue',
                    'suggestedDuration' => ['min' => 120, 'max' => 600],
                ],
                'comedy' => [
                    'id' => 'comedy',
                    'name' => 'Comedy',
                    'icon' => '😄',
                    'description' => 'Humor, wit, comedic timing',
                    'characteristics' => ['funny', 'timing', 'character-comedy'],
                    'defaultNarration' => 'dialogue',
                    'suggestedDuration' => ['min' => 90, 'max' => 600],
                ],
            ],
        ],
        'series' => [
            'id' => 'series',
            'name' => 'Series/Episodes',
            'icon' => 'fa-solid fa-tv',
            'description' => 'Episodic storytelling with story arcs',
            'subTypes' => [
                'episode' => [
                    'id' => 'episode',
                    'name' => 'Single Episode',
                    'icon' => '📺',
                    'description' => 'One complete episode',
                    'characteristics' => ['episodic', 'arc-based', 'cliffhanger'],
                    'defaultNarration' => 'dialogue',
                    'suggestedDuration' => ['min' => 180, 'max' => 600],
                ],
                'mini-series' => [
                    'id' => 'mini-series',
                    'name' => 'Mini-Series',
                    'icon' => '🎞️',
                    'description' => 'Short multi-part series',
                    'characteristics' => ['serialized', 'connected', 'bingeable'],
                    'defaultNarration' => 'dialogue',
                    'suggestedDuration' => ['min' => 120, 'max' => 600],
                ],
            ],
        ],
        'educational' => [
            'id' => 'educational',
            'name' => 'Educational',
            'icon' => 'fa-solid fa-graduation-cap',
            'description' => 'Learning and informative content',
            'subTypes' => [
                'tutorial' => [
                    'id' => 'tutorial',
                    'name' => 'Tutorial',
                    'icon' => '📚',
                    'description' => 'Step-by-step instructions',
                    'characteristics' => ['instructional', 'clear', 'practical'],
                    'defaultNarration' => 'voiceover',
                    'suggestedDuration' => ['min' => 60, 'max' => 300],
                ],
                'explainer' => [
                    'id' => 'explainer',
                    'name' => 'Explainer',
                    'icon' => '💡',
                    'description' => 'Concept explanations',
                    'characteristics' => ['informative', 'visual', 'simplified'],
                    'defaultNarration' => 'voiceover',
                    'suggestedDuration' => ['min' => 60, 'max' => 300],
                ],
                'documentary' => [
                    'id' => 'documentary',
                    'name' => 'Documentary',
                    'icon' => '🎬',
                    'description' => 'In-depth exploration of topics',
                    'characteristics' => ['journalistic', 'authentic', 'in-depth'],
                    'defaultNarration' => 'voiceover',
                    'suggestedDuration' => ['min' => 180, 'max' => 600],
                ],
            ],
        ],
        'music' => [
            'id' => 'music',
            'name' => 'Music Video',
            'icon' => 'fa-solid fa-music',
            'description' => 'Visual accompaniment to music',
            'subTypes' => [
                'narrative' => [
                    'id' => 'narrative',
                    'name' => 'Narrative',
                    'icon' => '🎭',
                    'description' => 'Story-driven music video',
                    'characteristics' => ['story', 'emotional', 'cinematic'],
                    'defaultNarration' => 'music',
                    'suggestedDuration' => ['min' => 180, 'max' => 300],
                ],
                'performance' => [
                    'id' => 'performance',
                    'name' => 'Performance',
                    'icon' => '🎤',
                    'description' => 'Artist/band performance focused',
                    'characteristics' => ['performance', 'energy', 'visual'],
                    'defaultNarration' => 'music',
                    'suggestedDuration' => ['min' => 180, 'max' => 300],
                ],
                'lyric' => [
                    'id' => 'lyric',
                    'name' => 'Lyric Video',
                    'icon' => '📝',
                    'description' => 'Animated lyrics with visuals',
                    'characteristics' => ['typography', 'animated', 'rhythmic'],
                    'defaultNarration' => 'music',
                    'suggestedDuration' => ['min' => 180, 'max' => 300],
                ],
            ],
        ],
        'commercial' => [
            'id' => 'commercial',
            'name' => 'Commercial/Promo',
            'icon' => 'fa-solid fa-bullhorn',
            'description' => 'Promotional and advertising content',
            'subTypes' => [
                'product-ad' => [
                    'id' => 'product-ad',
                    'name' => 'Product Ad',
                    'icon' => '🛍️',
                    'description' => 'Product showcase and promotion',
                    'characteristics' => ['promotional', 'persuasive', 'visual'],
                    'defaultNarration' => 'voiceover',
                    'suggestedDuration' => ['min' => 15, 'max' => 60],
                ],
                'brand' => [
                    'id' => 'brand',
                    'name' => 'Brand Story',
                    'icon' => '🏢',
                    'description' => 'Brand identity and values',
                    'characteristics' => ['emotional', 'brand-focused', 'premium'],
                    'defaultNarration' => 'voiceover',
                    'suggestedDuration' => ['min' => 30, 'max' => 120],
                ],
                'testimonial' => [
                    'id' => 'testimonial',
                    'name' => 'Testimonial',
                    'icon' => '⭐',
                    'description' => 'Customer reviews and stories',
                    'characteristics' => ['authentic', 'trust-building', 'relatable'],
                    'defaultNarration' => 'dialogue',
                    'suggestedDuration' => ['min' => 30, 'max' => 120],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caption Styles
    |--------------------------------------------------------------------------
    | Video caption styling configurations
    */
    'caption_styles' => [
        'karaoke' => [
            'id' => 'karaoke',
            'name' => 'Karaoke',
            'description' => 'Word-by-word highlight animation',
            'fontFamily' => 'Montserrat',
            'fontWeight' => 700,
            'fillColor' => '#FFFFFF',
            'highlightColor' => '#FBBF24',
            'strokeColor' => '#000000',
            'strokeWidth' => 2,
            'wordsPerLine' => 4,
        ],
        'beasty' => [
            'id' => 'beasty',
            'name' => 'MrBeast Style',
            'description' => 'Bold, uppercase, high impact',
            'fontFamily' => 'Impact',
            'fontWeight' => 700,
            'fillColor' => '#FBBF24',
            'highlightColor' => '#FFFFFF',
            'strokeColor' => '#000000',
            'strokeWidth' => 3,
            'wordsPerLine' => 3,
            'uppercase' => true,
        ],
        'hormozi' => [
            'id' => 'hormozi',
            'name' => 'Hormozi Style',
            'description' => 'Clean, professional, centered',
            'fontFamily' => 'Inter',
            'fontWeight' => 600,
            'fillColor' => '#FFFFFF',
            'highlightColor' => '#3B82F6',
            'strokeColor' => '#000000',
            'strokeWidth' => 1,
            'wordsPerLine' => 4,
        ],
        'ali' => [
            'id' => 'ali',
            'name' => 'Ali Abdaal Style',
            'description' => 'Soft glow, elegant',
            'fontFamily' => 'Inter',
            'fontWeight' => 500,
            'fillColor' => '#FFFFFF',
            'highlightColor' => '#10B981',
            'strokeColor' => 'transparent',
            'strokeWidth' => 0,
            'wordsPerLine' => 5,
            'glow' => true,
        ],
        'podcast' => [
            'id' => 'podcast',
            'name' => 'Podcast Style',
            'description' => 'Clean, readable, minimal',
            'fontFamily' => 'Roboto',
            'fontWeight' => 500,
            'fillColor' => '#FFFFFF',
            'highlightColor' => '#FBBF24',
            'strokeColor' => '#000000',
            'strokeWidth' => 1,
            'wordsPerLine' => 5,
        ],
        'minimal' => [
            'id' => 'minimal',
            'name' => 'Minimal',
            'description' => 'Subtle, unobtrusive captions',
            'fontFamily' => 'Inter',
            'fontWeight' => 400,
            'fillColor' => '#FFFFFF',
            'highlightColor' => '#FFFFFF',
            'strokeColor' => 'transparent',
            'strokeWidth' => 0,
            'wordsPerLine' => 6,
            'opacity' => 0.9,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transition Types
    |--------------------------------------------------------------------------
    | Scene transition configurations
    */
    'transitions' => [
        'cut' => ['id' => 'cut', 'name' => 'Cut', 'duration' => 0, 'icon' => '✂️'],
        'fade' => ['id' => 'fade', 'name' => 'Fade', 'duration' => 0.5, 'icon' => '🌫️'],
        'dissolve' => ['id' => 'dissolve', 'name' => 'Dissolve', 'duration' => 0.8, 'icon' => '💫'],
        'wipe' => ['id' => 'wipe', 'name' => 'Wipe', 'duration' => 0.5, 'icon' => '➡️'],
        'slide-left' => ['id' => 'slide-left', 'name' => 'Slide Left', 'duration' => 0.5, 'icon' => '⬅️'],
        'slide-right' => ['id' => 'slide-right', 'name' => 'Slide Right', 'duration' => 0.5, 'icon' => '➡️'],
        'zoom-in' => ['id' => 'zoom-in', 'name' => 'Zoom In', 'duration' => 0.5, 'icon' => '🔍'],
        'zoom-out' => ['id' => 'zoom-out', 'name' => 'Zoom Out', 'duration' => 0.5, 'icon' => '🔎'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Narration Styles
    |--------------------------------------------------------------------------
    | Character Intelligence narration style configurations
    */
    'narration_styles' => [
        'voiceover' => [
            'id' => 'voiceover',
            'name' => 'Voiceover',
            'icon' => '🎙️',
            'description' => 'Narrator speaks over visuals',
            'requiresVoice' => true,
            'requiresCharacters' => false,
        ],
        'dialogue' => [
            'id' => 'dialogue',
            'name' => 'Dialogue',
            'icon' => '💬',
            'description' => 'Characters speak to each other',
            'requiresVoice' => true,
            'requiresCharacters' => true,
        ],
        'narrator' => [
            'id' => 'narrator',
            'name' => 'Narrator',
            'icon' => '📖',
            'description' => 'Third-person storytelling voice',
            'requiresVoice' => true,
            'requiresCharacters' => false,
        ],
        'none' => [
            'id' => 'none',
            'name' => 'No Voice',
            'icon' => '🔇',
            'description' => 'Music/ambient only - no spoken words',
            'requiresVoice' => false,
            'requiresCharacters' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Voice Options
    |--------------------------------------------------------------------------
    | Available voices for voiceover generation
    */
    'voices' => [
        'alloy' => ['id' => 'alloy', 'name' => 'Alloy', 'gender' => 'neutral', 'style' => 'versatile', 'icon' => '🎭'],
        'echo' => ['id' => 'echo', 'name' => 'Echo', 'gender' => 'male', 'style' => 'warm', 'icon' => '🎤'],
        'fable' => ['id' => 'fable', 'name' => 'Fable', 'gender' => 'neutral', 'style' => 'storytelling', 'icon' => '📚'],
        'onyx' => ['id' => 'onyx', 'name' => 'Onyx', 'gender' => 'male', 'style' => 'deep', 'icon' => '🎵'],
        'nova' => ['id' => 'nova', 'name' => 'Nova', 'gender' => 'female', 'style' => 'friendly', 'icon' => '✨'],
        'shimmer' => ['id' => 'shimmer', 'name' => 'Shimmer', 'gender' => 'female', 'style' => 'bright', 'icon' => '💫'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Narrative Structure Intelligence
    |--------------------------------------------------------------------------
    | Story arcs, presets, tension curves, and emotional journeys for
    | Hollywood production level script generation
    */

    // Story Arc Structures - How the narrative unfolds
    'story_arcs' => [
        'three-act' => [
            'id' => 'three-act',
            'name' => 'Three-Act Structure',
            'icon' => '🎬',
            'description' => 'Classic Hollywood: Setup → Confrontation → Resolution',
            'structure' => ['setup' => 25, 'confrontation' => 50, 'resolution' => 25],
            'beats' => ['hook', 'inciting_incident', 'rising_action', 'midpoint', 'crisis', 'climax', 'resolution'],
        ],
        'five-act' => [
            'id' => 'five-act',
            'name' => 'Five-Act Structure',
            'icon' => '🎭',
            'description' => 'Shakespearean: Exposition → Rising → Climax → Falling → Resolution',
            'structure' => ['exposition' => 15, 'rising' => 25, 'climax' => 20, 'falling' => 25, 'resolution' => 15],
            'beats' => ['exposition', 'rising_action', 'climax', 'falling_action', 'denouement'],
        ],
        'heros-journey' => [
            'id' => 'heros-journey',
            'name' => "Hero's Journey",
            'icon' => '⚔️',
            'description' => "Campbell's monomyth: Ordinary World → Adventure → Transformation → Return",
            'structure' => ['departure' => 25, 'initiation' => 50, 'return' => 25],
            'beats' => ['ordinary_world', 'call_to_adventure', 'threshold', 'trials', 'ordeal', 'reward', 'return'],
        ],
        'story-circle' => [
            'id' => 'story-circle',
            'name' => 'Story Circle',
            'icon' => '🔄',
            'description' => "Dan Harmon's 8-step: You → Need → Go → Search → Find → Take → Return → Change",
            'structure' => ['comfort' => 12, 'desire' => 12, 'unfamiliar' => 13, 'adapt' => 13, 'find' => 12, 'take' => 13, 'return' => 12, 'change' => 13],
            'beats' => ['comfort_zone', 'desire', 'unfamiliar_situation', 'adapt', 'get_what_wanted', 'pay_price', 'return', 'change'],
        ],
        'freytags-pyramid' => [
            'id' => 'freytags-pyramid',
            'name' => "Freytag's Pyramid",
            'icon' => '📐',
            'description' => 'Dramatic arc: Exposition → Rise → Climax → Fall → Catastrophe',
            'structure' => ['exposition' => 15, 'rising' => 30, 'climax' => 10, 'falling' => 30, 'catastrophe' => 15],
            'beats' => ['exposition', 'rising_action', 'climax', 'falling_action', 'catastrophe'],
        ],
        'kishotenketsu' => [
            'id' => 'kishotenketsu',
            'name' => 'Kishotenketsu',
            'icon' => '🎌',
            'description' => 'East Asian 4-part: Introduction → Development → Twist → Conclusion',
            'structure' => ['ki' => 25, 'sho' => 25, 'ten' => 25, 'ketsu' => 25],
            'beats' => ['introduction', 'development', 'twist', 'conclusion'],
        ],
        'youtube-retention' => [
            'id' => 'youtube-retention',
            'name' => 'YouTube Retention',
            'icon' => '📈',
            'description' => 'Optimized for watch time: Hook → Promise → Deliver → Reward loops',
            'structure' => ['hook' => 5, 'promise' => 10, 'deliver' => 70, 'cta' => 15],
            'beats' => ['hook', 'tease', 'value_1', 'pattern_break', 'value_2', 'pattern_break', 'value_3', 'cta'],
        ],
        'tiktok-viral' => [
            'id' => 'tiktok-viral',
            'name' => 'TikTok Viral',
            'icon' => '⚡',
            'description' => 'Stop scroll immediately → Build to payoff at 80% → Loop-worthy ending',
            'structure' => ['hook' => 10, 'build' => 70, 'payoff' => 15, 'loop' => 5],
            'beats' => ['scroll_stop', 'promise', 'buildup', 'payoff', 'loop_point'],
        ],
        'problem-solution' => [
            'id' => 'problem-solution',
            'name' => 'Problem-Solution',
            'icon' => '💡',
            'description' => 'Educational: Problem → Agitate → Solution → Result',
            'structure' => ['problem' => 25, 'agitate' => 25, 'solution' => 35, 'result' => 15],
            'beats' => ['problem_intro', 'pain_points', 'solution_reveal', 'how_it_works', 'results'],
        ],
        'before-after-bridge' => [
            'id' => 'before-after-bridge',
            'name' => 'Before-After-Bridge',
            'icon' => '🌉',
            'description' => 'Transformation: Current state → Dream state → How to get there',
            'structure' => ['before' => 30, 'after' => 30, 'bridge' => 40],
            'beats' => ['current_pain', 'dream_outcome', 'solution_path', 'action_steps'],
        ],
        'inverted-pyramid' => [
            'id' => 'inverted-pyramid',
            'name' => 'Inverted Pyramid',
            'icon' => '📰',
            'description' => 'News style: Most important first → Supporting details → Background',
            'structure' => ['lead' => 20, 'body' => 50, 'tail' => 30],
            'beats' => ['key_fact', 'important_details', 'supporting_info', 'background'],
        ],
        'documentary' => [
            'id' => 'documentary',
            'name' => 'Documentary',
            'icon' => '🎥',
            'description' => 'Evidence-based: Setup → Investigation → Discovery → Reflection',
            'structure' => ['setup' => 15, 'investigation' => 40, 'discovery' => 30, 'reflection' => 15],
            'beats' => ['opening_hook', 'context', 'evidence', 'interviews', 'revelation', 'conclusion'],
        ],
    ],

    // Narrative Presets - Platform-optimized storytelling formulas
    'narrative_presets' => [
        'youtube-standard' => [
            'id' => 'youtube-standard',
            'name' => 'YouTube Standard',
            'icon' => '📺',
            'description' => 'Hook in 5s, pattern breaks every 45-60s, strong CTA ending',
            'defaultArc' => 'youtube-retention',
            'defaultTension' => 'waves',
            'hookTiming' => 5,
            'patternBreakInterval' => 45,
            'ctaPosition' => 90,
            'tips' => 'Use open loops, tease upcoming content, deliver value consistently',
        ],
        'tiktok-viral' => [
            'id' => 'tiktok-viral',
            'name' => 'TikTok Viral',
            'icon' => '⚡',
            'description' => 'Stop the scroll immediately, build to payoff at 80%, loop-worthy ending',
            'defaultArc' => 'tiktok-viral',
            'defaultTension' => 'steady-build',
            'hookTiming' => 0,
            'payoffPosition' => 80,
            'loopFriendly' => true,
            'tips' => 'First frame must hook, use trending sounds, create share-worthy moment',
        ],
        'cinematic-short' => [
            'id' => 'cinematic-short',
            'name' => 'Cinematic Short',
            'icon' => '🎬',
            'description' => 'Character development focus, slow-burn tension, emotional resolution',
            'defaultArc' => 'three-act',
            'defaultTension' => 'slow-burn',
            'defaultEmotion' => 'triumph',
            'pacing' => 'deliberate',
            'tips' => 'Focus on visual storytelling, let moments breathe, build emotional investment',
        ],
        'documentary-feature' => [
            'id' => 'documentary-feature',
            'name' => 'Documentary',
            'icon' => '📽️',
            'description' => 'Evidence-based reveals, interview integration, reflective ending',
            'defaultArc' => 'documentary',
            'defaultTension' => 'escalating-steps',
            'defaultEmotion' => 'educational',
            'tips' => 'Build credibility with facts, use expert voices, leave viewer thinking',
        ],
        'series-episode' => [
            'id' => 'series-episode',
            'name' => 'Series Episode',
            'icon' => '📺',
            'description' => 'Cliffhanger ending, serial elements, double-peak tension',
            'defaultArc' => 'five-act',
            'defaultTension' => 'double-peak',
            'cliffhangerEnding' => true,
            'tips' => 'Reference previous episodes, set up future plots, end on hook',
        ],
        'commercial-spot' => [
            'id' => 'commercial-spot',
            'name' => 'Commercial/Ad',
            'icon' => '📢',
            'description' => 'Brand reveal at 80%, problem-solution arc, strong CTA',
            'defaultArc' => 'problem-solution',
            'defaultTension' => 'steady-build',
            'brandRevealPosition' => 80,
            'tips' => 'Lead with emotion, delay brand reveal, clear single CTA',
        ],
        'thriller-short' => [
            'id' => 'thriller-short',
            'name' => 'Thriller',
            'icon' => '😰',
            'description' => 'Twist at 75%, ratcheting tension, revelation climax',
            'defaultArc' => 'freytags-pyramid',
            'defaultTension' => 'rollercoaster',
            'defaultEmotion' => 'thriller',
            'twistPosition' => 75,
            'tips' => 'Plant clues early, misdirect attention, satisfying twist reveal',
        ],
        'horror-short' => [
            'id' => 'horror-short',
            'name' => 'Horror',
            'icon' => '👻',
            'description' => 'Scares every 45s, flat-with-spikes tension, ambiguous ending',
            'defaultArc' => 'three-act',
            'defaultTension' => 'flat-with-spikes',
            'defaultEmotion' => 'horror',
            'scareInterval' => 45,
            'tips' => 'Build dread, use silence effectively, leave questions unanswered',
        ],
        'inspirational' => [
            'id' => 'inspirational',
            'name' => 'Inspirational',
            'icon' => '🌟',
            'description' => 'Transformation arc, escalating emotional peaks, uplifting ending',
            'defaultArc' => 'heros-journey',
            'defaultTension' => 'steady-build',
            'defaultEmotion' => 'triumph',
            'tips' => 'Start with struggle, show growth moments, end on high note',
        ],
        'educational-explainer' => [
            'id' => 'educational-explainer',
            'name' => 'Explainer',
            'icon' => '📚',
            'description' => 'Clear structure, visual aids, knowledge retention focus',
            'defaultArc' => 'problem-solution',
            'defaultTension' => 'escalating-steps',
            'defaultEmotion' => 'educational',
            'tips' => 'Chunk information, use analogies, summarize key points',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Production Type to Preset Mapping
    |--------------------------------------------------------------------------
    | Maps production types/subtypes to recommended narrative presets.
    | This enables cascading selection: Step 1 choices influence Step 3 options.
    |
    | Structure:
    | - 'default' => The preset auto-selected when this production type is chosen
    | - 'recommended' => Array of presets shown as primary options
    | - 'compatible' => Array of presets that work but aren't primary
    | - 'hidden' => Array of presets that don't make sense for this type
    */
    'production_preset_mapping' => [
        // === SOCIAL CONTENT ===
        'social' => [
            '_default' => [
                'default' => 'tiktok-viral',
                'recommended' => ['tiktok-viral', 'youtube-standard'],
                'compatible' => ['inspirational', 'educational-explainer'],
                'hidden' => ['cinematic-short', 'documentary-feature', 'series-episode', 'thriller-short', 'horror-short'],
            ],
            'viral' => [
                'default' => 'tiktok-viral',
                'recommended' => ['tiktok-viral'],
                'compatible' => ['youtube-standard'],
                'hidden' => ['cinematic-short', 'documentary-feature', 'series-episode', 'thriller-short', 'horror-short'],
            ],
            'educational-short' => [
                'default' => 'educational-explainer',
                'recommended' => ['educational-explainer', 'youtube-standard'],
                'compatible' => ['tiktok-viral'],
                'hidden' => ['cinematic-short', 'documentary-feature', 'series-episode', 'thriller-short', 'horror-short'],
            ],
            'story-short' => [
                'default' => 'tiktok-viral',
                'recommended' => ['tiktok-viral', 'inspirational'],
                'compatible' => ['youtube-standard'],
                'hidden' => ['documentary-feature', 'series-episode'],
            ],
            'product' => [
                'default' => 'commercial-spot',
                'recommended' => ['commercial-spot', 'tiktok-viral'],
                'compatible' => ['youtube-standard'],
                'hidden' => ['cinematic-short', 'documentary-feature', 'series-episode', 'thriller-short', 'horror-short'],
            ],
            'lifestyle' => [
                'default' => 'youtube-standard',
                'recommended' => ['youtube-standard', 'tiktok-viral', 'inspirational'],
                'compatible' => [],
                'hidden' => ['documentary-feature', 'series-episode', 'thriller-short', 'horror-short'],
            ],
            'meme-comedy' => [
                'default' => 'tiktok-viral',
                'recommended' => ['tiktok-viral'],
                'compatible' => ['youtube-standard'],
                'hidden' => ['cinematic-short', 'documentary-feature', 'series-episode', 'thriller-short', 'horror-short', 'inspirational'],
            ],
        ],

        // === MOVIE/FILM ===
        'movie' => [
            '_default' => [
                'default' => 'cinematic-short',
                'recommended' => ['cinematic-short', 'inspirational'],
                'compatible' => ['thriller-short', 'horror-short'],
                'hidden' => ['youtube-standard', 'tiktok-viral', 'commercial-spot', 'educational-explainer'],
            ],
            'action' => [
                'default' => 'cinematic-short',
                'recommended' => ['cinematic-short'],
                'compatible' => ['thriller-short', 'inspirational'],
                'hidden' => ['youtube-standard', 'tiktok-viral', 'documentary-feature', 'commercial-spot', 'educational-explainer', 'horror-short'],
            ],
            'drama' => [
                'default' => 'cinematic-short',
                'recommended' => ['cinematic-short', 'inspirational'],
                'compatible' => ['documentary-feature'],
                'hidden' => ['youtube-standard', 'tiktok-viral', 'commercial-spot', 'educational-explainer', 'thriller-short', 'horror-short'],
            ],
            'thriller' => [
                'default' => 'thriller-short',
                'recommended' => ['thriller-short', 'cinematic-short'],
                'compatible' => [],
                'hidden' => ['youtube-standard', 'tiktok-viral', 'documentary-feature', 'commercial-spot', 'educational-explainer', 'inspirational', 'horror-short'],
            ],
            'horror' => [
                'default' => 'horror-short',
                'recommended' => ['horror-short'],
                'compatible' => ['thriller-short', 'cinematic-short'],
                'hidden' => ['youtube-standard', 'tiktok-viral', 'documentary-feature', 'commercial-spot', 'educational-explainer', 'inspirational'],
            ],
            'scifi' => [
                'default' => 'cinematic-short',
                'recommended' => ['cinematic-short'],
                'compatible' => ['thriller-short', 'inspirational'],
                'hidden' => ['youtube-standard', 'tiktok-viral', 'documentary-feature', 'commercial-spot', 'educational-explainer', 'horror-short'],
            ],
            'comedy' => [
                'default' => 'cinematic-short',
                'recommended' => ['cinematic-short'],
                'compatible' => ['inspirational'],
                'hidden' => ['youtube-standard', 'tiktok-viral', 'documentary-feature', 'commercial-spot', 'educational-explainer', 'thriller-short', 'horror-short'],
            ],
        ],

        // === SERIES/EPISODES ===
        'series' => [
            '_default' => [
                'default' => 'series-episode',
                'recommended' => ['series-episode'],
                'compatible' => ['cinematic-short', 'thriller-short', 'horror-short'],
                'hidden' => ['youtube-standard', 'tiktok-viral', 'commercial-spot', 'educational-explainer'],
            ],
            'episode' => [
                'default' => 'series-episode',
                'recommended' => ['series-episode'],
                'compatible' => ['cinematic-short', 'thriller-short', 'horror-short'],
                'hidden' => ['youtube-standard', 'tiktok-viral', 'documentary-feature', 'commercial-spot', 'educational-explainer'],
            ],
            'mini-series' => [
                'default' => 'series-episode',
                'recommended' => ['series-episode'],
                'compatible' => ['cinematic-short', 'documentary-feature'],
                'hidden' => ['youtube-standard', 'tiktok-viral', 'commercial-spot', 'educational-explainer'],
            ],
        ],

        // === EDUCATIONAL ===
        'educational' => [
            '_default' => [
                'default' => 'educational-explainer',
                'recommended' => ['educational-explainer', 'documentary-feature'],
                'compatible' => ['youtube-standard', 'inspirational'],
                'hidden' => ['tiktok-viral', 'commercial-spot', 'thriller-short', 'horror-short', 'cinematic-short'],
            ],
            'tutorial' => [
                'default' => 'educational-explainer',
                'recommended' => ['educational-explainer'],
                'compatible' => ['youtube-standard'],
                'hidden' => ['tiktok-viral', 'documentary-feature', 'commercial-spot', 'cinematic-short', 'thriller-short', 'horror-short', 'series-episode'],
            ],
            'explainer' => [
                'default' => 'educational-explainer',
                'recommended' => ['educational-explainer'],
                'compatible' => ['youtube-standard', 'documentary-feature'],
                'hidden' => ['tiktok-viral', 'commercial-spot', 'cinematic-short', 'thriller-short', 'horror-short', 'series-episode'],
            ],
            'documentary' => [
                'default' => 'documentary-feature',
                'recommended' => ['documentary-feature'],
                'compatible' => ['educational-explainer', 'inspirational'],
                'hidden' => ['tiktok-viral', 'youtube-standard', 'commercial-spot', 'cinematic-short', 'thriller-short', 'horror-short'],
            ],
        ],

        // === MUSIC VIDEO ===
        'music' => [
            '_default' => [
                'default' => 'cinematic-short',
                'recommended' => ['cinematic-short', 'inspirational'],
                'compatible' => ['tiktok-viral'],
                'hidden' => ['youtube-standard', 'documentary-feature', 'commercial-spot', 'educational-explainer', 'series-episode'],
            ],
            'narrative' => [
                'default' => 'cinematic-short',
                'recommended' => ['cinematic-short', 'inspirational'],
                'compatible' => ['thriller-short'],
                'hidden' => ['youtube-standard', 'tiktok-viral', 'documentary-feature', 'commercial-spot', 'educational-explainer', 'series-episode'],
            ],
            'performance' => [
                'default' => 'cinematic-short',
                'recommended' => ['cinematic-short'],
                'compatible' => ['tiktok-viral', 'inspirational'],
                'hidden' => ['youtube-standard', 'documentary-feature', 'commercial-spot', 'educational-explainer', 'series-episode', 'thriller-short', 'horror-short'],
            ],
            'lyric' => [
                'default' => 'inspirational',
                'recommended' => ['inspirational', 'cinematic-short'],
                'compatible' => ['tiktok-viral'],
                'hidden' => ['youtube-standard', 'documentary-feature', 'commercial-spot', 'educational-explainer', 'series-episode', 'thriller-short', 'horror-short'],
            ],
        ],

        // === COMMERCIAL/PROMO ===
        'commercial' => [
            '_default' => [
                'default' => 'commercial-spot',
                'recommended' => ['commercial-spot'],
                'compatible' => ['inspirational', 'tiktok-viral'],
                'hidden' => ['documentary-feature', 'series-episode', 'thriller-short', 'horror-short', 'cinematic-short'],
            ],
            'product-ad' => [
                'default' => 'commercial-spot',
                'recommended' => ['commercial-spot'],
                'compatible' => ['tiktok-viral'],
                'hidden' => ['youtube-standard', 'documentary-feature', 'series-episode', 'thriller-short', 'horror-short', 'cinematic-short', 'inspirational'],
            ],
            'brand' => [
                'default' => 'commercial-spot',
                'recommended' => ['commercial-spot', 'inspirational'],
                'compatible' => ['cinematic-short'],
                'hidden' => ['youtube-standard', 'tiktok-viral', 'documentary-feature', 'series-episode', 'thriller-short', 'horror-short', 'educational-explainer'],
            ],
            'testimonial' => [
                'default' => 'commercial-spot',
                'recommended' => ['commercial-spot', 'documentary-feature'],
                'compatible' => ['inspirational'],
                'hidden' => ['youtube-standard', 'tiktok-viral', 'series-episode', 'thriller-short', 'horror-short', 'cinematic-short', 'educational-explainer'],
            ],
        ],
    ],

    // Tension Curves - Pacing dynamics throughout the video
    'tension_curves' => [
        'steady-build' => [
            'id' => 'steady-build',
            'name' => 'Steady Build',
            'icon' => '📈',
            'description' => 'Linear escalation from low to climax',
            'curve' => [10, 20, 30, 40, 50, 60, 70, 80, 90, 95],
            'bestFor' => ['inspirational', 'educational', 'commercial'],
        ],
        'waves' => [
            'id' => 'waves',
            'name' => 'Waves',
            'icon' => '🌊',
            'description' => 'Multiple peaks and valleys for engagement',
            'curve' => [30, 60, 40, 70, 50, 80, 60, 90, 70, 95],
            'bestFor' => ['youtube', 'entertainment', 'tutorial'],
        ],
        'slow-burn' => [
            'id' => 'slow-burn',
            'name' => 'Slow Burn',
            'icon' => '🔥',
            'description' => 'Gradual build with explosive payoff',
            'curve' => [10, 15, 20, 25, 30, 40, 55, 75, 90, 100],
            'bestFor' => ['cinematic', 'drama', 'mystery'],
        ],
        'flat-with-spikes' => [
            'id' => 'flat-with-spikes',
            'name' => 'Flat with Spikes',
            'icon' => '⚡',
            'description' => 'Calm baseline with sudden intense moments',
            'curve' => [30, 30, 80, 30, 30, 90, 30, 30, 95, 40],
            'bestFor' => ['horror', 'thriller', 'comedy'],
        ],
        'escalating-steps' => [
            'id' => 'escalating-steps',
            'name' => 'Escalating Steps',
            'icon' => '🪜',
            'description' => 'Plateau then jump pattern',
            'curve' => [20, 20, 40, 40, 60, 60, 80, 80, 95, 95],
            'bestFor' => ['documentary', 'educational', 'explainer'],
        ],
        'rollercoaster' => [
            'id' => 'rollercoaster',
            'name' => 'Rollercoaster',
            'icon' => '🎢',
            'description' => 'Rapid emotional changes',
            'curve' => [50, 80, 30, 90, 20, 85, 40, 95, 50, 100],
            'bestFor' => ['action', 'thriller', 'comedy'],
        ],
        'double-peak' => [
            'id' => 'double-peak',
            'name' => 'Double Peak',
            'icon' => '⛰️',
            'description' => 'Two climaxes with brief resolution',
            'curve' => [20, 40, 60, 85, 50, 60, 75, 95, 70, 80],
            'bestFor' => ['series', 'drama', 'epic'],
        ],
        'inverted-u' => [
            'id' => 'inverted-u',
            'name' => 'Inverted U',
            'icon' => '🏔️',
            'description' => 'Peak in middle, gentle resolution',
            'curve' => [20, 40, 60, 80, 95, 90, 75, 55, 40, 30],
            'bestFor' => ['tragedy', 'drama', 'reflection'],
        ],
    ],

    // Emotional Journeys - The feeling arc for viewers
    'emotional_journeys' => [
        'triumph' => [
            'id' => 'triumph',
            'name' => 'Triumph',
            'icon' => '🏆',
            'description' => 'Struggle → Growth → Victory',
            'emotionArc' => ['doubt', 'hope', 'setback', 'determination', 'breakthrough', 'celebration'],
            'endFeeling' => 'empowered',
        ],
        'redemption' => [
            'id' => 'redemption',
            'name' => 'Redemption',
            'icon' => '💫',
            'description' => 'Fall → Realization → Rise',
            'emotionArc' => ['pride', 'fall', 'shame', 'reflection', 'change', 'redemption'],
            'endFeeling' => 'hopeful',
        ],
        'cinderella' => [
            'id' => 'cinderella',
            'name' => 'Cinderella',
            'icon' => '✨',
            'description' => 'Low → Opportunity → Transformation',
            'emotionArc' => ['longing', 'opportunity', 'hope', 'threat', 'triumph', 'joy'],
            'endFeeling' => 'delighted',
        ],
        'tragedy' => [
            'id' => 'tragedy',
            'name' => 'Tragedy',
            'icon' => '💔',
            'description' => 'Height → Fall → Loss',
            'emotionArc' => ['happiness', 'pride', 'warning', 'denial', 'fall', 'grief'],
            'endFeeling' => 'reflective',
        ],
        'thriller' => [
            'id' => 'thriller',
            'name' => 'Thriller',
            'icon' => '😰',
            'description' => 'Curiosity → Tension → Relief/Shock',
            'emotionArc' => ['intrigue', 'unease', 'suspicion', 'fear', 'revelation', 'shock'],
            'endFeeling' => 'breathless',
        ],
        'mystery' => [
            'id' => 'mystery',
            'name' => 'Mystery',
            'icon' => '🔍',
            'description' => 'Confusion → Investigation → Revelation',
            'emotionArc' => ['curiosity', 'confusion', 'discovery', 'realization', 'revelation', 'satisfaction'],
            'endFeeling' => 'satisfied',
        ],
        'comedy' => [
            'id' => 'comedy',
            'name' => 'Comedy',
            'icon' => '😄',
            'description' => 'Awkward → Escalation → Relief',
            'emotionArc' => ['normalcy', 'awkwardness', 'escalation', 'chaos', 'resolution', 'laughter'],
            'endFeeling' => 'joyful',
        ],
        'horror' => [
            'id' => 'horror',
            'name' => 'Horror',
            'icon' => '👻',
            'description' => 'Safety → Dread → Terror',
            'emotionArc' => ['normalcy', 'unease', 'dread', 'terror', 'survival', 'lingering_fear'],
            'endFeeling' => 'unsettled',
        ],
        'educational' => [
            'id' => 'educational',
            'name' => 'Educational',
            'icon' => '📚',
            'description' => 'Confusion → Clarity → Mastery',
            'emotionArc' => ['curiosity', 'confusion', 'understanding', 'application', 'mastery', 'confidence'],
            'endFeeling' => 'informed',
        ],
        'meditative' => [
            'id' => 'meditative',
            'name' => 'Meditative',
            'icon' => '🧘',
            'description' => 'Busy → Calm → Peace',
            'emotionArc' => ['restlessness', 'slowing', 'awareness', 'calm', 'peace', 'centeredness'],
            'endFeeling' => 'peaceful',
        ],
        'awe' => [
            'id' => 'awe',
            'name' => 'Awe & Wonder',
            'icon' => '🌌',
            'description' => 'Normal → Discovery → Amazement',
            'emotionArc' => ['ordinary', 'curiosity', 'discovery', 'wonder', 'amazement', 'inspiration'],
            'endFeeling' => 'inspired',
        ],
        'nostalgia' => [
            'id' => 'nostalgia',
            'name' => 'Nostalgia',
            'icon' => '🎞️',
            'description' => 'Present → Memory → Bittersweet appreciation',
            'emotionArc' => ['present', 'trigger', 'memory', 'longing', 'acceptance', 'appreciation'],
            'endFeeling' => 'wistful',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cinematic Shot Language
    |--------------------------------------------------------------------------
    | Professional cinematography vocabulary for AI image generation
    */

    // Shot Types - Camera framing
    'shot_types' => [
        'extreme-wide' => [
            'id' => 'extreme-wide',
            'name' => 'Extreme Wide Shot',
            'abbrev' => 'EWS',
            'description' => 'Establishes location, subject appears small in vast environment',
            'bestFor' => ['establishing', 'landscape', 'epic'],
            'promptHint' => 'extreme wide shot, vast landscape, tiny subject in frame',
        ],
        'wide' => [
            'id' => 'wide',
            'name' => 'Wide Shot',
            'abbrev' => 'WS',
            'description' => 'Full body visible with environment context',
            'bestFor' => ['action', 'group', 'movement'],
            'promptHint' => 'wide shot, full body visible, environmental context',
        ],
        'medium-wide' => [
            'id' => 'medium-wide',
            'name' => 'Medium Wide Shot',
            'abbrev' => 'MWS',
            'description' => 'Knees up, balances subject and environment',
            'bestFor' => ['conversation', 'walking', 'product'],
            'promptHint' => 'medium wide shot, knees up framing',
        ],
        'medium' => [
            'id' => 'medium',
            'name' => 'Medium Shot',
            'abbrev' => 'MS',
            'description' => 'Waist up, standard conversational framing',
            'bestFor' => ['dialogue', 'interview', 'presentation'],
            'promptHint' => 'medium shot, waist up, conversational framing',
        ],
        'medium-close' => [
            'id' => 'medium-close',
            'name' => 'Medium Close-Up',
            'abbrev' => 'MCU',
            'description' => 'Chest up, intimate but not intrusive',
            'bestFor' => ['emotion', 'reaction', 'testimonial'],
            'promptHint' => 'medium close-up, chest up, intimate framing',
        ],
        'close-up' => [
            'id' => 'close-up',
            'name' => 'Close-Up',
            'abbrev' => 'CU',
            'description' => 'Face fills frame, captures emotion',
            'bestFor' => ['emotion', 'detail', 'impact'],
            'promptHint' => 'close-up shot, face filling frame, emotional detail',
        ],
        'extreme-close' => [
            'id' => 'extreme-close',
            'name' => 'Extreme Close-Up',
            'abbrev' => 'ECU',
            'description' => 'Single feature (eyes, hands), intense detail',
            'bestFor' => ['tension', 'detail', 'reveal'],
            'promptHint' => 'extreme close-up, single feature detail, macro view',
        ],
        'over-shoulder' => [
            'id' => 'over-shoulder',
            'name' => 'Over-the-Shoulder',
            'abbrev' => 'OTS',
            'description' => 'Subject framed over another person\'s shoulder',
            'bestFor' => ['conversation', 'perspective', 'connection'],
            'promptHint' => 'over-the-shoulder shot, conversational perspective',
        ],
        'pov' => [
            'id' => 'pov',
            'name' => 'Point of View',
            'abbrev' => 'POV',
            'description' => 'Camera becomes the character\'s eyes',
            'bestFor' => ['immersion', 'horror', 'action'],
            'promptHint' => 'point of view shot, first-person perspective',
        ],
        'aerial' => [
            'id' => 'aerial',
            'name' => 'Aerial/Drone Shot',
            'abbrev' => 'AERIAL',
            'description' => 'High angle bird\'s eye view',
            'bestFor' => ['establishing', 'scale', 'travel'],
            'promptHint' => 'aerial drone shot, bird\'s eye view, from above',
        ],
    ],

    // Camera Movements
    'camera_movements' => [
        'static' => [
            'id' => 'static',
            'name' => 'Static',
            'description' => 'Camera remains fixed',
            'kenBurns' => ['startScale' => 1.0, 'endScale' => 1.0],
            'promptHint' => 'static camera, locked off',
        ],
        'push-in' => [
            'id' => 'push-in',
            'name' => 'Push In / Dolly In',
            'description' => 'Camera moves toward subject, builds intensity',
            'kenBurns' => ['startScale' => 1.0, 'endScale' => 1.2, 'endY' => 0.45],
            'promptHint' => 'dolly in, camera pushing forward',
        ],
        'pull-out' => [
            'id' => 'pull-out',
            'name' => 'Pull Out / Dolly Out',
            'description' => 'Camera moves away, reveals context',
            'kenBurns' => ['startScale' => 1.2, 'endScale' => 1.0],
            'promptHint' => 'dolly out, camera pulling back, revealing',
        ],
        'pan-left' => [
            'id' => 'pan-left',
            'name' => 'Pan Left',
            'description' => 'Camera rotates left to follow action',
            'kenBurns' => ['startX' => 0.6, 'endX' => 0.4],
            'promptHint' => 'panning left, horizontal movement',
        ],
        'pan-right' => [
            'id' => 'pan-right',
            'name' => 'Pan Right',
            'description' => 'Camera rotates right to follow action',
            'kenBurns' => ['startX' => 0.4, 'endX' => 0.6],
            'promptHint' => 'panning right, horizontal movement',
        ],
        'tilt-up' => [
            'id' => 'tilt-up',
            'name' => 'Tilt Up',
            'description' => 'Camera tilts upward, reveals height',
            'kenBurns' => ['startY' => 0.6, 'endY' => 0.4],
            'promptHint' => 'tilting up, vertical reveal',
        ],
        'tilt-down' => [
            'id' => 'tilt-down',
            'name' => 'Tilt Down',
            'description' => 'Camera tilts downward',
            'kenBurns' => ['startY' => 0.4, 'endY' => 0.6],
            'promptHint' => 'tilting down, descending view',
        ],
        'tracking' => [
            'id' => 'tracking',
            'name' => 'Tracking Shot',
            'description' => 'Camera follows alongside subject',
            'kenBurns' => ['startX' => 0.3, 'endX' => 0.7, 'startScale' => 1.05, 'endScale' => 1.05],
            'promptHint' => 'tracking shot, following movement',
        ],
        'zoom-in' => [
            'id' => 'zoom-in',
            'name' => 'Zoom In',
            'description' => 'Lens zooms toward subject',
            'kenBurns' => ['startScale' => 1.0, 'endScale' => 1.3],
            'promptHint' => 'zoom in, focal length change',
        ],
        'zoom-out' => [
            'id' => 'zoom-out',
            'name' => 'Zoom Out',
            'description' => 'Lens zooms away from subject',
            'kenBurns' => ['startScale' => 1.3, 'endScale' => 1.0],
            'promptHint' => 'zoom out, revealing wider view',
        ],
    ],

    // Lighting Styles
    'lighting_styles' => [
        'natural' => [
            'id' => 'natural',
            'name' => 'Natural Light',
            'description' => 'Soft, realistic daylight',
            'mood' => 'authentic',
            'promptHint' => 'natural lighting, soft daylight, realistic',
        ],
        'golden-hour' => [
            'id' => 'golden-hour',
            'name' => 'Golden Hour',
            'description' => 'Warm, magical sunrise/sunset light',
            'mood' => 'romantic',
            'promptHint' => 'golden hour lighting, warm sunset glow, magical light',
        ],
        'blue-hour' => [
            'id' => 'blue-hour',
            'name' => 'Blue Hour',
            'description' => 'Cool twilight tones',
            'mood' => 'melancholic',
            'promptHint' => 'blue hour lighting, twilight, cool tones',
        ],
        'high-key' => [
            'id' => 'high-key',
            'name' => 'High Key',
            'description' => 'Bright, minimal shadows, optimistic',
            'mood' => 'uplifting',
            'promptHint' => 'high key lighting, bright, minimal shadows',
        ],
        'low-key' => [
            'id' => 'low-key',
            'name' => 'Low Key',
            'description' => 'Dark, dramatic shadows, mysterious',
            'mood' => 'dramatic',
            'promptHint' => 'low key lighting, dramatic shadows, chiaroscuro',
        ],
        'rembrandt' => [
            'id' => 'rembrandt',
            'name' => 'Rembrandt',
            'description' => 'Classic portrait lighting with triangle under eye',
            'mood' => 'artistic',
            'promptHint' => 'Rembrandt lighting, classic portrait, triangle shadow under eye',
        ],
        'silhouette' => [
            'id' => 'silhouette',
            'name' => 'Silhouette',
            'description' => 'Backlit subject appears as dark outline',
            'mood' => 'mysterious',
            'promptHint' => 'silhouette, backlit, dark outline against bright background',
        ],
        'neon' => [
            'id' => 'neon',
            'name' => 'Neon/Cyberpunk',
            'description' => 'Colorful artificial lighting',
            'mood' => 'futuristic',
            'promptHint' => 'neon lighting, cyberpunk colors, vibrant artificial light',
        ],
        'studio' => [
            'id' => 'studio',
            'name' => 'Studio Lighting',
            'description' => 'Professional controlled lighting',
            'mood' => 'polished',
            'promptHint' => 'professional studio lighting, controlled, polished',
        ],
        'candlelight' => [
            'id' => 'candlelight',
            'name' => 'Candlelight/Firelight',
            'description' => 'Warm flickering light source',
            'mood' => 'intimate',
            'promptHint' => 'candlelight, warm flickering glow, intimate atmosphere',
        ],
    ],

    // Color Grading Styles
    'color_grades' => [
        'neutral' => [
            'id' => 'neutral',
            'name' => 'Neutral/Natural',
            'description' => 'True-to-life colors',
            'promptHint' => 'natural colors, balanced, true to life',
        ],
        'warm' => [
            'id' => 'warm',
            'name' => 'Warm Tones',
            'description' => 'Orange/yellow color cast, cozy feel',
            'promptHint' => 'warm color grading, orange and yellow tones, cozy',
        ],
        'cool' => [
            'id' => 'cool',
            'name' => 'Cool Tones',
            'description' => 'Blue/teal color cast, professional feel',
            'promptHint' => 'cool color grading, blue and teal tones, professional',
        ],
        'teal-orange' => [
            'id' => 'teal-orange',
            'name' => 'Teal & Orange',
            'description' => 'Hollywood blockbuster look',
            'promptHint' => 'teal and orange color grading, cinematic Hollywood look',
        ],
        'desaturated' => [
            'id' => 'desaturated',
            'name' => 'Desaturated',
            'description' => 'Muted colors, serious tone',
            'promptHint' => 'desaturated colors, muted, serious tone',
        ],
        'vintage' => [
            'id' => 'vintage',
            'name' => 'Vintage/Retro',
            'description' => 'Faded colors, nostalgic feel',
            'promptHint' => 'vintage color grading, faded, nostalgic, retro film look',
        ],
        'high-contrast' => [
            'id' => 'high-contrast',
            'name' => 'High Contrast',
            'description' => 'Bold blacks and whites',
            'promptHint' => 'high contrast, bold blacks, bright whites, dramatic',
        ],
        'pastel' => [
            'id' => 'pastel',
            'name' => 'Pastel',
            'description' => 'Soft, dreamy colors',
            'promptHint' => 'pastel colors, soft, dreamy, ethereal',
        ],
        'monochrome' => [
            'id' => 'monochrome',
            'name' => 'Black & White',
            'description' => 'Classic monochrome',
            'promptHint' => 'black and white, monochrome, classic film',
        ],
        'vibrant' => [
            'id' => 'vibrant',
            'name' => 'Vibrant/Saturated',
            'description' => 'Bold, punchy colors',
            'promptHint' => 'vibrant colors, highly saturated, bold and punchy',
        ],
    ],

    // Composition Rules
    'compositions' => [
        'rule-of-thirds' => [
            'id' => 'rule-of-thirds',
            'name' => 'Rule of Thirds',
            'description' => 'Subject on intersection points',
            'promptHint' => 'rule of thirds composition, subject off-center',
        ],
        'centered' => [
            'id' => 'centered',
            'name' => 'Centered/Symmetrical',
            'description' => 'Subject in center, balanced',
            'promptHint' => 'centered composition, symmetrical, balanced framing',
        ],
        'leading-lines' => [
            'id' => 'leading-lines',
            'name' => 'Leading Lines',
            'description' => 'Lines draw eye to subject',
            'promptHint' => 'leading lines composition, lines guiding to subject',
        ],
        'frame-within-frame' => [
            'id' => 'frame-within-frame',
            'name' => 'Frame Within Frame',
            'description' => 'Subject framed by environmental elements',
            'promptHint' => 'frame within frame, natural framing elements',
        ],
        'negative-space' => [
            'id' => 'negative-space',
            'name' => 'Negative Space',
            'description' => 'Empty space emphasizes subject',
            'promptHint' => 'negative space composition, minimalist, isolated subject',
        ],
        'diagonal' => [
            'id' => 'diagonal',
            'name' => 'Diagonal',
            'description' => 'Dynamic diagonal lines create energy',
            'promptHint' => 'diagonal composition, dynamic angles, energetic',
        ],
        'golden-ratio' => [
            'id' => 'golden-ratio',
            'name' => 'Golden Ratio',
            'description' => 'Mathematical spiral composition',
            'promptHint' => 'golden ratio composition, fibonacci spiral',
        ],
        'depth-layering' => [
            'id' => 'depth-layering',
            'name' => 'Depth Layering',
            'description' => 'Foreground, midground, background layers',
            'promptHint' => 'depth layering, foreground midground background, dimensional',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scene Beat System
    |--------------------------------------------------------------------------
    | Micro-structure within scenes for better pacing
    */

    'scene_beats' => [
        'setup' => [
            'id' => 'setup',
            'name' => 'Setup Beat',
            'description' => 'Establish context, introduce elements',
            'percentage' => 25,
            'purpose' => 'Orient the viewer, set expectations',
        ],
        'development' => [
            'id' => 'development',
            'name' => 'Development Beat',
            'description' => 'Build tension, develop content',
            'percentage' => 50,
            'purpose' => 'Deliver main content, build engagement',
        ],
        'payoff' => [
            'id' => 'payoff',
            'name' => 'Payoff Beat',
            'description' => 'Deliver value, create impact',
            'percentage' => 25,
            'purpose' => 'Reward attention, memorable moment',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention Hooks
    |--------------------------------------------------------------------------
    | Engagement elements to maintain viewer attention
    */

    'retention_hooks' => [
        'question' => [
            'id' => 'question',
            'name' => 'Rhetorical Question',
            'templates' => [
                'But here\'s what most people don\'t realize...',
                'Have you ever wondered why...?',
                'What if I told you that...?',
                'Want to know the secret?',
                'Can you guess what happened next?',
            ],
            'insertAfter' => 30, // seconds
        ],
        'tease' => [
            'id' => 'tease',
            'name' => 'Content Tease',
            'templates' => [
                'But wait, it gets even better...',
                'Stay with me because...',
                'The best part is coming up...',
                'You won\'t believe what\'s next...',
                'Here\'s where it gets interesting...',
            ],
            'insertAfter' => 45,
        ],
        'pattern-break' => [
            'id' => 'pattern-break',
            'name' => 'Pattern Break',
            'templates' => [
                'Now, here\'s the twist...',
                'Let me stop right there...',
                'But plot twist...',
                'Here\'s the thing though...',
                'Okay, real talk...',
            ],
            'insertAfter' => 60,
        ],
        'social-proof' => [
            'id' => 'social-proof',
            'name' => 'Social Proof',
            'templates' => [
                'Thousands of people have already...',
                'The experts agree that...',
                'Studies have shown...',
                'Top performers know that...',
                'The data is clear...',
            ],
            'insertAfter' => 90,
        ],
        'urgency' => [
            'id' => 'urgency',
            'name' => 'Urgency Hook',
            'templates' => [
                'This is something you need to know right now...',
                'Don\'t miss this crucial point...',
                'Pay close attention to this...',
                'This changes everything...',
                'Here\'s the game-changer...',
            ],
            'insertAfter' => 120,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Professional Transitions
    |--------------------------------------------------------------------------
    | Context-aware scene transitions
    */

    'transitions' => [
        'cut' => [
            'id' => 'cut',
            'name' => 'Cut',
            'description' => 'Instant transition, standard',
            'bestFor' => ['action', 'dialogue', 'fast-paced'],
            'duration' => 0,
        ],
        'fade' => [
            'id' => 'fade',
            'name' => 'Fade',
            'description' => 'Gradual fade through black',
            'bestFor' => ['emotional', 'ending', 'time-passage'],
            'duration' => 500,
        ],
        'dissolve' => [
            'id' => 'dissolve',
            'name' => 'Dissolve',
            'description' => 'One image blends into next',
            'bestFor' => ['dreamy', 'memory', 'gentle'],
            'duration' => 800,
        ],
        'wipe' => [
            'id' => 'wipe',
            'name' => 'Wipe',
            'description' => 'One scene pushes out another',
            'bestFor' => ['energetic', 'reveal', 'change'],
            'duration' => 400,
        ],
        'zoom' => [
            'id' => 'zoom',
            'name' => 'Zoom Transition',
            'description' => 'Zoom into/out of next scene',
            'bestFor' => ['social-media', 'dynamic', 'travel'],
            'duration' => 300,
        ],
        'slide' => [
            'id' => 'slide',
            'name' => 'Slide',
            'description' => 'Scenes slide horizontally',
            'bestFor' => ['comparison', 'before-after', 'list'],
            'duration' => 400,
        ],
        'morph' => [
            'id' => 'morph',
            'name' => 'Morph',
            'description' => 'Shape morphs between scenes',
            'bestFor' => ['transformation', 'progress', 'journey'],
            'duration' => 600,
        ],
        'flash' => [
            'id' => 'flash',
            'name' => 'Flash/Glitch',
            'description' => 'Quick flash or glitch effect',
            'bestFor' => ['impact', 'surprise', 'modern'],
            'duration' => 200,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Visual Styles (Tier 3)
    |--------------------------------------------------------------------------
    | Consistent visual style presets for image generation
    */

    'visual_styles' => [
        'photorealistic' => [
            'id' => 'photorealistic',
            'name' => 'Photorealistic',
            'icon' => '📷',
            'description' => 'Hyper-realistic photography style',
            'promptPrefix' => 'photorealistic, highly detailed, 8k resolution, professional photography',
            'promptSuffix' => 'sharp focus, natural lighting, realistic textures',
            'negativePrompt' => 'cartoon, illustration, painting, drawing, anime, artificial',
            'bestFor' => ['documentary', 'commercial', 'testimonial'],
        ],
        'cinematic' => [
            'id' => 'cinematic',
            'name' => 'Cinematic',
            'icon' => '🎬',
            'description' => 'Hollywood film aesthetic with dramatic lighting',
            'promptPrefix' => 'cinematic shot, movie still, dramatic lighting, film grain',
            'promptSuffix' => 'anamorphic lens, depth of field, professional color grading',
            'negativePrompt' => 'amateur, flat lighting, oversaturated, snapshot',
            'bestFor' => ['drama', 'thriller', 'action', 'scifi'],
        ],
        'anime' => [
            'id' => 'anime',
            'name' => 'Anime/Manga',
            'icon' => '🎌',
            'description' => 'Japanese animation style',
            'promptPrefix' => 'anime style, manga aesthetic, cel shaded, vibrant colors',
            'promptSuffix' => 'clean lines, expressive characters, detailed backgrounds',
            'negativePrompt' => 'photorealistic, 3d render, western cartoon',
            'bestFor' => ['animation', 'fantasy', 'comedy'],
        ],
        'illustration' => [
            'id' => 'illustration',
            'name' => 'Digital Illustration',
            'icon' => '🎨',
            'description' => 'Modern digital art style',
            'promptPrefix' => 'digital illustration, artstation trending, concept art',
            'promptSuffix' => 'highly detailed, vibrant colors, professional artwork',
            'negativePrompt' => 'photo, 3d, amateur, low quality',
            'bestFor' => ['educational', 'explainer', 'fantasy'],
        ],
        '3d-render' => [
            'id' => '3d-render',
            'name' => '3D Render',
            'icon' => '🔮',
            'description' => 'High-quality 3D rendered visuals',
            'promptPrefix' => '3d render, octane render, unreal engine 5, ray tracing',
            'promptSuffix' => 'highly detailed, volumetric lighting, subsurface scattering',
            'negativePrompt' => '2d, flat, hand-drawn, low poly',
            'bestFor' => ['scifi', 'product', 'tech'],
        ],
        'vintage' => [
            'id' => 'vintage',
            'name' => 'Vintage/Retro',
            'icon' => '📼',
            'description' => 'Nostalgic film look',
            'promptPrefix' => 'vintage photograph, retro aesthetic, film photography',
            'promptSuffix' => 'grain, faded colors, nostalgic mood, old film stock',
            'negativePrompt' => 'modern, digital, clean, sharp',
            'bestFor' => ['nostalgia', 'documentary', 'drama'],
        ],
        'minimalist' => [
            'id' => 'minimalist',
            'name' => 'Minimalist',
            'icon' => '⬜',
            'description' => 'Clean, simple, modern aesthetic',
            'promptPrefix' => 'minimalist design, clean composition, simple',
            'promptSuffix' => 'negative space, modern aesthetic, elegant simplicity',
            'negativePrompt' => 'cluttered, busy, detailed, complex',
            'bestFor' => ['commercial', 'brand', 'tech'],
        ],
        'watercolor' => [
            'id' => 'watercolor',
            'name' => 'Watercolor',
            'icon' => '🖌️',
            'description' => 'Soft watercolor painting style',
            'promptPrefix' => 'watercolor painting, soft edges, flowing colors',
            'promptSuffix' => 'artistic, painterly, organic textures',
            'negativePrompt' => 'sharp, digital, photorealistic, harsh',
            'bestFor' => ['artistic', 'emotional', 'children'],
        ],
        'noir' => [
            'id' => 'noir',
            'name' => 'Film Noir',
            'icon' => '🕵️',
            'description' => 'Classic black and white detective aesthetic',
            'promptPrefix' => 'film noir, black and white, high contrast, shadows',
            'promptSuffix' => 'dramatic lighting, venetian blinds shadows, moody atmosphere',
            'negativePrompt' => 'colorful, bright, cheerful, low contrast',
            'bestFor' => ['thriller', 'mystery', 'drama'],
        ],
        'neon-cyberpunk' => [
            'id' => 'neon-cyberpunk',
            'name' => 'Neon Cyberpunk',
            'icon' => '🌃',
            'description' => 'Futuristic neon-lit urban aesthetic',
            'promptPrefix' => 'cyberpunk, neon lights, futuristic city, blade runner',
            'promptSuffix' => 'rain reflections, holographic displays, dystopian atmosphere',
            'negativePrompt' => 'natural, rural, bright daylight, historical',
            'bestFor' => ['scifi', 'tech', 'music'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Music Moods (Tier 3)
    |--------------------------------------------------------------------------
    | Soundtrack mood suggestions for scenes
    */

    'music_moods' => [
        'epic' => [
            'id' => 'epic',
            'name' => 'Epic/Cinematic',
            'icon' => '🎻',
            'description' => 'Grand orchestral, building crescendos',
            'instruments' => ['orchestra', 'choir', 'drums', 'brass'],
            'tempo' => 'moderate-to-fast',
            'energy' => 'high',
            'keywords' => ['triumphant', 'powerful', 'inspiring', 'majestic'],
            'bestFor' => ['climax', 'action', 'inspirational'],
        ],
        'emotional' => [
            'id' => 'emotional',
            'name' => 'Emotional/Touching',
            'icon' => '💗',
            'description' => 'Piano-led, strings, gentle and moving',
            'instruments' => ['piano', 'strings', 'acoustic guitar'],
            'tempo' => 'slow',
            'energy' => 'low-to-medium',
            'keywords' => ['heartfelt', 'tender', 'moving', 'intimate'],
            'bestFor' => ['drama', 'testimonial', 'ending'],
        ],
        'tense' => [
            'id' => 'tense',
            'name' => 'Tense/Suspenseful',
            'icon' => '😰',
            'description' => 'Building dread, dissonant strings, heartbeat rhythms',
            'instruments' => ['strings', 'synth pads', 'percussion'],
            'tempo' => 'variable',
            'energy' => 'medium-building',
            'keywords' => ['suspenseful', 'anxious', 'ominous', 'anticipation'],
            'bestFor' => ['thriller', 'horror', 'mystery'],
        ],
        'upbeat' => [
            'id' => 'upbeat',
            'name' => 'Upbeat/Energetic',
            'icon' => '🎉',
            'description' => 'Fast tempo, positive energy, driving beats',
            'instruments' => ['synth', 'drums', 'bass', 'electronic'],
            'tempo' => 'fast',
            'energy' => 'high',
            'keywords' => ['exciting', 'fun', 'energetic', 'motivating'],
            'bestFor' => ['viral', 'product', 'tutorial'],
        ],
        'ambient' => [
            'id' => 'ambient',
            'name' => 'Ambient/Atmospheric',
            'icon' => '🌌',
            'description' => 'Subtle textures, ethereal pads, spacious',
            'instruments' => ['synth pads', 'ambient textures', 'reverb'],
            'tempo' => 'slow',
            'energy' => 'low',
            'keywords' => ['peaceful', 'meditative', 'dreamy', 'spacious'],
            'bestFor' => ['meditative', 'establishing', 'documentary'],
        ],
        'corporate' => [
            'id' => 'corporate',
            'name' => 'Corporate/Professional',
            'icon' => '💼',
            'description' => 'Clean, positive, professional background music',
            'instruments' => ['acoustic guitar', 'light percussion', 'piano'],
            'tempo' => 'moderate',
            'energy' => 'medium',
            'keywords' => ['professional', 'optimistic', 'confident', 'clean'],
            'bestFor' => ['commercial', 'explainer', 'brand'],
        ],
        'dramatic' => [
            'id' => 'dramatic',
            'name' => 'Dramatic',
            'icon' => '🎭',
            'description' => 'Bold orchestral hits, emotional swells',
            'instruments' => ['orchestra', 'percussion', 'choir'],
            'tempo' => 'variable',
            'energy' => 'high',
            'keywords' => ['intense', 'powerful', 'emotional', 'bold'],
            'bestFor' => ['climax', 'reveal', 'transformation'],
        ],
        'playful' => [
            'id' => 'playful',
            'name' => 'Playful/Quirky',
            'icon' => '🎪',
            'description' => 'Light, bouncy, whimsical elements',
            'instruments' => ['pizzicato', 'xylophone', 'ukulele', 'whistle'],
            'tempo' => 'moderate-to-fast',
            'energy' => 'medium',
            'keywords' => ['fun', 'lighthearted', 'whimsical', 'cheerful'],
            'bestFor' => ['comedy', 'children', 'lifestyle'],
        ],
        'horror' => [
            'id' => 'horror',
            'name' => 'Horror/Dark',
            'icon' => '👻',
            'description' => 'Dissonant, unsettling, creeping dread',
            'instruments' => ['dissonant strings', 'deep bass', 'sound design'],
            'tempo' => 'slow-to-variable',
            'energy' => 'low-with-spikes',
            'keywords' => ['scary', 'unsettling', 'creepy', 'dread'],
            'bestFor' => ['horror', 'thriller', 'mystery'],
        ],
        'electronic' => [
            'id' => 'electronic',
            'name' => 'Electronic/Tech',
            'icon' => '🤖',
            'description' => 'Modern synths, digital beats, futuristic',
            'instruments' => ['synthesizers', 'electronic drums', 'bass'],
            'tempo' => 'moderate-to-fast',
            'energy' => 'medium-to-high',
            'keywords' => ['modern', 'tech', 'futuristic', 'innovative'],
            'bestFor' => ['tech', 'scifi', 'product'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pacing Profiles (Tier 3)
    |--------------------------------------------------------------------------
    | Words-per-minute and scene duration optimization
    */

    'pacing_profiles' => [
        'slow' => [
            'id' => 'slow',
            'name' => 'Slow & Deliberate',
            'icon' => '🐢',
            'wpm' => 100,
            'description' => 'Thoughtful pacing for emotional or complex content',
            'sceneDuration' => ['min' => 15, 'avg' => 25, 'max' => 40],
            'pauseAfterSentence' => 0.8,
            'bestFor' => ['documentary', 'drama', 'meditation'],
        ],
        'moderate' => [
            'id' => 'moderate',
            'name' => 'Moderate',
            'icon' => '🚶',
            'wpm' => 130,
            'description' => 'Balanced pacing for general content',
            'sceneDuration' => ['min' => 10, 'avg' => 18, 'max' => 30],
            'pauseAfterSentence' => 0.5,
            'bestFor' => ['educational', 'explainer', 'commercial'],
        ],
        'standard' => [
            'id' => 'standard',
            'name' => 'Standard',
            'icon' => '▶️',
            'wpm' => 145,
            'description' => 'Natural conversational pace',
            'sceneDuration' => ['min' => 8, 'avg' => 15, 'max' => 25],
            'pauseAfterSentence' => 0.4,
            'bestFor' => ['youtube', 'tutorial', 'brand'],
        ],
        'fast' => [
            'id' => 'fast',
            'name' => 'Fast & Dynamic',
            'icon' => '🏃',
            'wpm' => 165,
            'description' => 'Quick pacing for energetic content',
            'sceneDuration' => ['min' => 5, 'avg' => 10, 'max' => 18],
            'pauseAfterSentence' => 0.3,
            'bestFor' => ['tiktok', 'reels', 'viral'],
        ],
        'rapid' => [
            'id' => 'rapid',
            'name' => 'Rapid Fire',
            'icon' => '⚡',
            'wpm' => 180,
            'description' => 'High-energy, attention-grabbing pace',
            'sceneDuration' => ['min' => 3, 'avg' => 6, 'max' => 12],
            'pauseAfterSentence' => 0.2,
            'bestFor' => ['viral', 'comedy', 'action'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Genre Templates (Tier 3)
    |--------------------------------------------------------------------------
    | Pre-configured settings for specific video genres
    */

    'genre_templates' => [
        'youtube-explainer' => [
            'id' => 'youtube-explainer',
            'name' => 'YouTube Explainer',
            'icon' => '📚',
            'description' => 'Educational content optimized for YouTube',
            'defaults' => [
                'narrativePreset' => 'youtube-standard',
                'storyArc' => 'problem-solution',
                'tensionCurve' => 'waves',
                'emotionalJourney' => 'educational',
                'visualStyle' => 'illustration',
                'musicMood' => 'corporate',
                'pacingProfile' => 'standard',
                'colorGrade' => 'vibrant',
                'lighting' => 'high-key',
            ],
            'tips' => 'Hook viewers in first 5 seconds, use pattern breaks every 60s, end with clear CTA',
        ],
        'tiktok-viral' => [
            'id' => 'tiktok-viral',
            'name' => 'TikTok Viral',
            'icon' => '⚡',
            'description' => 'Fast-paced content for TikTok/Reels',
            'defaults' => [
                'narrativePreset' => 'tiktok-viral',
                'storyArc' => 'tiktok-viral',
                'tensionCurve' => 'steady-build',
                'emotionalJourney' => 'triumph',
                'visualStyle' => 'cinematic',
                'musicMood' => 'upbeat',
                'pacingProfile' => 'fast',
                'colorGrade' => 'vibrant',
                'lighting' => 'neon',
            ],
            'tips' => 'First frame must stop scroll, payoff at 80%, loop-worthy ending',
        ],
        'cinematic-drama' => [
            'id' => 'cinematic-drama',
            'name' => 'Cinematic Drama',
            'icon' => '🎬',
            'description' => 'Hollywood-style dramatic storytelling',
            'defaults' => [
                'narrativePreset' => 'cinematic-short',
                'storyArc' => 'three-act',
                'tensionCurve' => 'slow-burn',
                'emotionalJourney' => 'triumph',
                'visualStyle' => 'cinematic',
                'musicMood' => 'dramatic',
                'pacingProfile' => 'slow',
                'colorGrade' => 'teal-orange',
                'lighting' => 'low-key',
            ],
            'tips' => 'Focus on character moments, let emotions breathe, use visual storytelling',
        ],
        'documentary' => [
            'id' => 'documentary',
            'name' => 'Documentary',
            'icon' => '📽️',
            'description' => 'Factual, evidence-based storytelling',
            'defaults' => [
                'narrativePreset' => 'documentary-feature',
                'storyArc' => 'documentary',
                'tensionCurve' => 'escalating-steps',
                'emotionalJourney' => 'educational',
                'visualStyle' => 'photorealistic',
                'musicMood' => 'ambient',
                'pacingProfile' => 'moderate',
                'colorGrade' => 'desaturated',
                'lighting' => 'natural',
            ],
            'tips' => 'Ground in facts, use expert voices, build to revelation',
        ],
        'horror-thriller' => [
            'id' => 'horror-thriller',
            'name' => 'Horror/Thriller',
            'icon' => '👻',
            'description' => 'Suspenseful, tension-building content',
            'defaults' => [
                'narrativePreset' => 'thriller-short',
                'storyArc' => 'freytags-pyramid',
                'tensionCurve' => 'flat-with-spikes',
                'emotionalJourney' => 'horror',
                'visualStyle' => 'cinematic',
                'musicMood' => 'horror',
                'pacingProfile' => 'slow',
                'colorGrade' => 'desaturated',
                'lighting' => 'low-key',
            ],
            'tips' => 'Build dread slowly, use silence, plant clues for twist reveal',
        ],
        'commercial-ad' => [
            'id' => 'commercial-ad',
            'name' => 'Commercial/Ad',
            'icon' => '📢',
            'description' => 'Product or brand promotional content',
            'defaults' => [
                'narrativePreset' => 'commercial-spot',
                'storyArc' => 'problem-solution',
                'tensionCurve' => 'steady-build',
                'emotionalJourney' => 'triumph',
                'visualStyle' => 'photorealistic',
                'musicMood' => 'corporate',
                'pacingProfile' => 'standard',
                'colorGrade' => 'vibrant',
                'lighting' => 'studio',
            ],
            'tips' => 'Lead with emotion, delay brand reveal to 80%, single clear CTA',
        ],
        'product-demo' => [
            'id' => 'product-demo',
            'name' => 'Product Demo',
            'icon' => '📦',
            'description' => 'Product showcase and demonstration',
            'defaults' => [
                'narrativePreset' => 'youtube-standard',
                'storyArc' => 'before-after-bridge',
                'tensionCurve' => 'escalating-steps',
                'emotionalJourney' => 'educational',
                'visualStyle' => 'photorealistic',
                'musicMood' => 'upbeat',
                'pacingProfile' => 'moderate',
                'colorGrade' => 'neutral',
                'lighting' => 'studio',
            ],
            'tips' => 'Show problem first, demonstrate solution, highlight key features',
        ],
        'inspirational' => [
            'id' => 'inspirational',
            'name' => 'Inspirational/Motivational',
            'icon' => '🌟',
            'description' => 'Uplifting, motivational content',
            'defaults' => [
                'narrativePreset' => 'inspirational',
                'storyArc' => 'heros-journey',
                'tensionCurve' => 'steady-build',
                'emotionalJourney' => 'triumph',
                'visualStyle' => 'cinematic',
                'musicMood' => 'epic',
                'pacingProfile' => 'moderate',
                'colorGrade' => 'warm',
                'lighting' => 'golden-hour',
            ],
            'tips' => 'Start with struggle, show transformation, end on high emotional note',
        ],
        'comedy-skit' => [
            'id' => 'comedy-skit',
            'name' => 'Comedy/Skit',
            'icon' => '😄',
            'description' => 'Humorous, entertaining content',
            'defaults' => [
                'narrativePreset' => 'tiktok-viral',
                'storyArc' => 'story-circle',
                'tensionCurve' => 'waves',
                'emotionalJourney' => 'comedy',
                'visualStyle' => 'photorealistic',
                'musicMood' => 'playful',
                'pacingProfile' => 'fast',
                'colorGrade' => 'vibrant',
                'lighting' => 'high-key',
            ],
            'tips' => 'Set up expectations, subvert them, timing is everything',
        ],
        'tutorial' => [
            'id' => 'tutorial',
            'name' => 'Tutorial/How-To',
            'icon' => '🎓',
            'description' => 'Step-by-step instructional content',
            'defaults' => [
                'narrativePreset' => 'educational-explainer',
                'storyArc' => 'inverted-pyramid',
                'tensionCurve' => 'escalating-steps',
                'emotionalJourney' => 'educational',
                'visualStyle' => 'illustration',
                'musicMood' => 'corporate',
                'pacingProfile' => 'moderate',
                'colorGrade' => 'neutral',
                'lighting' => 'studio',
            ],
            'tips' => 'Clear steps, visual demonstrations, summarize key points',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Visual Themes (Tier 3)
    |--------------------------------------------------------------------------
    | Cohesive visual theme configurations for consistency
    */

    'visual_themes' => [
        'warm-sunset' => [
            'id' => 'warm-sunset',
            'name' => 'Warm Sunset',
            'colors' => ['#FF6B35', '#F7C59F', '#2E294E', '#FFE66D'],
            'mood' => 'nostalgic',
            'lighting' => 'golden-hour',
            'colorGrade' => 'warm',
        ],
        'cool-corporate' => [
            'id' => 'cool-corporate',
            'name' => 'Cool Corporate',
            'colors' => ['#1A365D', '#2B6CB0', '#63B3ED', '#FFFFFF'],
            'mood' => 'professional',
            'lighting' => 'studio',
            'colorGrade' => 'cool',
        ],
        'neon-night' => [
            'id' => 'neon-night',
            'name' => 'Neon Night',
            'colors' => ['#FF00FF', '#00FFFF', '#FF1493', '#0D0D0D'],
            'mood' => 'futuristic',
            'lighting' => 'neon',
            'colorGrade' => 'high-contrast',
        ],
        'earth-natural' => [
            'id' => 'earth-natural',
            'name' => 'Earth & Natural',
            'colors' => ['#2D5016', '#8B4513', '#87CEEB', '#F5DEB3'],
            'mood' => 'organic',
            'lighting' => 'natural',
            'colorGrade' => 'neutral',
        ],
        'minimal-mono' => [
            'id' => 'minimal-mono',
            'name' => 'Minimal Monochrome',
            'colors' => ['#000000', '#333333', '#666666', '#FFFFFF'],
            'mood' => 'elegant',
            'lighting' => 'high-key',
            'colorGrade' => 'monochrome',
        ],
        'pastel-dream' => [
            'id' => 'pastel-dream',
            'name' => 'Pastel Dream',
            'colors' => ['#FFB3BA', '#BAFFC9', '#BAE1FF', '#FFFFBA'],
            'mood' => 'soft',
            'lighting' => 'high-key',
            'colorGrade' => 'pastel',
        ],
        'vintage-film' => [
            'id' => 'vintage-film',
            'name' => 'Vintage Film',
            'colors' => ['#D4A574', '#8B7355', '#F5F5DC', '#2F2F2F'],
            'mood' => 'nostalgic',
            'lighting' => 'natural',
            'colorGrade' => 'vintage',
        ],
        'dark-cinematic' => [
            'id' => 'dark-cinematic',
            'name' => 'Dark Cinematic',
            'colors' => ['#1A1A2E', '#16213E', '#0F3460', '#E94560'],
            'mood' => 'dramatic',
            'lighting' => 'low-key',
            'colorGrade' => 'teal-orange',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Credit Costs
    |--------------------------------------------------------------------------
    | Credit costs for various operations
    */
    'credit_costs' => [
        'script_generation' => 5,
        'image_generation' => 3,
        'voiceover_generation' => 2,
        'video_animation' => 10,
        'video_export' => 15,
        'concept_improvement' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Models
    |--------------------------------------------------------------------------
    | AI model configurations
    */
    'ai_models' => [
        'script' => [
            'provider' => 'openai',
            'model' => 'gpt-4',
        ],
        'image' => [
            'provider' => 'openai',
            'model' => 'dall-e-3',
        ],
        'voiceover' => [
            'provider' => 'openai',
            'model' => 'tts-1-hd',
            'voices' => ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'],
        ],
    ],
];
