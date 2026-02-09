<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platforms
    |--------------------------------------------------------------------------
    */
    'platforms' => [
        'youtube' => [
            'name' => 'YouTube',
            'icon' => 'fa-brands fa-youtube',
            'color' => '#FF0000',
            'max_title' => 100,
            'max_description' => 5000,
            'max_tags' => 500,
            'thumbnail_ratio' => '16:9',
        ],
        'tiktok' => [
            'name' => 'TikTok',
            'icon' => 'fa-brands fa-tiktok',
            'color' => '#00F2EA',
            'max_title' => 150,
            'max_description' => 2200,
            'max_tags' => 0,
            'thumbnail_ratio' => '9:16',
        ],
        'instagram' => [
            'name' => 'Instagram',
            'icon' => 'fa-brands fa-instagram',
            'color' => '#E1306C',
            'max_title' => 0,
            'max_description' => 2200,
            'max_tags' => 0,
            'thumbnail_ratio' => '1:1',
        ],
        'linkedin' => [
            'name' => 'LinkedIn',
            'icon' => 'fa-brands fa-linkedin',
            'color' => '#0077B5',
            'max_title' => 150,
            'max_description' => 3000,
            'max_tags' => 0,
            'thumbnail_ratio' => '16:9',
        ],
        'general' => [
            'name' => 'Multi-Platform',
            'icon' => 'fa-light fa-globe',
            'color' => '#8b5cf6',
            'max_title' => 100,
            'max_description' => 5000,
            'max_tags' => 500,
            'thumbnail_ratio' => '16:9',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool Definitions
    |--------------------------------------------------------------------------
    */
    'tools' => [
        'video_optimizer' => [
            'name' => 'Video Optimizer',
            'description' => 'AI-powered SEO optimization for video titles, descriptions, tags and metadata',
            'icon' => 'fa-light fa-chart-line-up',
            'color' => 'from-blue-500 to-cyan-500',
            'credits' => 1,
            'route' => 'app.ai-tools.video-optimizer',
        ],
        'competitor_analysis' => [
            'name' => 'Competitor Analysis',
            'description' => 'Deep-dive analysis of competitor content strategy with actionable insights',
            'icon' => 'fa-light fa-magnifying-glass-chart',
            'color' => 'from-purple-500 to-pink-500',
            'credits' => 2,
            'route' => 'app.ai-tools.competitor-analysis',
        ],
        'trend_predictor' => [
            'name' => 'Trend Predictor',
            'description' => 'Predict upcoming trends and get content ideas before they go viral',
            'icon' => 'fa-light fa-arrow-trend-up',
            'color' => 'from-green-500 to-emerald-500',
            'credits' => 2,
            'route' => 'app.ai-tools.trend-predictor',
        ],
        'ai_thumbnails' => [
            'name' => 'AI Thumbnails',
            'description' => 'Generate eye-catching thumbnails with AI in any aspect ratio',
            'icon' => 'fa-light fa-image',
            'color' => 'from-orange-500 to-yellow-500',
            'credits' => 3,
            'route' => 'app.ai-tools.ai-thumbnails',
        ],
        'channel_audit' => [
            'name' => 'Channel Audit Pro',
            'description' => 'Comprehensive channel analysis with scores, metrics and growth recommendations',
            'icon' => 'fa-light fa-clipboard-check',
            'color' => 'from-red-500 to-rose-500',
            'credits' => 3,
            'route' => 'app.ai-tools.channel-audit',
        ],
        'more_tools' => [
            'name' => 'More AI Tools',
            'description' => 'Script Studio, Viral Hooks, Content Multiplier, Thumbnail Arena and more',
            'icon' => 'fa-light fa-grid-2-plus',
            'color' => 'from-indigo-500 to-violet-500',
            'credits' => 0,
            'route' => 'app.ai-tools.more-tools',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sub-Tool Definitions
    |--------------------------------------------------------------------------
    */
    'sub_tools' => [
        'script_studio' => [
            'name' => 'Script Studio',
            'description' => 'Generate complete video scripts with hooks, sections and CTAs',
            'icon' => 'fa-light fa-scroll',
            'color' => 'from-blue-500 to-indigo-500',
            'credits' => 2,
            'route' => 'app.ai-tools.script-studio',
        ],
        'viral_hooks' => [
            'name' => 'Viral Hook Lab',
            'description' => 'Generate attention-grabbing hooks with effectiveness scores',
            'icon' => 'fa-light fa-bolt',
            'color' => 'from-yellow-500 to-orange-500',
            'credits' => 1,
            'route' => 'app.ai-tools.viral-hooks',
        ],
        'content_multiplier' => [
            'name' => 'Content Multiplier',
            'description' => 'Repurpose content into Shorts, threads, blogs, newsletters and more',
            'icon' => 'fa-light fa-clone',
            'color' => 'from-teal-500 to-cyan-500',
            'credits' => 2,
            'route' => 'app.ai-tools.content-multiplier',
        ],
        'thumbnail_arena' => [
            'name' => 'Thumbnail Arena',
            'description' => 'AI-powered head-to-head thumbnail comparison with detailed analysis',
            'icon' => 'fa-light fa-swords',
            'color' => 'from-red-500 to-purple-500',
            'credits' => 2,
            'route' => 'app.ai-tools.thumbnail-arena',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Styles
    |--------------------------------------------------------------------------
    */
    'thumbnail_styles' => [
        'bold_text' => 'Bold Text Overlay',
        'cinematic' => 'Cinematic',
        'minimalist' => 'Minimalist',
        'vibrant' => 'Vibrant & Colorful',
        'dark_moody' => 'Dark & Moody',
        'professional' => 'Professional / Corporate',
        'playful' => 'Playful / Fun',
        'retro' => 'Retro / Vintage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hook Styles
    |--------------------------------------------------------------------------
    */
    'hook_styles' => [
        'question' => 'Question',
        'controversy' => 'Controversy',
        'promise' => 'Promise',
        'story' => 'Story',
        'statistic' => 'Statistic',
        'challenge' => 'Challenge',
    ],

    /*
    |--------------------------------------------------------------------------
    | Script Styles
    |--------------------------------------------------------------------------
    */
    'script_styles' => [
        'engaging' => 'Engaging',
        'educational' => 'Educational',
        'storytelling' => 'Storytelling',
        'listicle' => 'Listicle',
    ],

    /*
    |--------------------------------------------------------------------------
    | Script Durations
    |--------------------------------------------------------------------------
    */
    'script_durations' => [
        'short' => ['label' => 'Short (30-60s)', 'words' => '75-150'],
        'medium' => ['label' => 'Medium (2-5 min)', 'words' => '300-750'],
        'long' => ['label' => 'Long (8-15 min)', 'words' => '1200-2250'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Multiplier Formats
    |--------------------------------------------------------------------------
    */
    'multiplier_formats' => [
        'shorts_script' => 'Shorts / Reels Script',
        'twitter_thread' => 'Twitter/X Thread',
        'blog_post' => 'Blog Post',
        'quotes' => 'Quotable Quotes',
        'email_newsletter' => 'Email Newsletter',
        'linkedin_post' => 'LinkedIn Post',
        'instagram_caption' => 'Instagram Caption',
    ],
];
