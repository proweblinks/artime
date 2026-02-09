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
            'description' => 'Transform your video\'s discoverability with AI-powered SEO optimization. Generate optimized titles, descriptions, and tags to rank higher.',
            'icon' => 'fa-light fa-chart-line-up',
            'emoji' => "\xF0\x9F\x9A\x80",
            'color' => 'from-blue-500 to-purple-600',
            'cta_text' => 'Optimize Video',
            'cta_color' => 'text-blue-400',
            'credits' => 1,
            'route' => 'app.ai-tools.video-optimizer',
        ],
        'competitor_analysis' => [
            'name' => 'Competitor Analysis',
            'description' => 'Reverse-engineer the success of any video. Reveal competitor SEO strategies, strengths, and weaknesses to outperform them.',
            'icon' => 'fa-light fa-magnifying-glass-chart',
            'emoji' => "\xF0\x9F\x8E\xAF",
            'color' => 'from-red-500 to-orange-600',
            'cta_text' => 'Analyze Competitor',
            'cta_color' => 'text-red-400',
            'credits' => 2,
            'route' => 'app.ai-tools.competitor-analysis',
        ],
        'trend_predictor' => [
            'name' => 'Trend Predictor',
            'description' => 'Stay ahead of the curve with AI-powered trend forecasting. Discover what\'s hot and predict future viral topics in your niche.',
            'icon' => 'fa-light fa-arrow-trend-up',
            'emoji' => "\xF0\x9F\x93\x88",
            'color' => 'from-cyan-500 to-blue-600',
            'cta_text' => 'Predict Trends',
            'cta_color' => 'text-cyan-400',
            'credits' => 2,
            'route' => 'app.ai-tools.trend-predictor',
        ],
        'ai_thumbnails' => [
            'name' => 'AI Thumbnails',
            'description' => 'Generate eye-catching thumbnails with AI. Multiple styles, reference images, and batch generation for maximum click-through rates.',
            'icon' => 'fa-light fa-image',
            'emoji' => "\xF0\x9F\x8E\xA8",
            'color' => 'from-pink-500 to-rose-600',
            'cta_text' => 'Create Thumbnails',
            'cta_color' => 'text-pink-400',
            'credits' => 3,
            'route' => 'app.ai-tools.ai-thumbnails',
        ],
        'channel_audit' => [
            'name' => 'Channel Audit Pro',
            'description' => 'Get a complete health check for any YouTube channel. AI analyzes performance and provides actionable growth recommendations.',
            'icon' => 'fa-light fa-clipboard-check',
            'emoji' => "\xF0\x9F\x94\x8D",
            'color' => 'from-emerald-500 to-teal-600',
            'cta_text' => 'Start Audit',
            'cta_color' => 'text-emerald-400',
            'credits' => 3,
            'route' => 'app.ai-tools.channel-audit',
        ],
        'more_tools' => [
            'name' => 'More AI Tools',
            'description' => 'Script Studio, Viral Hook Lab, Content Multiplier, Thumbnail Arena and more creative tools to supercharge your workflow.',
            'icon' => 'fa-light fa-grid-2-plus',
            'emoji' => "\xF0\x9F\xA7\xB0",
            'color' => 'from-purple-500 to-indigo-600',
            'cta_text' => 'Explore Tools',
            'cta_color' => 'text-purple-400',
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
    | Thumbnail Modes
    |--------------------------------------------------------------------------
    */
    'thumbnail_modes' => [
        'quick'     => ['name' => 'Quick', 'credits' => 2, 'icon' => 'fa-bolt', 'description' => 'Fast generation, no reference needed'],
        'reference' => ['name' => 'Reference', 'credits' => 4, 'icon' => 'fa-image', 'description' => 'Upload reference for style transfer'],
        'upgrade'   => ['name' => 'Upgrade', 'credits' => 4, 'icon' => 'fa-arrow-up', 'description' => 'Upgrade existing thumbnail from YouTube'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Categories
    |--------------------------------------------------------------------------
    */
    'thumbnail_categories' => [
        'general' => 'General',
        'gaming' => 'Gaming',
        'tutorial' => 'Tutorial',
        'vlog' => 'Vlog',
        'review' => 'Review',
        'news' => 'News',
        'entertainment' => 'Entertainment',
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Styles
    |--------------------------------------------------------------------------
    */
    'thumbnail_styles' => [
        'professional' => 'Professional',
        'dramatic' => 'Dramatic',
        'minimal' => 'Minimal',
        'bold' => 'Bold',
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Upscale Credits
    |--------------------------------------------------------------------------
    */
    'thumbnail_upscale_credits' => 2,

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
