<?php

namespace Modules\AppAITools\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppAIToolsController extends Controller
{
    /**
     * Main tools hub page.
     */
    public function index()
    {
        return view('appaitools::index');
    }

    /**
     * Individual tool page.
     */
    public function tool(Request $request, string $tool = '')
    {
        $tool = $request->route()->defaults['tool'] ?? $tool;

        $toolMap = [
            'video-optimizer' => ['component' => 'video-optimizer', 'title' => 'Video Optimizer'],
            'competitor-analysis' => ['component' => 'competitor-analysis', 'title' => 'Competitor Analysis'],
            'trend-predictor' => ['component' => 'trend-predictor', 'title' => 'Trend Predictor'],
            'ai-thumbnails' => ['component' => 'ai-thumbnails', 'title' => 'AI Thumbnails'],
            'channel-audit' => ['component' => 'channel-audit', 'title' => 'Channel Audit Pro'],
            'more-tools' => ['component' => 'more-tools', 'title' => 'More AI Tools'],
        ];

        if (!isset($toolMap[$tool])) {
            abort(404);
        }

        return view('appaitools::tool', [
            'component' => $toolMap[$tool]['component'],
            'title' => $toolMap[$tool]['title'],
        ]);
    }

    /**
     * Sub-tool page (under More Tools).
     */
    public function subTool(Request $request, string $tool = '')
    {
        $tool = $request->route()->defaults['tool'] ?? $tool;

        $subToolMap = [
            'script-studio' => ['component' => 'script-studio', 'title' => 'Script Studio'],
            'viral-hook-lab' => ['component' => 'viral-hook-lab', 'title' => 'Viral Hook Lab'],
            'content-multiplier' => ['component' => 'content-multiplier', 'title' => 'Content Multiplier'],
            'thumbnail-arena' => ['component' => 'thumbnail-arena', 'title' => 'Thumbnail Arena'],
        ];

        if (!isset($subToolMap[$tool])) {
            abort(404);
        }

        return view('appaitools::tool', [
            'component' => $subToolMap[$tool]['component'],
            'title' => $subToolMap[$tool]['title'],
        ]);
    }

    /**
     * Enterprise Suite dashboard.
     */
    public function enterpriseSuite()
    {
        return view('appaitools::tool', [
            'component' => 'enterprise-dashboard',
            'title' => 'Enterprise Suite',
        ]);
    }

    /**
     * Enterprise Suite individual tool page.
     */
    public function enterpriseTool(Request $request, string $tool = '')
    {
        $tool = $request->route()->defaults['tool'] ?? $tool;

        $enterpriseToolMap = [
            'placement-finder'          => ['component' => 'enterprise.placement-finder',          'title' => 'Placement Finder'],
            'monetization-analyzer'     => ['component' => 'enterprise.monetization-analyzer',     'title' => 'Monetization Analyzer'],
            'sponsorship-calculator'    => ['component' => 'enterprise.sponsorship-calculator',    'title' => 'Sponsorship Rate Calculator'],
            'revenue-diversification'   => ['component' => 'enterprise.revenue-diversification',   'title' => 'Revenue Diversification'],
            'cpm-booster'               => ['component' => 'enterprise.cpm-booster',               'title' => 'CPM Booster Strategist'],
            'audience-profiler'         => ['component' => 'enterprise.audience-profiler',         'title' => 'Audience Monetization Profiler'],
            'digital-product-architect' => ['component' => 'enterprise.digital-product-architect', 'title' => 'Digital Product Architect'],
            'affiliate-finder'          => ['component' => 'enterprise.affiliate-finder',          'title' => 'Affiliate Goldmine Finder'],
            'multi-income-converter'    => ['component' => 'enterprise.multi-income-converter',    'title' => 'Multi-Income Converter'],
            'brand-deal-matchmaker'     => ['component' => 'enterprise.brand-deal-matchmaker',     'title' => 'Brand Deal Matchmaker'],
            'licensing-scout'           => ['component' => 'enterprise.licensing-scout',           'title' => 'Licensing & Syndication Scout'],
            'revenue-automation'        => ['component' => 'enterprise.revenue-automation',        'title' => 'Revenue Automation Pipeline'],
            // TikTok
            'tiktok-hashtag-strategy'   => ['component' => 'enterprise.tiktok-hashtag-strategy',   'title' => 'Hashtag Strategy Builder'],
            'tiktok-seo-analyzer'       => ['component' => 'enterprise.tiktok-seo-analyzer',       'title' => 'TikTok SEO Analyzer'],
            'tiktok-posting-time'       => ['component' => 'enterprise.tiktok-posting-time',       'title' => 'Posting Time Optimizer'],
            'tiktok-hook-analyzer'      => ['component' => 'enterprise.tiktok-hook-analyzer',      'title' => 'Hook Analyzer'],
            'tiktok-sound-trends'       => ['component' => 'enterprise.tiktok-sound-trends',       'title' => 'Sound Trend Analyzer'],
            'tiktok-viral-predictor'    => ['component' => 'enterprise.tiktok-viral-predictor',    'title' => 'Viral Content Predictor'],
            'tiktok-creator-fund'       => ['component' => 'enterprise.tiktok-creator-fund',       'title' => 'Creator Fund Calculator'],
            'tiktok-duet-stitch'        => ['component' => 'enterprise.tiktok-duet-stitch',        'title' => 'Duet & Stitch Planner'],
            'tiktok-brand-partnership'  => ['component' => 'enterprise.tiktok-brand-partnership',  'title' => 'Brand Partnership Finder'],
            'tiktok-shop-optimizer'     => ['component' => 'enterprise.tiktok-shop-optimizer',     'title' => 'TikTok Shop Optimizer'],
        ];

        if (!isset($enterpriseToolMap[$tool])) {
            abort(404);
        }

        return view('appaitools::tool', [
            'component' => $enterpriseToolMap[$tool]['component'],
            'title' => $enterpriseToolMap[$tool]['title'],
        ]);
    }
}
