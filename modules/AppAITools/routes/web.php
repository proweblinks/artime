<?php

use Illuminate\Support\Facades\Route;
use Modules\AppAITools\Http\Controllers\AppAIToolsController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::group(['prefix' => 'app/ai-tools'], function () {
        // Main tools hub
        Route::get('/', [AppAIToolsController::class, 'index'])->name('app.ai-tools.index');

        // Individual tools
        Route::get('/video-optimizer', [AppAIToolsController::class, 'tool'])->name('app.ai-tools.video-optimizer')->defaults('tool', 'video-optimizer');
        Route::get('/competitor-analysis', [AppAIToolsController::class, 'tool'])->name('app.ai-tools.competitor-analysis')->defaults('tool', 'competitor-analysis');
        Route::get('/trend-predictor', [AppAIToolsController::class, 'tool'])->name('app.ai-tools.trend-predictor')->defaults('tool', 'trend-predictor');
        Route::get('/ai-thumbnails', [AppAIToolsController::class, 'tool'])->name('app.ai-tools.ai-thumbnails')->defaults('tool', 'ai-thumbnails');
        Route::get('/channel-audit', [AppAIToolsController::class, 'tool'])->name('app.ai-tools.channel-audit')->defaults('tool', 'channel-audit');

        // More tools sub-hub
        Route::get('/more-tools', [AppAIToolsController::class, 'tool'])->name('app.ai-tools.more-tools')->defaults('tool', 'more-tools');
        Route::get('/more-tools/script-studio', [AppAIToolsController::class, 'subTool'])->name('app.ai-tools.script-studio')->defaults('tool', 'script-studio');
        Route::get('/more-tools/viral-hooks', [AppAIToolsController::class, 'subTool'])->name('app.ai-tools.viral-hooks')->defaults('tool', 'viral-hook-lab');
        Route::get('/more-tools/content-multiplier', [AppAIToolsController::class, 'subTool'])->name('app.ai-tools.content-multiplier')->defaults('tool', 'content-multiplier');
        Route::get('/more-tools/thumbnail-arena', [AppAIToolsController::class, 'subTool'])->name('app.ai-tools.thumbnail-arena')->defaults('tool', 'thumbnail-arena');

        // Enterprise Suite
        Route::get('/enterprise-suite', [AppAIToolsController::class, 'enterpriseSuite'])->name('app.ai-tools.enterprise-suite');
        Route::get('/enterprise-suite/placement-finder', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.placement-finder')->defaults('tool', 'placement-finder');
        Route::get('/enterprise-suite/monetization-analyzer', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.monetization-analyzer')->defaults('tool', 'monetization-analyzer');
        Route::get('/enterprise-suite/sponsorship-calculator', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.sponsorship-calculator')->defaults('tool', 'sponsorship-calculator');
        Route::get('/enterprise-suite/revenue-diversification', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.revenue-diversification')->defaults('tool', 'revenue-diversification');
        Route::get('/enterprise-suite/cpm-booster', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.cpm-booster')->defaults('tool', 'cpm-booster');
        Route::get('/enterprise-suite/audience-profiler', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.audience-profiler')->defaults('tool', 'audience-profiler');
        Route::get('/enterprise-suite/digital-product-architect', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.digital-product-architect')->defaults('tool', 'digital-product-architect');
        Route::get('/enterprise-suite/affiliate-finder', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.affiliate-finder')->defaults('tool', 'affiliate-finder');
        Route::get('/enterprise-suite/multi-income-converter', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.multi-income-converter')->defaults('tool', 'multi-income-converter');
        Route::get('/enterprise-suite/brand-deal-matchmaker', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.brand-deal-matchmaker')->defaults('tool', 'brand-deal-matchmaker');
        Route::get('/enterprise-suite/licensing-scout', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.licensing-scout')->defaults('tool', 'licensing-scout');
        Route::get('/enterprise-suite/revenue-automation', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.revenue-automation')->defaults('tool', 'revenue-automation');

        // Cross-Platform YouTube↔TikTok Tools
        Route::get('/enterprise-suite/tiktok-yt-converter', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.tiktok-yt-converter')->defaults('tool', 'tiktok-yt-converter');
        Route::get('/enterprise-suite/tiktok-yt-arbitrage', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.tiktok-yt-arbitrage')->defaults('tool', 'tiktok-yt-arbitrage');

        // TikTok Enterprise Tools
        Route::get('/enterprise-suite/tiktok-hashtag-strategy', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.tiktok-hashtag-strategy')->defaults('tool', 'tiktok-hashtag-strategy');
        Route::get('/enterprise-suite/tiktok-seo-analyzer', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.tiktok-seo-analyzer')->defaults('tool', 'tiktok-seo-analyzer');
        Route::get('/enterprise-suite/tiktok-posting-time', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.tiktok-posting-time')->defaults('tool', 'tiktok-posting-time');
        Route::get('/enterprise-suite/tiktok-hook-analyzer', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.tiktok-hook-analyzer')->defaults('tool', 'tiktok-hook-analyzer');
        Route::get('/enterprise-suite/tiktok-sound-trends', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.tiktok-sound-trends')->defaults('tool', 'tiktok-sound-trends');
        Route::get('/enterprise-suite/tiktok-viral-predictor', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.tiktok-viral-predictor')->defaults('tool', 'tiktok-viral-predictor');
        Route::get('/enterprise-suite/tiktok-creator-fund', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.tiktok-creator-fund')->defaults('tool', 'tiktok-creator-fund');
        Route::get('/enterprise-suite/tiktok-duet-stitch', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.tiktok-duet-stitch')->defaults('tool', 'tiktok-duet-stitch');
        Route::get('/enterprise-suite/tiktok-brand-partnership', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.tiktok-brand-partnership')->defaults('tool', 'tiktok-brand-partnership');
        Route::get('/enterprise-suite/tiktok-shop-optimizer', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.tiktok-shop-optimizer')->defaults('tool', 'tiktok-shop-optimizer');

        // Cross-Platform YouTube↔Instagram Tools
        Route::get('/enterprise-suite/ig-yt-reels-converter', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.ig-yt-reels-converter')->defaults('tool', 'ig-yt-reels-converter');
        Route::get('/enterprise-suite/ig-yt-arbitrage', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.ig-yt-arbitrage')->defaults('tool', 'ig-yt-arbitrage');

        // Instagram Enterprise Tools
        Route::get('/enterprise-suite/ig-reels-monetization', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.ig-reels-monetization')->defaults('tool', 'ig-reels-monetization');
        Route::get('/enterprise-suite/ig-seo-optimizer', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.ig-seo-optimizer')->defaults('tool', 'ig-seo-optimizer');
        Route::get('/enterprise-suite/ig-story-planner', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.ig-story-planner')->defaults('tool', 'ig-story-planner');
        Route::get('/enterprise-suite/ig-carousel-builder', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.ig-carousel-builder')->defaults('tool', 'ig-carousel-builder');
        Route::get('/enterprise-suite/ig-collab-matcher', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.ig-collab-matcher')->defaults('tool', 'ig-collab-matcher');
        Route::get('/enterprise-suite/ig-link-bio', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.ig-link-bio')->defaults('tool', 'ig-link-bio');
        Route::get('/enterprise-suite/ig-dm-automation', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.ig-dm-automation')->defaults('tool', 'ig-dm-automation');
        Route::get('/enterprise-suite/ig-hashtag-tracker', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.ig-hashtag-tracker')->defaults('tool', 'ig-hashtag-tracker');
        Route::get('/enterprise-suite/ig-aesthetic-analyzer', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.ig-aesthetic-analyzer')->defaults('tool', 'ig-aesthetic-analyzer');
        Route::get('/enterprise-suite/ig-shopping-optimizer', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.ig-shopping-optimizer')->defaults('tool', 'ig-shopping-optimizer');

        // Cross-Platform YouTube↔Facebook Tools
        Route::get('/enterprise-suite/fb-yt-reels-converter', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.fb-yt-reels-converter')->defaults('tool', 'fb-yt-reels-converter');
        Route::get('/enterprise-suite/fb-yt-arbitrage', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.fb-yt-arbitrage')->defaults('tool', 'fb-yt-arbitrage');

        // Facebook Enterprise Tools
        Route::get('/enterprise-suite/fb-reels-bonus', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.fb-reels-bonus')->defaults('tool', 'fb-reels-bonus');
        Route::get('/enterprise-suite/fb-group-monetization', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.fb-group-monetization')->defaults('tool', 'fb-group-monetization');
        Route::get('/enterprise-suite/fb-ad-breaks', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.fb-ad-breaks')->defaults('tool', 'fb-ad-breaks');
        Route::get('/enterprise-suite/fb-page-growth', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.fb-page-growth')->defaults('tool', 'fb-page-growth');
        Route::get('/enterprise-suite/fb-shop-optimizer', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.fb-shop-optimizer')->defaults('tool', 'fb-shop-optimizer');
        Route::get('/enterprise-suite/fb-content-recycler', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.fb-content-recycler')->defaults('tool', 'fb-content-recycler');
        Route::get('/enterprise-suite/fb-live-monetization', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.fb-live-monetization')->defaults('tool', 'fb-live-monetization');
        Route::get('/enterprise-suite/fb-engagement-optimizer', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.fb-engagement-optimizer')->defaults('tool', 'fb-engagement-optimizer');
        Route::get('/enterprise-suite/fb-audience-insights', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.fb-audience-insights')->defaults('tool', 'fb-audience-insights');
        Route::get('/enterprise-suite/fb-posting-scheduler', [AppAIToolsController::class, 'enterpriseTool'])->name('app.ai-tools.enterprise.fb-posting-scheduler')->defaults('tool', 'fb-posting-scheduler');
    });
});
