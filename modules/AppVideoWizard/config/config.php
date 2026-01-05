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
            'description' => 'Cinematic storytelling content',
            'subTypes' => [
                'action' => [
                    'id' => 'action',
                    'name' => 'Action',
                    'icon' => 'fa-solid fa-explosion',
                    'description' => 'High-energy action sequences',
                    'characteristics' => ['dynamic', 'fast-paced', 'exciting'],
                    'defaultNarration' => 'dialogue',
                    'suggestedDuration' => ['min' => 60, 'max' => 300],
                ],
                'drama' => [
                    'id' => 'drama',
                    'name' => 'Drama',
                    'icon' => 'fa-solid fa-masks-theater',
                    'description' => 'Emotional character-driven stories',
                    'characteristics' => ['emotional', 'character-focused', 'narrative'],
                    'defaultNarration' => 'dialogue',
                    'suggestedDuration' => ['min' => 60, 'max' => 300],
                ],
                'thriller' => [
                    'id' => 'thriller',
                    'name' => 'Thriller/Mystery',
                    'icon' => 'fa-solid fa-user-secret',
                    'description' => 'Suspenseful, mystery content',
                    'characteristics' => ['suspenseful', 'mysterious', 'engaging'],
                    'defaultNarration' => 'dialogue',
                    'suggestedDuration' => ['min' => 60, 'max' => 300],
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
        'fade' => ['id' => 'fade', 'name' => 'Fade', 'duration' => 0.5],
        'cut' => ['id' => 'cut', 'name' => 'Cut', 'duration' => 0],
        'slide-left' => ['id' => 'slide-left', 'name' => 'Slide Left', 'duration' => 0.5],
        'slide-right' => ['id' => 'slide-right', 'name' => 'Slide Right', 'duration' => 0.5],
        'zoom-in' => ['id' => 'zoom-in', 'name' => 'Zoom In', 'duration' => 0.5],
        'zoom-out' => ['id' => 'zoom-out', 'name' => 'Zoom Out', 'duration' => 0.5],
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
