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
            'category' => 'optimization',
            'last_updated' => '2026-01-15',
        ],
        'competitor_analysis' => [
            'name' => 'Competitor Analysis',
            'description' => 'Channel-level competitive intelligence. Analyze any competitor\'s strategy, find content gaps, exploit weaknesses, and build a battle plan.',
            'icon' => 'fa-light fa-magnifying-glass-chart',
            'emoji' => "\xF0\x9F\x8E\xAF",
            'color' => 'from-red-500 to-orange-600',
            'cta_text' => 'Analyze Competitor',
            'cta_color' => 'text-red-400',
            'credits' => 3,
            'route' => 'app.ai-tools.competitor-analysis',
            'category' => 'analytics',
            'last_updated' => '2026-02-10',
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
            'category' => 'analytics',
            'last_updated' => '2026-01-20',
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
            'category' => 'content',
            'last_updated' => '2026-01-25',
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
            'category' => 'analytics',
            'last_updated' => '2026-02-01',
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
            'category' => 'content',
            'last_updated' => '2026-01-10',
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
            'category' => 'monetization',
            'last_updated' => '2026-02-05',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hub Categories (for category filter tabs)
    |--------------------------------------------------------------------------
    */
    'hub_categories' => [
        'all'           => ['name' => 'All Tools',     'icon' => 'fa-light fa-grid-2',          'emoji' => "\xE2\x9C\xA8"],
        'optimization'  => ['name' => 'Optimization',  'icon' => 'fa-light fa-chart-line-up',   'emoji' => "\xF0\x9F\x9A\x80"],
        'analytics'     => ['name' => 'Analytics',      'icon' => 'fa-light fa-chart-pie',       'emoji' => "\xF0\x9F\x93\x8A"],
        'content'       => ['name' => 'Content',        'icon' => 'fa-light fa-pen-nib',         'emoji' => "\xF0\x9F\x8E\xA8"],
        'monetization'  => ['name' => 'Monetization',  'icon' => 'fa-light fa-sack-dollar',     'emoji' => "\xF0\x9F\x92\xB0"],
    ],

    /*
    |--------------------------------------------------------------------------
    | Suggestion Engine (What should I do next?)
    |--------------------------------------------------------------------------
    */
    'suggestion_engine' => [
        'questions' => [
            ['q' => 'I want to grow my channel', 'tools' => ['channel_audit', 'trend_predictor', 'competitor_analysis']],
            ['q' => 'I need help with my next video', 'tools' => ['video_optimizer', 'ai_thumbnails', 'more_tools']],
            ['q' => 'I want to spy on competitors', 'tools' => ['competitor_analysis', 'channel_audit']],
            ['q' => 'I want to make money', 'tools' => ['enterprise_suite', 'channel_audit']],
            ['q' => 'I want better thumbnails', 'tools' => ['ai_thumbnails']],
            ['q' => 'I need content ideas', 'tools' => ['trend_predictor', 'more_tools']],
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
    | Enterprise Suite Platforms
    |--------------------------------------------------------------------------
    */
    'enterprise_platforms' => [
        'youtube' => [
            'name' => 'YouTube',
            'icon' => 'fa-brands fa-youtube',
            'color' => '#FF0000',
            'emoji' => "\xF0\x9F\x93\xBA",
            'status' => 'active',
            'description' => 'Monetize & grow your YouTube channel',
            'tool_count' => 15,
            'planned_tools' => [],
        ],
        'tiktok' => [
            'name' => 'TikTok',
            'icon' => 'fa-brands fa-tiktok',
            'color' => '#00F2EA',
            'emoji' => "\xF0\x9F\x8E\xB5",
            'status' => 'active',
            'description' => 'Grow & monetize on TikTok',
            'tool_count' => 12,
            'planned_tools' => [],
        ],
        'instagram' => [
            'name' => 'Instagram',
            'icon' => 'fa-brands fa-instagram',
            'color' => '#E1306C',
            'emoji' => "\xF0\x9F\x93\xB8",
            'status' => 'coming_soon',
            'description' => 'Grow your Instagram presence & revenue',
            'tool_count' => 0,
            'planned_tools' => [
                ['name' => 'Reels Monetization Analyzer', 'emoji' => "\xF0\x9F\x92\xB0", 'description' => 'Analyze and optimize Reels for Play Bonuses and brand deals'],
                ['name' => 'Instagram SEO Optimizer', 'emoji' => "\xF0\x9F\x94\x8D", 'description' => 'Optimize bio, captions, and alt text for Instagram search'],
                ['name' => 'Story Engagement Planner', 'emoji' => "\xF0\x9F\x93\x96", 'description' => 'Plan interactive stories that drive engagement and sales'],
                ['name' => 'Carousel Content Builder', 'emoji' => "\xF0\x9F\x8E\xA0", 'description' => 'Design high-save carousel post strategies'],
                ['name' => 'Collab Post Matcher', 'emoji' => "\xF0\x9F\xA4\x9D", 'description' => 'Find ideal collab partners based on audience overlap'],
                ['name' => 'Link-in-Bio Optimizer', 'emoji' => "\xF0\x9F\x94\x97", 'description' => 'Optimize your link tree for maximum conversions'],
                ['name' => 'DM Automation Strategist', 'emoji' => "\xF0\x9F\x92\xAC", 'description' => 'Design keyword-triggered DM funnels for lead gen'],
                ['name' => 'Hashtag Performance Tracker', 'emoji' => "#\xE2\x83\xA3", 'description' => 'Analyze hashtag performance and find winning combinations'],
                ['name' => 'Aesthetic & Brand Analyzer', 'emoji' => "\xF0\x9F\x8E\xA8", 'description' => 'Analyze visual consistency and brand cohesion'],
                ['name' => 'Shopping Tag Optimizer', 'emoji' => "\xF0\x9F\x9B\x8D\xEF\xB8\x8F", 'description' => 'Optimize product tagging strategy for Instagram Shopping'],
            ],
        ],
        'facebook' => [
            'name' => 'Facebook',
            'icon' => 'fa-brands fa-facebook',
            'color' => '#1877F2',
            'emoji' => "\xF0\x9F\x91\xA5",
            'status' => 'coming_soon',
            'description' => 'Monetize Facebook pages & groups',
            'tool_count' => 0,
            'planned_tools' => [
                ['name' => 'Reels Bonus Optimizer', 'emoji' => "\xF0\x9F\x92\xB5", 'description' => 'Maximize Facebook Reels Play Bonus earnings'],
                ['name' => 'Group Monetization Planner', 'emoji' => "\xF0\x9F\x91\xA5", 'description' => 'Turn Facebook Groups into revenue engines'],
                ['name' => 'Ad Break Optimizer', 'emoji' => "\xF0\x9F\x93\xBA", 'description' => 'Optimize in-stream ad placement and earnings'],
                ['name' => 'Page Growth Analyzer', 'emoji' => "\xF0\x9F\x93\x88", 'description' => 'Analyze page performance and growth opportunities'],
                ['name' => 'Facebook Shop Builder', 'emoji' => "\xF0\x9F\x9B\x92", 'description' => 'Optimize Facebook Shop listings and conversions'],
                ['name' => 'Content Recycler', 'emoji' => "\xE2\x99\xBB\xEF\xB8\x8F", 'description' => 'Repurpose top-performing content across formats'],
            ],
        ],
        'linkedin' => [
            'name' => 'LinkedIn',
            'icon' => 'fa-brands fa-linkedin',
            'color' => '#0077B5',
            'emoji' => "\xF0\x9F\x92\xBC",
            'status' => 'coming_soon',
            'description' => 'Build authority & generate leads on LinkedIn',
            'tool_count' => 0,
            'planned_tools' => [
                ['name' => 'Thought Leadership Planner', 'emoji' => "\xF0\x9F\x92\xA1", 'description' => 'Build a content strategy that positions you as an authority'],
                ['name' => 'LinkedIn SEO Optimizer', 'emoji' => "\xF0\x9F\x94\x8D", 'description' => 'Optimize profile and posts for LinkedIn search algorithm'],
                ['name' => 'Newsletter Growth Analyzer', 'emoji' => "\xF0\x9F\x93\xA7", 'description' => 'Grow and monetize your LinkedIn newsletter'],
                ['name' => 'Lead Magnet Builder', 'emoji' => "\xF0\x9F\xA7\xB2", 'description' => 'Create high-converting lead magnets for B2B audiences'],
                ['name' => 'Post Hook Generator', 'emoji' => "\xF0\x9F\xAA\x9D", 'description' => 'Generate scroll-stopping opening lines for posts'],
                ['name' => 'Connection Strategy Planner', 'emoji' => "\xF0\x9F\xA4\x9D", 'description' => 'Strategic outreach and networking plan builder'],
            ],
        ],
        'x_twitter' => [
            'name' => 'X / Twitter',
            'icon' => 'fa-brands fa-x-twitter',
            'color' => '#000000',
            'emoji' => "\xF0\x9F\x90\xA6",
            'status' => 'coming_soon',
            'description' => 'Monetize your X presence',
            'tool_count' => 0,
            'planned_tools' => [
                ['name' => 'Thread Strategy Builder', 'emoji' => "\xF0\x9F\xA7\xB5", 'description' => 'Build viral thread strategies that drive followers and revenue'],
                ['name' => 'Ad Revenue Optimizer', 'emoji' => "\xF0\x9F\x92\xB0", 'description' => 'Optimize for X Premium ad revenue sharing'],
                ['name' => 'Spaces Monetization Planner', 'emoji' => "\xF0\x9F\x8E\x99\xEF\xB8\x8F", 'description' => 'Plan and monetize X Spaces for maximum impact'],
                ['name' => 'Viral Hook Analyzer', 'emoji' => "\xF0\x9F\x94\xA5", 'description' => 'Analyze what makes posts go viral on X'],
                ['name' => 'Subscription Strategy', 'emoji' => "\xE2\xAD\x90", 'description' => 'Build a premium subscription offering on X'],
                ['name' => 'Engagement Optimizer', 'emoji' => "\xF0\x9F\x93\x8A", 'description' => 'Optimize posting times, formats, and engagement tactics'],
            ],
        ],
        'cross_platform' => [
            'name' => 'Cross-Platform',
            'icon' => 'fa-light fa-globe',
            'color' => '#8B5CF6',
            'emoji' => "\xF0\x9F\x8C\x90",
            'status' => 'coming_soon',
            'description' => 'Multi-platform strategies & repurposing',
            'tool_count' => 0,
            'planned_tools' => [
                ['name' => 'Content Repurposing Engine', 'emoji' => "\xE2\x99\xBB\xEF\xB8\x8F", 'description' => 'Transform one piece of content into 10+ platform-specific formats'],
                ['name' => 'Cross-Platform Analytics', 'emoji' => "\xF0\x9F\x93\x8A", 'description' => 'Unified analytics dashboard across all platforms'],
                ['name' => 'Audience Migration Planner', 'emoji' => "\xF0\x9F\x9A\x80", 'description' => 'Move followers between platforms strategically'],
                ['name' => 'Multi-Platform Scheduler', 'emoji' => "\xF0\x9F\x93\x85", 'description' => 'Optimal posting schedule across all your platforms'],
                ['name' => 'Revenue Portfolio Analyzer', 'emoji' => "\xF0\x9F\x92\xBC", 'description' => 'Analyze and balance revenue across all platforms'],
                ['name' => 'Brand Consistency Checker', 'emoji' => "\xF0\x9F\x8E\xA8", 'description' => 'Ensure consistent branding across all your platforms'],
            ],
        ],
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
            'platform' => 'youtube',
            'credits' => 2,
            'route' => 'app.ai-tools.video-optimizer',
            'is_existing' => true,
            'tiers' => [],
            'next_steps' => [
                ['tool' => 'cpm-booster', 'reason' => 'Boost CPM on your optimized videos'],
                ['tool' => 'monetization-analyzer', 'reason' => 'See how optimization affects revenue'],
            ],
            'inputs' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'Channel URL', 'required' => true],
            ],
            'result_sections' => [],
            'estimated_seconds' => 10,
        ],
        'placement-finder' => [
            'name' => 'Placement Finder',
            'description' => 'Find YouTube channels for Google Ads placements in your niche',
            'icon' => 'fa-light fa-bullseye-pointer',
            'emoji' => "\xF0\x9F\x8E\xAC",
            'color' => 'from-purple-500 to-violet-600',
            'category' => 'optimization',
            'platform' => 'youtube',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.placement-finder',
            'is_existing' => false,
            'loading_steps' => ['Analyzing your channel', 'Identifying your niche', 'Finding placement channels', 'Scoring audience relevance', 'Building campaign strategy', 'Compiling results'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'audience-profiler', 'reason' => 'Profile the audience you\'re targeting'],
                ['tool' => 'cpm-booster', 'reason' => 'Optimize CPM for your placements'],
            ],
            'inputs' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'Channel URL', 'required' => true],
                ['key' => 'niche', 'type' => 'text', 'label' => 'Target Niche', 'required' => false],
            ],
            'result_sections' => ['placement_score', 'placements', 'campaign_strategy', 'niche_insights'],
            'estimated_seconds' => 18,
        ],
        'viral-predictor' => [
            'name' => 'Viral Score Predictor',
            'description' => 'Predict your video\'s viral potential before publishing',
            'icon' => 'fa-light fa-crystal-ball',
            'emoji' => "\xF0\x9F\x94\xAE",
            'color' => 'from-pink-500 to-rose-600',
            'category' => 'analytics',
            'platform' => 'youtube',
            'credits' => 2,
            'route' => 'app.ai-tools.trend-predictor',
            'is_existing' => true,
            'tiers' => [],
            'next_steps' => [
                ['tool' => 'placement-finder', 'reason' => 'Find placements for viral content'],
                ['tool' => 'sponsorship-calculator', 'reason' => 'Calculate rates for viral reach'],
            ],
            'inputs' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'Video URL', 'required' => true],
            ],
            'result_sections' => [],
            'estimated_seconds' => 10,
        ],
        'monetization-analyzer' => [
            'name' => 'Monetization Analyzer',
            'description' => 'Estimate channel earnings and optimize revenue streams',
            'icon' => 'fa-light fa-coins',
            'emoji' => "\xF0\x9F\x92\xB0",
            'color' => 'from-green-500 to-emerald-600',
            'category' => 'monetization',
            'platform' => 'youtube',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.monetization-analyzer',
            'is_existing' => false,
            'loading_steps' => ['Fetching channel data', 'Analyzing view patterns', 'Calculating CPM estimates', 'Generating revenue report'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'revenue-diversification', 'reason' => 'Find untapped income streams'],
                ['tool' => 'cpm-booster', 'reason' => 'Boost your ad revenue CPM'],
            ],
            'inputs' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'Channel URL', 'required' => true],
            ],
            'result_sections' => ['monetization_score', 'revenue_breakdown', 'growth_opportunities', 'action_plan'],
            'estimated_seconds' => 15,
        ],
        'script-writer' => [
            'name' => 'Script Writer Pro',
            'description' => 'AI-powered video scripts that engage and retain viewers',
            'icon' => 'fa-light fa-scroll',
            'emoji' => "\xF0\x9F\x93\x9D",
            'color' => 'from-amber-500 to-orange-600',
            'category' => 'content',
            'platform' => 'youtube',
            'credits' => 2,
            'route' => 'app.ai-tools.script-studio',
            'is_existing' => true,
            'tiers' => [],
            'next_steps' => [
                ['tool' => 'multi-income-converter', 'reason' => 'Turn scripts into multiple content pieces'],
                ['tool' => 'affiliate-finder', 'reason' => 'Add affiliate mentions to your script'],
            ],
            'inputs' => [
                ['key' => 'topic', 'type' => 'text', 'label' => 'Video Topic', 'required' => true],
            ],
            'result_sections' => [],
            'estimated_seconds' => 10,
        ],
        'sponsorship-calculator' => [
            'name' => 'Sponsorship Rate Calculator',
            'description' => 'Calculate your true market value for brand deals',
            'icon' => 'fa-light fa-gem',
            'emoji' => "\xF0\x9F\x92\x8E",
            'color' => 'from-purple-500 to-pink-600',
            'category' => 'monetization',
            'platform' => 'youtube',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.sponsorship-calculator',
            'is_existing' => false,
            'loading_steps' => ['Fetching channel data', 'Analyzing engagement', 'Determining niche CPM', 'Calculating rates', 'Industry comparison', 'Generating tips'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'brand-deal-matchmaker', 'reason' => 'Find brands that match your rates'],
                ['tool' => 'audience-profiler', 'reason' => 'Strengthen your pitch with audience data'],
            ],
            'inputs' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'Channel URL', 'required' => true],
            ],
            'result_sections' => ['sponsorship_score', 'rate_card', 'industry_comparison', 'pitch_tips'],
            'estimated_seconds' => 20,
        ],
        'revenue-diversification' => [
            'name' => 'Revenue Diversification',
            'description' => 'Identify untapped income streams for your channel',
            'icon' => 'fa-light fa-chart-pie',
            'emoji' => "\xF0\x9F\x93\x8A",
            'color' => 'from-blue-500 to-cyan-600',
            'category' => 'monetization',
            'platform' => 'youtube',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.revenue-diversification',
            'is_existing' => false,
            'loading_steps' => ['Analyzing channel', 'Auditing revenue', 'Identifying gaps', 'Calculating potential', 'Building action plan'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'digital-product-architect', 'reason' => 'Design products for your audience'],
                ['tool' => 'affiliate-finder', 'reason' => 'Discover affiliate programs in your niche'],
            ],
            'inputs' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'Channel URL', 'required' => true],
            ],
            'result_sections' => ['diversification_score', 'revenue_streams', 'gap_analysis', 'action_plan'],
            'estimated_seconds' => 18,
        ],
        'cpm-booster' => [
            'name' => 'CPM Booster Strategist',
            'description' => 'Optimize content for higher-paying advertisers',
            'icon' => 'fa-light fa-rocket',
            'emoji' => "\xF0\x9F\x92\xB0",
            'color' => 'from-green-500 to-emerald-600',
            'category' => 'monetization',
            'platform' => 'youtube',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.cpm-booster',
            'is_existing' => false,
            'loading_steps' => ['Analyzing niche', 'Checking CPM trends', 'Finding high-CPM keywords', 'Generating video ideas', 'Building calendar'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'monetization-analyzer', 'reason' => 'See full revenue potential'],
                ['tool' => 'placement-finder', 'reason' => 'Find high-CPM ad placements'],
            ],
            'inputs' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'Channel URL', 'required' => true],
                ['key' => 'niche', 'type' => 'text', 'label' => 'Niche', 'required' => false],
            ],
            'result_sections' => ['cpm_score', 'keyword_strategy', 'content_calendar', 'cpm_trends'],
            'estimated_seconds' => 20,
        ],
        'audience-profiler' => [
            'name' => 'Audience Monetization Profiler',
            'description' => 'Deep analysis of your audience\'s spending behavior',
            'icon' => 'fa-light fa-users-viewfinder',
            'emoji' => "\xF0\x9F\x93\xB1",
            'color' => 'from-orange-500 to-red-600',
            'category' => 'analytics',
            'platform' => 'youtube',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.audience-profiler',
            'is_existing' => false,
            'loading_steps' => ['Fetching channel data', 'Analyzing demographics', 'Profiling segments', 'Finding products', 'Creating offers'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'digital-product-architect', 'reason' => 'Create products for your audience'],
                ['tool' => 'brand-deal-matchmaker', 'reason' => 'Pitch brands with audience insights'],
            ],
            'inputs' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'Channel URL', 'required' => true],
            ],
            'result_sections' => ['audience_score', 'demographics', 'spending_behavior', 'product_recommendations'],
            'estimated_seconds' => 18,
        ],
        'digital-product-architect' => [
            'name' => 'Digital Product Architect',
            'description' => 'Design and price digital products for your audience',
            'icon' => 'fa-light fa-cart-shopping',
            'emoji' => "\xF0\x9F\x9B\x92",
            'color' => 'from-indigo-500 to-purple-600',
            'category' => 'monetization',
            'platform' => 'youtube',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.digital-product-architect',
            'is_existing' => false,
            'loading_steps' => ['Analyzing content', 'Identifying expertise', 'Finding product gaps', 'Generating product ideas', 'Building launch plan'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'audience-profiler', 'reason' => 'Validate product-audience fit'],
                ['tool' => 'multi-income-converter', 'reason' => 'Promote products from your videos'],
            ],
            'inputs' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'Channel URL', 'required' => true],
            ],
            'result_sections' => ['product_score', 'product_ideas', 'pricing_strategy', 'launch_plan'],
            'estimated_seconds' => 22,
        ],
        'affiliate-finder' => [
            'name' => 'Affiliate Goldmine Finder',
            'description' => 'Discover high-paying affiliate opportunities in your niche',
            'icon' => 'fa-light fa-arrow-trend-up',
            'emoji' => "\xF0\x9F\x93\x88",
            'color' => 'from-yellow-500 to-orange-600',
            'category' => 'monetization',
            'platform' => 'youtube',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.affiliate-finder',
            'is_existing' => false,
            'loading_steps' => ['Analyzing niche', 'Scanning affiliate networks', 'Finding programs', 'Matching products', 'Generating scripts'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'multi-income-converter', 'reason' => 'Embed affiliates into your content'],
                ['tool' => 'revenue-diversification', 'reason' => 'See all revenue stream options'],
            ],
            'inputs' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'Channel URL', 'required' => true],
                ['key' => 'niche', 'type' => 'text', 'label' => 'Niche', 'required' => false],
            ],
            'result_sections' => ['affiliate_score', 'programs', 'integration_scripts', 'revenue_estimates'],
            'estimated_seconds' => 20,
        ],
        'multi-income-converter' => [
            'name' => 'Multi-Income Converter',
            'description' => 'Turn one video into multiple revenue streams',
            'icon' => 'fa-light fa-clone',
            'emoji' => "\xF0\x9F\x8E\xAC",
            'color' => 'from-teal-500 to-cyan-600',
            'category' => 'monetization',
            'platform' => 'youtube',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.multi-income-converter',
            'is_existing' => false,
            'loading_steps' => ['Analyzing video content', 'Extracting key points', 'Finding platforms', 'Creating content pieces', 'Building strategy'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'licensing-scout', 'reason' => 'License your repurposed content'],
                ['tool' => 'digital-product-architect', 'reason' => 'Turn content into products'],
            ],
            'inputs' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'Video URL', 'required' => true],
            ],
            'result_sections' => ['conversion_score', 'content_pieces', 'platform_strategy', 'monetization_tips'],
            'estimated_seconds' => 18,
        ],
        'brand-deal-matchmaker' => [
            'name' => 'Brand Deal Matchmaker',
            'description' => 'Find perfect brand partnerships for your channel',
            'icon' => 'fa-light fa-handshake',
            'emoji' => "\xF0\x9F\xA4\x9D",
            'color' => 'from-pink-500 to-rose-600',
            'category' => 'monetization',
            'platform' => 'youtube',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.brand-deal-matchmaker',
            'is_existing' => false,
            'loading_steps' => ['Analyzing channel', 'Identifying niche', 'Matching brands', 'Creating pitches', 'Building strategy'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'sponsorship-calculator', 'reason' => 'Calculate your rates for these brands'],
                ['tool' => 'audience-profiler', 'reason' => 'Back your pitch with audience data'],
            ],
            'inputs' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'Channel URL', 'required' => true],
            ],
            'result_sections' => ['match_score', 'brand_matches', 'pitch_templates', 'outreach_strategy'],
            'estimated_seconds' => 20,
        ],
        'licensing-scout' => [
            'name' => 'Licensing & Syndication Scout',
            'description' => 'Find licensing opportunities for your content',
            'icon' => 'fa-light fa-file-certificate',
            'emoji' => "\xF0\x9F\x93\x9C",
            'color' => 'from-teal-500 to-cyan-600',
            'category' => 'monetization',
            'platform' => 'youtube',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.licensing-scout',
            'is_existing' => false,
            'loading_steps' => ['Analyzing content', 'Scanning platforms', 'Finding opportunities', 'Evaluating networks', 'Creating action plan'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'revenue-automation', 'reason' => 'Automate your licensing revenue'],
                ['tool' => 'multi-income-converter', 'reason' => 'Create licensable content from videos'],
            ],
            'inputs' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'Channel URL', 'required' => true],
            ],
            'result_sections' => ['licensing_score', 'opportunities', 'platform_analysis', 'action_plan'],
            'estimated_seconds' => 18,
        ],
        'revenue-automation' => [
            'name' => 'Revenue Automation Pipeline',
            'description' => 'Build automated revenue systems for your channel',
            'icon' => 'fa-light fa-gears',
            'emoji' => "\xE2\x9A\x99\xEF\xB8\x8F",
            'color' => 'from-orange-500 to-red-600',
            'category' => 'monetization',
            'platform' => 'youtube',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.revenue-automation',
            'is_existing' => false,
            'loading_steps' => ['Analyzing channel', 'Mapping revenue streams', 'Finding automations', 'Building tool stack', 'Creating timeline'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'monetization-analyzer', 'reason' => 'Identify what to automate first'],
                ['tool' => 'digital-product-architect', 'reason' => 'Create automated product funnels'],
            ],
            'inputs' => [
                ['key' => 'url', 'type' => 'url', 'label' => 'Channel URL', 'required' => true],
            ],
            'result_sections' => ['automation_score', 'tool_stack', 'revenue_streams', 'implementation_timeline'],
            'estimated_seconds' => 22,
        ],

        // ── Cross-Platform YouTube↔TikTok Tools ────────────────────

        'tiktok-yt-converter' => [
            'name' => 'YouTube → TikTok Converter',
            'description' => 'Convert YouTube videos into TikTok content strategies using real video data',
            'icon' => 'fa-light fa-arrow-right-arrow-left',
            'emoji' => "\xF0\x9F\x94\x84",
            'color' => 'from-red-500 to-cyan-500',
            'category' => 'content',
            'platform' => 'tiktok',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.tiktok-yt-converter',
            'is_existing' => false,
            'loading_steps' => ['Fetching YouTube video data', 'Analyzing content structure', 'Identifying best clips', 'Building TikTok adaptation', 'Generating hashtag strategy', 'Creating hook rewrites'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'tiktok-hook-analyzer', 'reason' => 'Perfect your TikTok hooks'],
                ['tool' => 'tiktok-hashtag-strategy', 'reason' => 'Build a deeper hashtag strategy'],
            ],
            'inputs' => [
                ['key' => 'youtube_url', 'type' => 'url', 'label' => 'YouTube Video URL', 'required' => true],
                ['key' => 'tiktok_style', 'type' => 'text', 'label' => 'TikTok Style', 'required' => false],
            ],
            'result_sections' => ['adaptation_score', 'video_overview', 'hook_rewrites', 'clip_suggestions', 'hashtag_strategy', 'sound_suggestions', 'caption_rewrites', 'format_tips'],
            'estimated_seconds' => 20,
        ],
        'tiktok-yt-arbitrage' => [
            'name' => 'Cross-Platform Arbitrage',
            'description' => 'Find content gaps between YouTube and TikTok using real channel data',
            'icon' => 'fa-light fa-chart-network',
            'emoji' => "\xF0\x9F\x93\x8A",
            'color' => 'from-violet-500 to-cyan-500',
            'category' => 'analytics',
            'platform' => 'tiktok',
            'credits' => 4,
            'route' => 'app.ai-tools.enterprise.tiktok-yt-arbitrage',
            'is_existing' => false,
            'loading_steps' => ['Fetching YouTube channel data', 'Analyzing top videos', 'Calculating performance metrics', 'Identifying content gaps', 'Finding TikTok opportunities', 'Building cross-platform strategy'],
            'tiers' => [
                'quick'    => ['credits' => 2, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 4, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 6, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'tiktok-yt-converter', 'reason' => 'Convert your best YouTube videos'],
                ['tool' => 'tiktok-viral-predictor', 'reason' => 'Predict viral potential of gap content'],
            ],
            'inputs' => [
                ['key' => 'youtube_channel', 'type' => 'url', 'label' => 'YouTube Channel URL', 'required' => true],
                ['key' => 'tiktok_niche', 'type' => 'text', 'label' => 'TikTok Niche', 'required' => false],
            ],
            'result_sections' => ['arbitrage_score', 'content_gaps', 'first_mover_opportunities', 'audience_overlap', 'cross_platform_strategy', 'quick_wins'],
            'estimated_seconds' => 25,
        ],

        // ── TikTok Tools ──────────────────────────────────────────────

        'tiktok-hashtag-strategy' => [
            'name' => 'Hashtag Strategy Builder',
            'description' => 'Build viral hashtag combos for maximum reach',
            'icon' => 'fa-light fa-hashtag',
            'emoji' => "#\xE2\x83\xA3",
            'color' => 'from-cyan-500 to-teal-600',
            'category' => 'optimization',
            'platform' => 'tiktok',
            'credits' => 2,
            'route' => 'app.ai-tools.enterprise.tiktok-hashtag-strategy',
            'is_existing' => false,
            'loading_steps' => ['Analyzing TikTok trends', 'Scanning hashtag performance', 'Building combinations', 'Calculating reach potential', 'Generating strategy'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 2, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 4, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'tiktok-seo-analyzer', 'reason' => 'Optimize your captions to match'],
                ['tool' => 'tiktok-viral-predictor', 'reason' => 'Test viral potential with these hashtags'],
            ],
            'inputs' => [
                ['key' => 'niche', 'type' => 'text', 'label' => 'Niche', 'required' => true],
                ['key' => 'content_type', 'type' => 'text', 'label' => 'Content Type', 'required' => false],
            ],
            'result_sections' => ['hashtag_score', 'primary_hashtags', 'hashtag_sets', 'trending_now', 'strategy_tips'],
            'estimated_seconds' => 15,
        ],
        'tiktok-seo-analyzer' => [
            'name' => 'TikTok SEO Analyzer',
            'description' => 'Optimize captions, keywords, and descriptions for TikTok search',
            'icon' => 'fa-light fa-magnifying-glass',
            'emoji' => "\xF0\x9F\x94\x8D",
            'color' => 'from-blue-500 to-indigo-600',
            'category' => 'optimization',
            'platform' => 'tiktok',
            'credits' => 2,
            'route' => 'app.ai-tools.enterprise.tiktok-seo-analyzer',
            'is_existing' => false,
            'loading_steps' => ['Analyzing profile SEO', 'Scanning caption patterns', 'Checking keyword density', 'Evaluating discoverability', 'Generating recommendations'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 2, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 4, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'tiktok-hashtag-strategy', 'reason' => 'Build hashtag sets for your keywords'],
                ['tool' => 'tiktok-hook-analyzer', 'reason' => 'Improve your hooks for retention'],
            ],
            'inputs' => [
                ['key' => 'profile', 'type' => 'text', 'label' => 'Profile (@username or URL)', 'required' => true],
                ['key' => 'caption', 'type' => 'textarea', 'label' => 'Caption to Analyze', 'required' => false],
            ],
            'result_sections' => ['seo_score', 'profile_analysis', 'caption_analysis', 'keyword_opportunities', 'content_pillars'],
            'estimated_seconds' => 15,
        ],
        'tiktok-posting-time' => [
            'name' => 'Posting Time Optimizer',
            'description' => 'Find optimal posting times based on your audience data',
            'icon' => 'fa-light fa-clock',
            'emoji' => "\xE2\x8F\xB0",
            'color' => 'from-amber-500 to-orange-600',
            'category' => 'analytics',
            'platform' => 'tiktok',
            'credits' => 2,
            'route' => 'app.ai-tools.enterprise.tiktok-posting-time',
            'is_existing' => false,
            'loading_steps' => ['Analyzing posting patterns', 'Mapping audience activity', 'Calculating engagement windows', 'Building weekly schedule', 'Generating recommendations'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 2, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 4, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'tiktok-viral-predictor', 'reason' => 'Predict which content to post at peak times'],
                ['tool' => 'tiktok-hook-analyzer', 'reason' => 'Make your peak-time posts count'],
            ],
            'inputs' => [
                ['key' => 'profile', 'type' => 'text', 'label' => 'Profile (@username)', 'required' => true],
                ['key' => 'timezone', 'type' => 'text', 'label' => 'Timezone', 'required' => false],
                ['key' => 'content_type', 'type' => 'text', 'label' => 'Content Type', 'required' => false],
            ],
            'result_sections' => ['timing_score', 'best_times', 'weekly_schedule', 'peak_hours', 'frequency_recommendation'],
            'estimated_seconds' => 15,
        ],
        'tiktok-hook-analyzer' => [
            'name' => 'Hook Analyzer',
            'description' => 'Analyze and improve your first 3 seconds for retention',
            'icon' => 'fa-light fa-bolt',
            'emoji' => "\xF0\x9F\xAA\x9D",
            'color' => 'from-yellow-500 to-amber-600',
            'category' => 'content',
            'platform' => 'tiktok',
            'credits' => 2,
            'route' => 'app.ai-tools.enterprise.tiktok-hook-analyzer',
            'is_existing' => false,
            'loading_steps' => ['Analyzing hook structure', 'Scoring attention capture', 'Checking retention patterns', 'Finding improvements', 'Generating alternatives'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 2, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 4, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'tiktok-viral-predictor', 'reason' => 'Test if your improved hook will go viral'],
                ['tool' => 'tiktok-seo-analyzer', 'reason' => 'Optimize the rest of your caption'],
            ],
            'inputs' => [
                ['key' => 'hook_text', 'type' => 'textarea', 'label' => 'Hook Text (first 3 seconds)', 'required' => true],
                ['key' => 'niche', 'type' => 'text', 'label' => 'Niche', 'required' => false],
            ],
            'result_sections' => ['hook_score', 'analysis', 'strengths', 'weaknesses', 'improved_versions', 'hook_formulas'],
            'estimated_seconds' => 15,
        ],
        'tiktok-sound-trends' => [
            'name' => 'Sound Trend Analyzer',
            'description' => 'Identify trending sounds and audio before they peak',
            'icon' => 'fa-light fa-music',
            'emoji' => "\xF0\x9F\x8E\xB5",
            'color' => 'from-pink-500 to-rose-600',
            'category' => 'analytics',
            'platform' => 'tiktok',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.tiktok-sound-trends',
            'is_existing' => false,
            'loading_steps' => ['Scanning sound trends', 'Analyzing viral audio patterns', 'Matching to your niche', 'Predicting upcoming trends', 'Building sound strategy'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'tiktok-viral-predictor', 'reason' => 'Predict viral potential with these sounds'],
                ['tool' => 'tiktok-posting-time', 'reason' => 'Time your sound-based posts perfectly'],
            ],
            'inputs' => [
                ['key' => 'niche', 'type' => 'text', 'label' => 'Niche', 'required' => true],
                ['key' => 'content_style', 'type' => 'text', 'label' => 'Content Style', 'required' => false],
            ],
            'result_sections' => ['sound_score', 'trending_sounds', 'emerging_sounds', 'evergreen_sounds', 'sound_strategy'],
            'estimated_seconds' => 18,
        ],
        'tiktok-viral-predictor' => [
            'name' => 'Viral Content Predictor',
            'description' => 'Predict viral potential of your content before posting',
            'icon' => 'fa-light fa-crystal-ball',
            'emoji' => "\xF0\x9F\x94\xAE",
            'color' => 'from-purple-500 to-violet-600',
            'category' => 'analytics',
            'platform' => 'tiktok',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.tiktok-viral-predictor',
            'is_existing' => false,
            'loading_steps' => ['Analyzing content concept', 'Checking trend alignment', 'Scoring viral signals', 'Modeling reach potential', 'Predicting performance', 'Generating recommendations'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'tiktok-hook-analyzer', 'reason' => 'Perfect your hook for maximum retention'],
                ['tool' => 'tiktok-hashtag-strategy', 'reason' => 'Build the right hashtag strategy'],
            ],
            'inputs' => [
                ['key' => 'content_description', 'type' => 'textarea', 'label' => 'Content Description', 'required' => true],
                ['key' => 'niche', 'type' => 'text', 'label' => 'Niche', 'required' => false],
                ['key' => 'follower_count', 'type' => 'text', 'label' => 'Follower Count', 'required' => false],
            ],
            'result_sections' => ['viral_score', 'prediction', 'viral_signals', 'strengths', 'optimization_suggestions'],
            'estimated_seconds' => 20,
        ],
        'tiktok-creator-fund' => [
            'name' => 'Creator Fund Calculator',
            'description' => 'Estimate earnings and optimize for creator fund payouts',
            'icon' => 'fa-light fa-sack-dollar',
            'emoji' => "\xF0\x9F\x92\xB0",
            'color' => 'from-green-500 to-emerald-600',
            'category' => 'monetization',
            'platform' => 'tiktok',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.tiktok-creator-fund',
            'is_existing' => false,
            'loading_steps' => ['Analyzing creator profile', 'Estimating view metrics', 'Calculating fund payouts', 'Comparing revenue streams', 'Building optimization plan'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'tiktok-brand-partnership', 'reason' => 'Earn more through brand deals'],
                ['tool' => 'tiktok-shop-optimizer', 'reason' => 'Add product revenue'],
            ],
            'inputs' => [
                ['key' => 'profile', 'type' => 'text', 'label' => 'Profile (@username)', 'required' => true],
                ['key' => 'avg_views', 'type' => 'text', 'label' => 'Avg Views', 'required' => false],
                ['key' => 'follower_count', 'type' => 'text', 'label' => 'Follower Count', 'required' => false],
            ],
            'result_sections' => ['fund_score', 'earnings_estimate', 'fund_breakdown', 'eligibility', 'revenue_comparison', 'optimization_tips'],
            'estimated_seconds' => 18,
        ],
        'tiktok-duet-stitch' => [
            'name' => 'Duet & Stitch Planner',
            'description' => 'Find high-engagement duet/stitch opportunities',
            'icon' => 'fa-light fa-people-arrows',
            'emoji' => "\xF0\x9F\xA4\x9D",
            'color' => 'from-indigo-500 to-purple-600',
            'category' => 'content',
            'platform' => 'tiktok',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.tiktok-duet-stitch',
            'is_existing' => false,
            'loading_steps' => ['Analyzing your content style', 'Finding duet opportunities', 'Identifying stitch targets', 'Scoring engagement potential', 'Building collaboration plan'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'tiktok-viral-predictor', 'reason' => 'Predict viral potential of your collab content'],
                ['tool' => 'tiktok-hook-analyzer', 'reason' => 'Perfect hooks for duet/stitch videos'],
            ],
            'inputs' => [
                ['key' => 'profile', 'type' => 'text', 'label' => 'Profile (@username)', 'required' => true],
                ['key' => 'niche', 'type' => 'text', 'label' => 'Niche', 'required' => false],
                ['key' => 'goal', 'type' => 'text', 'label' => 'Goal', 'required' => false],
            ],
            'result_sections' => ['collaboration_score', 'duet_opportunities', 'stitch_opportunities', 'trending_duets', 'strategy'],
            'estimated_seconds' => 18,
        ],
        'tiktok-brand-partnership' => [
            'name' => 'Brand Partnership Finder',
            'description' => 'Match with brands looking for TikTok creators',
            'icon' => 'fa-light fa-handshake',
            'emoji' => "\xF0\x9F\xA4\x9D",
            'color' => 'from-rose-500 to-pink-600',
            'category' => 'monetization',
            'platform' => 'tiktok',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.tiktok-brand-partnership',
            'is_existing' => false,
            'loading_steps' => ['Analyzing creator profile', 'Scanning brand partnerships', 'Matching brand categories', 'Generating pitch templates', 'Building outreach strategy'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'tiktok-creator-fund', 'reason' => 'Compare brand deals vs fund earnings'],
                ['tool' => 'tiktok-shop-optimizer', 'reason' => 'Add TikTok Shop to your brand deals'],
            ],
            'inputs' => [
                ['key' => 'profile', 'type' => 'text', 'label' => 'Profile (@username)', 'required' => true],
                ['key' => 'niche', 'type' => 'text', 'label' => 'Niche', 'required' => false],
                ['key' => 'follower_count', 'type' => 'text', 'label' => 'Follower Count', 'required' => false],
            ],
            'result_sections' => ['partnership_score', 'brand_matches', 'pitch_templates', 'rate_card', 'outreach_strategy'],
            'estimated_seconds' => 20,
        ],
        'tiktok-shop-optimizer' => [
            'name' => 'TikTok Shop Optimizer',
            'description' => 'Optimize product listings and affiliate strategies for TikTok Shop',
            'icon' => 'fa-light fa-shop',
            'emoji' => "\xF0\x9F\x9B\x92",
            'color' => 'from-orange-500 to-red-600',
            'category' => 'monetization',
            'platform' => 'tiktok',
            'credits' => 3,
            'route' => 'app.ai-tools.enterprise.tiktok-shop-optimizer',
            'is_existing' => false,
            'loading_steps' => ['Analyzing shop profile', 'Scanning product trends', 'Evaluating pricing strategy', 'Finding affiliate products', 'Building optimization plan', 'Generating content ideas'],
            'tiers' => [
                'quick'    => ['credits' => 1, 'label' => 'Quick Scan',    'max_tokens' => 2000, 'icon' => 'fa-light fa-bolt'],
                'standard' => ['credits' => 3, 'label' => 'Full Analysis', 'max_tokens' => 5000, 'icon' => 'fa-light fa-chart-bar'],
                'deep'     => ['credits' => 5, 'label' => 'Deep Dive',     'max_tokens' => 8000, 'icon' => 'fa-light fa-microscope'],
            ],
            'next_steps' => [
                ['tool' => 'tiktok-brand-partnership', 'reason' => 'Partner with brands in your shop niche'],
                ['tool' => 'tiktok-viral-predictor', 'reason' => 'Create viral product showcase content'],
            ],
            'inputs' => [
                ['key' => 'profile', 'type' => 'text', 'label' => 'Profile (@username or shop URL)', 'required' => true],
                ['key' => 'product_type', 'type' => 'text', 'label' => 'Product Type', 'required' => false],
                ['key' => 'price_range', 'type' => 'text', 'label' => 'Price Range', 'required' => false],
            ],
            'result_sections' => ['shop_score', 'shop_overview', 'product_recommendations', 'affiliate_opportunities', 'content_strategy'],
            'estimated_seconds' => 22,
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
