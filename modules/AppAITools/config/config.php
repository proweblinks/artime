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
        'enterprise_suite' => [
            'name' => 'Enterprise Suite',
            'description' => '15 premium AI tools for monetization, analytics, brand deals, audience profiling, revenue automation and more.',
            'icon' => 'fa-light fa-crown',
            'emoji' => "\xF0\x9F\x91\x91",
            'color' => 'from-amber-500 to-yellow-600',
            'cta_text' => 'Enter Suite',
            'cta_color' => 'text-amber-400',
            'credits' => 0,
            'route' => 'app.ai-tools.enterprise-suite',
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
        'quick'     => ['name' => 'Quick', 'credits' => 2, 'icon' => 'fa-bolt', 'description' => 'Fast generation, no reference needed', 'features' => ['~5 seconds', 'No reference']],
        'reference' => ['name' => 'Reference', 'credits' => 4, 'icon' => 'fa-image', 'description' => 'Upload reference for style transfer', 'features' => ['~10 seconds', 'Upload face/product']],
        'upgrade'   => ['name' => 'Upgrade', 'credits' => 4, 'icon' => 'fa-arrow-up', 'description' => 'Upgrade existing thumbnail from YouTube', 'features' => ['~10 seconds', 'From YouTube URL']],
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Categories
    |--------------------------------------------------------------------------
    */
    'thumbnail_categories' => [
        'general'       => ['name' => 'General',       'icon' => "\xF0\x9F\x8E\xAF"],
        'gaming'        => ['name' => 'Gaming',        'icon' => "\xF0\x9F\x8E\xAE"],
        'tutorial'      => ['name' => 'Tutorial',      'icon' => "\xF0\x9F\x93\x9A"],
        'vlog'          => ['name' => 'Vlog',          'icon' => "\xF0\x9F\x93\xB9"],
        'review'        => ['name' => 'Review',        'icon' => "\xF0\x9F\x93\xA6"],
        'news'          => ['name' => 'News',          'icon' => "\xF0\x9F\x93\xB0"],
        'entertainment' => ['name' => 'Entertainment', 'icon' => "\xF0\x9F\x8E\xAC"],
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Styles
    |--------------------------------------------------------------------------
    */
    'thumbnail_styles' => [
        'professional' => ['name' => 'Professional', 'icon' => "\xF0\x9F\x92\xBC", 'description' => 'Clean & polished'],
        'dramatic'     => ['name' => 'Dramatic',     'icon' => "\xF0\x9F\x8E\xAC", 'description' => 'High contrast & cinematic'],
        'minimal'      => ['name' => 'Minimal',      'icon' => "\xE2\x9C\xA8",     'description' => 'Simple & elegant'],
        'bold'         => ['name' => 'Bold',         'icon' => "\xF0\x9F\x94\xA5", 'description' => 'Eye-catching & vibrant'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Image Models (Gemini-based)
    |--------------------------------------------------------------------------
    | Mirrors VideoWizard IMAGE_MODELS - same Gemini model IDs.
    | 'default' uses AI::process() which routes to whatever the admin configured.
    */
    'thumbnail_image_models' => [
        'nanobanana-pro' => [
            'name' => 'NanoBanana Pro',
            'description' => 'Best quality, 4K output, up to 5 face references',
            'credits' => 6,
            'provider' => 'gemini',
            'model' => 'gemini-3-pro-image-preview',
            'resolution' => '4K',
            'maxHumanRefs' => 5,
        ],
        'nanobanana' => [
            'name' => 'NanoBanana',
            'description' => 'Good quality, fast generation',
            'credits' => 3,
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash-image',
            'resolution' => '1K',
            'maxHumanRefs' => 3,
        ],
        'default' => [
            'name' => 'Standard',
            'description' => 'Uses admin-configured AI provider',
            'credits' => 2,
            'provider' => 'default',
            'model' => null,
            'resolution' => '1K',
            'maxHumanRefs' => 0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Upscale Credits
    |--------------------------------------------------------------------------
    */
    'thumbnail_upscale_credits' => 2,

    /*
    |--------------------------------------------------------------------------
    | Enterprise Suite Categories
    |--------------------------------------------------------------------------
    */
    'enterprise_categories' => [
        'all'           => ['name' => 'All Tools',      'icon' => 'fa-light fa-grid-2'],
        'optimization'  => ['name' => 'Optimization',   'icon' => 'fa-light fa-chart-line-up'],
        'analytics'     => ['name' => 'Analytics',       'icon' => 'fa-light fa-chart-pie'],
        'monetization'  => ['name' => 'Monetization',   'icon' => 'fa-light fa-sack-dollar'],
        'content'       => ['name' => 'Content',         'icon' => 'fa-light fa-pen-nib'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enterprise Suite Tools
    |--------------------------------------------------------------------------
    */
    'enterprise_tools' => [
        'bulk-optimizer' => [
            'name' => 'Bulk Video Optimizer',
            'description' => 'Optimize multiple videos at once with AI-powered SEO',
            'icon' => 'fa-light fa-chart-line-up',
            'emoji' => "\xF0\x9F\x8E\xAF",
            'color' => 'from-blue-500 to-indigo-600',
            'category' => 'optimization',
            'credits' => 2,
            'route' => 'app.ai-tools.video-optimizer',
            'is_existing' => true,
        ],
        'placement-finder' => [
            'name' => 'Placement Finder',
            'description' => 'Find YouTube channels for Google Ads placements in your niche',
            'icon' => 'fa-light fa-bullseye-pointer',
            'emoji' => "\xF0\x9F\x8E\xAC",
            'color' => 'from-purple-500 to-violet-600',
            'category' => 'optimization',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.placement-finder',
            'is_existing' => false,
            'loading_steps' => ['Analyzing your channel', 'Understanding your content', 'Identifying your niche', 'Searching similar channels', 'Scoring relevance', 'Compiling results'],
        ],
        'viral-predictor' => [
            'name' => 'Viral Score Predictor',
            'description' => 'Predict your video\'s viral potential before publishing',
            'icon' => 'fa-light fa-crystal-ball',
            'emoji' => "\xF0\x9F\x94\xAE",
            'color' => 'from-pink-500 to-rose-600',
            'category' => 'analytics',
            'credits' => 2,
            'route' => 'app.ai-tools.trend-predictor',
            'is_existing' => true,
        ],
        'monetization-analyzer' => [
            'name' => 'Monetization Analyzer',
            'description' => 'Estimate channel earnings and optimize revenue streams',
            'icon' => 'fa-light fa-coins',
            'emoji' => "\xF0\x9F\x92\xB0",
            'color' => 'from-green-500 to-emerald-600',
            'category' => 'monetization',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.monetization-analyzer',
            'is_existing' => false,
            'loading_steps' => ['Fetching channel data', 'Analyzing view patterns', 'Calculating CPM estimates', 'Generating revenue report'],
        ],
        'script-writer' => [
            'name' => 'Script Writer Pro',
            'description' => 'AI-powered video scripts that engage and retain viewers',
            'icon' => 'fa-light fa-scroll',
            'emoji' => "\xF0\x9F\x93\x9D",
            'color' => 'from-amber-500 to-orange-600',
            'category' => 'content',
            'credits' => 2,
            'route' => 'app.ai-tools.script-studio',
            'is_existing' => true,
        ],
        'sponsorship-calculator' => [
            'name' => 'Sponsorship Rate Calculator',
            'description' => 'Calculate your true market value for brand deals',
            'icon' => 'fa-light fa-gem',
            'emoji' => "\xF0\x9F\x92\x8E",
            'color' => 'from-purple-500 to-pink-600',
            'category' => 'monetization',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.sponsorship-calculator',
            'is_existing' => false,
            'loading_steps' => ['Fetching channel data', 'Analyzing engagement', 'Determining niche CPM', 'Calculating rates', 'Industry comparison', 'Generating tips'],
        ],
        'revenue-diversification' => [
            'name' => 'Revenue Diversification',
            'description' => 'Identify untapped income streams for your channel',
            'icon' => 'fa-light fa-chart-pie',
            'emoji' => "\xF0\x9F\x93\x8A",
            'color' => 'from-blue-500 to-cyan-600',
            'category' => 'monetization',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.revenue-diversification',
            'is_existing' => false,
            'loading_steps' => ['Analyzing channel', 'Auditing revenue', 'Identifying gaps', 'Calculating potential', 'Building action plan'],
        ],
        'cpm-booster' => [
            'name' => 'CPM Booster Strategist',
            'description' => 'Optimize content for higher-paying advertisers',
            'icon' => 'fa-light fa-rocket',
            'emoji' => "\xF0\x9F\x92\xB0",
            'color' => 'from-green-500 to-emerald-600',
            'category' => 'monetization',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.cpm-booster',
            'is_existing' => false,
            'loading_steps' => ['Analyzing niche', 'Checking CPM trends', 'Finding high-CPM keywords', 'Generating video ideas', 'Building calendar'],
        ],
        'audience-profiler' => [
            'name' => 'Audience Monetization Profiler',
            'description' => 'Deep analysis of your audience\'s spending behavior',
            'icon' => 'fa-light fa-users-viewfinder',
            'emoji' => "\xF0\x9F\x93\xB1",
            'color' => 'from-orange-500 to-red-600',
            'category' => 'analytics',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.audience-profiler',
            'is_existing' => false,
            'loading_steps' => ['Fetching channel data', 'Analyzing demographics', 'Profiling segments', 'Finding products', 'Creating offers'],
        ],
        'digital-product-architect' => [
            'name' => 'Digital Product Architect',
            'description' => 'Design and price digital products for your audience',
            'icon' => 'fa-light fa-cart-shopping',
            'emoji' => "\xF0\x9F\x9B\x92",
            'color' => 'from-indigo-500 to-purple-600',
            'category' => 'monetization',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.digital-product-architect',
            'is_existing' => false,
            'loading_steps' => ['Analyzing content', 'Identifying expertise', 'Finding product gaps', 'Generating product ideas', 'Building launch plan'],
        ],
        'affiliate-finder' => [
            'name' => 'Affiliate Goldmine Finder',
            'description' => 'Discover high-paying affiliate opportunities in your niche',
            'icon' => 'fa-light fa-arrow-trend-up',
            'emoji' => "\xF0\x9F\x93\x88",
            'color' => 'from-yellow-500 to-orange-600',
            'category' => 'monetization',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.affiliate-finder',
            'is_existing' => false,
            'loading_steps' => ['Analyzing niche', 'Scanning affiliate networks', 'Finding programs', 'Matching products', 'Generating scripts'],
        ],
        'multi-income-converter' => [
            'name' => 'Multi-Income Converter',
            'description' => 'Turn one video into multiple revenue streams',
            'icon' => 'fa-light fa-clone',
            'emoji' => "\xF0\x9F\x8E\xAC",
            'color' => 'from-teal-500 to-cyan-600',
            'category' => 'monetization',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.multi-income-converter',
            'is_existing' => false,
            'loading_steps' => ['Analyzing video content', 'Extracting key points', 'Finding platforms', 'Creating content pieces', 'Building strategy'],
        ],
        'brand-deal-matchmaker' => [
            'name' => 'Brand Deal Matchmaker',
            'description' => 'Find perfect brand partnerships for your channel',
            'icon' => 'fa-light fa-handshake',
            'emoji' => "\xF0\x9F\xA4\x9D",
            'color' => 'from-pink-500 to-rose-600',
            'category' => 'monetization',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.brand-deal-matchmaker',
            'is_existing' => false,
            'loading_steps' => ['Analyzing channel', 'Identifying niche', 'Matching brands', 'Creating pitches', 'Building strategy'],
        ],
        'licensing-scout' => [
            'name' => 'Licensing & Syndication Scout',
            'description' => 'Find licensing opportunities for your content',
            'icon' => 'fa-light fa-file-certificate',
            'emoji' => "\xF0\x9F\x93\x9C",
            'color' => 'from-teal-500 to-cyan-600',
            'category' => 'monetization',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.licensing-scout',
            'is_existing' => false,
            'loading_steps' => ['Analyzing content', 'Scanning platforms', 'Finding opportunities', 'Evaluating networks', 'Creating action plan'],
        ],
        'revenue-automation' => [
            'name' => 'Revenue Automation Pipeline',
            'description' => 'Build automated revenue systems for your channel',
            'icon' => 'fa-light fa-gears',
            'emoji' => "\xE2\x9A\x99\xEF\xB8\x8F",
            'color' => 'from-orange-500 to-red-600',
            'category' => 'monetization',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.revenue-automation',
            'is_existing' => false,
            'loading_steps' => ['Analyzing channel', 'Mapping revenue streams', 'Finding automations', 'Building tool stack', 'Creating timeline'],
        ],
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
