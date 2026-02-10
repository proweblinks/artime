<?php

namespace Modules\AppAITools\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;
use Modules\AppAITools\Models\AiToolHistory;

class EnterpriseToolService
{
    /**
     * Analyze a YouTube channel for Google Ads placement opportunities.
     */
    public function analyzePlacement(string $channelUrl, string $niche = ''): array
    {
        $nicheLine = $niche ? "\nThe advertiser is specifically targeting the \"{$niche}\" niche." : '';

        $prompt = 'You are a senior Google Ads strategist specializing in YouTube Placement Targeting campaigns. '
            . 'Your job is to analyze a YouTube channel and find REAL YouTube channels that would be ideal placements '
            . "for a Google Ads campaign targeting a similar audience.\n\n"
            . 'CONTEXT: In Google Ads, "Placement Targeting" lets advertisers choose specific YouTube channels where '
            . 'their ads will appear. The goal is to find channels whose viewers would be interested in the analyzed '
            . "channel's content — so ads shown on those channels reach a relevant, engaged audience.\n\n"
            . "CHANNEL TO ANALYZE: {$channelUrl}{$nicheLine}\n\n"
            . "CRITICAL RULES:\n"
            . "1. Only recommend YouTube channels you are CONFIDENT actually exist. Use channels you know from your training data.\n"
            . "2. Every channel MUST include its real YouTube handle (e.g. @mkbhd, @veritasium). If you are not sure of the exact handle, do NOT include that channel.\n"
            . "3. Include a MIX of channel sizes: some large (1M+), some medium (100K-1M), and some smaller but highly relevant (10K-100K). Smaller channels often have higher engagement rates and lower CPMs.\n"
            . "4. Generate exactly 15 placement channels.\n"
            . "5. For each channel, explain specifically WHY it is a good placement match — what audience overlap exists.\n"
            . "6. CPM estimates should reflect realistic YouTube Ads CPM ranges for the niche (typically 2-15 USD for most niches, 15-50 USD for finance/insurance/legal).\n\n"
            . "Respond with ONLY valid JSON in this exact structure:\n\n"
            . "{\n"
            . '  "channel_info": {' . "\n"
            . '    "name": "Channel Name",' . "\n"
            . '    "handle": "@handle",' . "\n"
            . '    "niche": "Primary niche category",' . "\n"
            . '    "sub_niche": "More specific sub-category",' . "\n"
            . '    "estimated_subscribers": "e.g. 1.2M",' . "\n"
            . '    "content_style": "e.g. Long-form reviews, Short tutorials, Vlogs",' . "\n"
            . '    "upload_frequency": "e.g. 2-3 videos/week",' . "\n"
            . '    "audience_type": "Brief description of who watches this channel"' . "\n"
            . "  },\n"
            . '  "placement_score": 85,' . "\n"
            . '  "niche_insights": {' . "\n"
            . '    "niche_cpm_range": "e.g. 4.00 - 10.00 USD",' . "\n"
            . '    "best_ad_formats": ["Skippable in-stream", "Discovery ads"],' . "\n"
            . '    "peak_months": ["November", "December"],' . "\n"
            . '    "audience_demographics": "e.g. Males 25-44, tech-savvy, mid-to-high income",' . "\n"
            . '    "competition_level": "Low|Medium|High"' . "\n"
            . "  },\n"
            . '  "placements": [' . "\n"
            . "    {\n"
            . '      "channel_name": "Actual Channel Name",' . "\n"
            . '      "handle": "@actualhandle",' . "\n"
            . '      "channel_url": "https://youtube.com/@actualhandle",' . "\n"
            . '      "subscribers": "e.g. 2.5M",' . "\n"
            . '      "relevance_score": 92,' . "\n"
            . '      "estimated_cpm": "e.g. 4.50 - 8.00 USD",' . "\n"
            . '      "content_type": "e.g. Tech reviews & unboxings",' . "\n"
            . '      "audience_match": "Specific reason why this channels audience overlaps with the analyzed channel",' . "\n"
            . '      "recommended_ad_format": "Skippable in-stream|Non-skippable|Bumper|Discovery",' . "\n"
            . '      "tier": "large|medium|small"' . "\n"
            . "    }\n"
            . "  ],\n"
            . '  "campaign_strategy": {' . "\n"
            . '    "recommended_daily_budget": "e.g. 20 - 50 USD",' . "\n"
            . '    "expected_cpm_range": "e.g. 4.00 - 10.00 USD",' . "\n"
            . '    "expected_ctr": "e.g. 0.5% - 1.2%",' . "\n"
            . '    "recommended_bid_strategy": "e.g. Target CPM or Maximize conversions",' . "\n"
            . '    "ad_group_structure": [' . "\n"
            . "      {\n"
            . '        "group_name": "e.g. High-Relevance Tech Channels",' . "\n"
            . '        "channels": ["@handle1", "@handle2", "@handle3"],' . "\n"
            . '        "rationale": "Why these channels are grouped together"' . "\n"
            . "      }\n"
            . "    ]\n"
            . "  },\n"
            . '  "google_ads_keywords": ["keyword1", "keyword2", "keyword3"],' . "\n"
            . '  "tips": [' . "\n"
            . '    "Specific, actionable tip about running placement campaigns in this niche"' . "\n"
            . "  ]\n"
            . "}";

        return $this->executeAnalysis('placement_finder', $channelUrl, $prompt);
    }

    /**
     * Analyze channel monetization and estimate earnings.
     */
    public function analyzeMonetization(string $channelUrl): array
    {
        $prompt = "You are an expert YouTube monetization analyst. Analyze this channel and provide detailed revenue estimates and optimization strategies.\n\n"
            . "Channel URL: {$channelUrl}\n\n"
            . "Provide analysis as JSON:\n"
            . "{\n"
            . "  \"channel_overview\": { \"name\": \"\", \"niche\": \"\", \"estimated_subscribers\": \"\", \"monthly_views\": \"\" },\n"
            . "  \"monetization_score\": 0-100,\n"
            . "  \"revenue_breakdown\": [\n"
            . "    { \"stream\": \"AdSense\", \"monthly_estimate\": \"$X\", \"potential\": \"high|medium|low\", \"status\": \"active|underutilized|inactive\" },\n"
            . "    { \"stream\": \"Sponsorships\", \"monthly_estimate\": \"$X\", \"potential\": \"high|medium|low\", \"status\": \"active|underutilized|inactive\" },\n"
            . "    { \"stream\": \"Memberships\", \"monthly_estimate\": \"$X\", \"potential\": \"high|medium|low\", \"status\": \"active|underutilized|inactive\" },\n"
            . "    { \"stream\": \"Merchandise\", \"monthly_estimate\": \"$X\", \"potential\": \"high|medium|low\", \"status\": \"active|underutilized|inactive\" },\n"
            . "    { \"stream\": \"Super Chats\", \"monthly_estimate\": \"$X\", \"potential\": \"high|medium|low\", \"status\": \"active|underutilized|inactive\" }\n"
            . "  ],\n"
            . "  \"total_monthly_estimate\": \"\",\n"
            . "  \"growth_opportunities\": [\n"
            . "    { \"opportunity\": \"\", \"potential_revenue\": \"\", \"difficulty\": \"easy|medium|hard\", \"priority\": \"high|medium|low\" }\n"
            . "  ],\n"
            . "  \"optimization_tips\": [\"tip1\", \"tip2\", \"tip3\", \"tip4\", \"tip5\"]\n"
            . "}\n\nRespond with ONLY valid JSON.";

        return $this->executeAnalysis('monetization_analyzer', $channelUrl, $prompt);
    }

    /**
     * Calculate sponsorship rates for a channel.
     */
    public function calculateSponsorship(string $channelUrl): array
    {
        $prompt = "You are an expert YouTube sponsorship rate consultant. Calculate fair sponsorship rates for this channel based on industry standards.\n\n"
            . "Channel URL: {$channelUrl}\n\n"
            . "Provide analysis as JSON:\n"
            . "{\n"
            . "  \"channel_profile\": { \"name\": \"\", \"niche\": \"\", \"estimated_subscribers\": \"\", \"avg_views\": \"\", \"engagement_rate\": \"\" },\n"
            . "  \"sponsorship_score\": 0-100,\n"
            . "  \"rate_tiers\": {\n"
            . "    \"dedicated_video\": { \"min\": \"\", \"max\": \"\", \"description\": \"\" },\n"
            . "    \"integrated_mention\": { \"min\": \"\", \"max\": \"\", \"description\": \"\" },\n"
            . "    \"shorts_mention\": { \"min\": \"\", \"max\": \"\", \"description\": \"\" },\n"
            . "    \"community_post\": { \"min\": \"\", \"max\": \"\", \"description\": \"\" }\n"
            . "  },\n"
            . "  \"niche_cpm_benchmark\": \"\",\n"
            . "  \"negotiation_tips\": [\"tip1\", \"tip2\", \"tip3\"],\n"
            . "  \"media_kit_suggestions\": [\"suggestion1\", \"suggestion2\", \"suggestion3\"],\n"
            . "  \"comparable_creators\": [\n"
            . "    { \"name\": \"\", \"subscribers\": \"\", \"estimated_rate\": \"\" }\n"
            . "  ]\n"
            . "}\n\nRespond with ONLY valid JSON.";

        return $this->executeAnalysis('sponsorship_calculator', $channelUrl, $prompt);
    }

    /**
     * Analyze revenue diversification opportunities.
     */
    public function analyzeRevenueDiversification(string $channelUrl): array
    {
        $prompt = "You are a YouTube revenue diversification strategist. Identify untapped income streams for this channel.\n\n"
            . "Channel URL: {$channelUrl}\n\n"
            . "Provide analysis as JSON:\n"
            . "{\n"
            . "  \"channel_profile\": { \"name\": \"\", \"niche\": \"\", \"current_streams\": [] },\n"
            . "  \"diversification_score\": 0-100,\n"
            . "  \"current_revenue_audit\": [\n"
            . "    { \"stream\": \"\", \"status\": \"active|inactive|underutilized\", \"potential\": \"high|medium|low\" }\n"
            . "  ],\n"
            . "  \"new_opportunities\": [\n"
            . "    { \"stream\": \"\", \"description\": \"\", \"monthly_potential\": \"\", \"setup_difficulty\": \"easy|medium|hard\", \"time_to_revenue\": \"\", \"action_steps\": [\"step1\", \"step2\"] }\n"
            . "  ],\n"
            . "  \"priority_roadmap\": [\n"
            . "    { \"month\": \"Month 1\", \"action\": \"\", \"expected_result\": \"\" }\n"
            . "  ],\n"
            . "  \"total_potential_increase\": \"\"\n"
            . "}\n\nGenerate 5-7 new opportunities. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('revenue_diversification', $channelUrl, $prompt);
    }

    /**
     * Strategize higher CPM content.
     */
    public function analyzeCpmBoost(string $channelUrl, string $niche = ''): array
    {
        $prompt = "You are a YouTube CPM optimization expert. Analyze this channel and provide strategies to attract higher-paying advertisers.\n\n"
            . "Channel URL: {$channelUrl}\n"
            . ($niche ? "Niche: {$niche}\n" : '')
            . "\nProvide analysis as JSON:\n"
            . "{\n"
            . "  \"current_analysis\": { \"niche\": \"\", \"estimated_cpm\": \"\", \"content_type\": \"\" },\n"
            . "  \"cpm_score\": 0-100,\n"
            . "  \"high_cpm_keywords\": [\n"
            . "    { \"keyword\": \"\", \"estimated_cpm\": \"\", \"search_volume\": \"\", \"competition\": \"low|medium|high\" }\n"
            . "  ],\n"
            . "  \"video_ideas\": [\n"
            . "    { \"title\": \"\", \"target_cpm\": \"\", \"reasoning\": \"\", \"keywords\": [] }\n"
            . "  ],\n"
            . "  \"content_calendar\": [\n"
            . "    { \"week\": \"Week 1\", \"topic\": \"\", \"target_cpm\": \"\", \"format\": \"\" }\n"
            . "  ],\n"
            . "  \"optimization_tips\": [\"tip1\", \"tip2\", \"tip3\", \"tip4\"]\n"
            . "}\n\nGenerate 10 high-CPM keywords and 5 video ideas. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('cpm_booster', $channelUrl, $prompt);
    }

    /**
     * Profile audience monetization potential.
     */
    public function profileAudience(string $channelUrl): array
    {
        $prompt = "You are an audience monetization analyst. Provide deep analysis of this channel's audience spending behavior and monetization potential.\n\n"
            . "Channel URL: {$channelUrl}\n\n"
            . "Provide analysis as JSON:\n"
            . "{\n"
            . "  \"channel_overview\": { \"name\": \"\", \"niche\": \"\", \"estimated_audience_size\": \"\" },\n"
            . "  \"audience_score\": 0-100,\n"
            . "  \"demographic_segments\": [\n"
            . "    { \"segment\": \"\", \"percentage\": \"\", \"spending_power\": \"high|medium|low\", \"interests\": [], \"purchase_triggers\": [] }\n"
            . "  ],\n"
            . "  \"spending_analysis\": {\n"
            . "    \"avg_disposable_income\": \"\",\n"
            . "    \"top_purchase_categories\": [],\n"
            . "    \"price_sensitivity\": \"low|medium|high\",\n"
            . "    \"impulse_buy_likelihood\": \"low|medium|high\"\n"
            . "  },\n"
            . "  \"product_recommendations\": [\n"
            . "    { \"product_type\": \"\", \"price_range\": \"\", \"conversion_potential\": \"high|medium|low\", \"reasoning\": \"\" }\n"
            . "  ],\n"
            . "  \"monetization_strategies\": [\"strategy1\", \"strategy2\", \"strategy3\"]\n"
            . "}\n\nGenerate 3-4 segments and 5 product recommendations. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('audience_profiler', $channelUrl, $prompt);
    }

    /**
     * Design digital products for a channel's audience.
     */
    public function designDigitalProducts(string $channelUrl, string $expertise = ''): array
    {
        $prompt = "You are a digital product strategist. Design and price digital products tailored to this channel's audience.\n\n"
            . "Channel URL: {$channelUrl}\n"
            . ($expertise ? "Creator expertise: {$expertise}\n" : '')
            . "\nProvide analysis as JSON:\n"
            . "{\n"
            . "  \"creator_profile\": { \"niche\": \"\", \"expertise_areas\": [], \"audience_size\": \"\" },\n"
            . "  \"product_readiness_score\": 0-100,\n"
            . "  \"product_ideas\": [\n"
            . "    { \"name\": \"\", \"type\": \"course|ebook|template|membership|coaching|tool\", \"description\": \"\", \"suggested_price\": \"\", \"estimated_monthly_revenue\": \"\", \"development_time\": \"\", \"difficulty\": \"easy|medium|hard\", \"content_outline\": [] }\n"
            . "  ],\n"
            . "  \"launch_plan\": [\n"
            . "    { \"phase\": \"\", \"duration\": \"\", \"actions\": [], \"goal\": \"\" }\n"
            . "  ],\n"
            . "  \"pricing_strategy\": { \"anchor_price\": \"\", \"discount_strategy\": \"\", \"bundle_ideas\": [] },\n"
            . "  \"platform_recommendations\": [\n"
            . "    { \"platform\": \"\", \"best_for\": \"\", \"fee_structure\": \"\" }\n"
            . "  ]\n"
            . "}\n\nGenerate 4-5 product ideas. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('digital_product_architect', $channelUrl, $prompt);
    }

    /**
     * Find affiliate opportunities.
     */
    public function findAffiliates(string $channelUrl, string $niche = ''): array
    {
        $prompt = "You are an affiliate marketing expert for YouTube creators. Find the best high-paying affiliate opportunities for this channel.\n\n"
            . "Channel URL: {$channelUrl}\n"
            . ($niche ? "Niche: {$niche}\n" : '')
            . "\nProvide analysis as JSON:\n"
            . "{\n"
            . "  \"channel_analysis\": { \"niche\": \"\", \"content_type\": \"\", \"audience_match\": \"\" },\n"
            . "  \"affiliate_score\": 0-100,\n"
            . "  \"programs\": [\n"
            . "    { \"program\": \"\", \"network\": \"\", \"commission_rate\": \"\", \"cookie_duration\": \"\", \"avg_payout\": \"\", \"relevance_score\": 0-100, \"signup_url_hint\": \"\", \"integration_ideas\": [] }\n"
            . "  ],\n"
            . "  \"script_templates\": [\n"
            . "    { \"type\": \"dedicated|mention|review\", \"template\": \"\" }\n"
            . "  ],\n"
            . "  \"estimated_monthly_income\": \"\",\n"
            . "  \"tips\": [\"tip1\", \"tip2\", \"tip3\"]\n"
            . "}\n\nFind 8-10 affiliate programs. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('affiliate_finder', $channelUrl, $prompt);
    }

    /**
     * Convert a video into multiple income streams.
     */
    public function convertToMultiIncome(string $videoUrl): array
    {
        $prompt = "You are a content monetization strategist. Analyze this video and create a strategy to turn it into multiple revenue streams across platforms.\n\n"
            . "Video URL: {$videoUrl}\n\n"
            . "Provide analysis as JSON:\n"
            . "{\n"
            . "  \"video_analysis\": { \"title\": \"\", \"topic\": \"\", \"key_points\": [], \"content_type\": \"\" },\n"
            . "  \"multi_income_score\": 0-100,\n"
            . "  \"income_streams\": [\n"
            . "    { \"platform\": \"\", \"content_type\": \"\", \"description\": \"\", \"estimated_revenue\": \"\", \"effort_level\": \"low|medium|high\", \"time_to_create\": \"\", \"content_draft\": \"\" }\n"
            . "  ],\n"
            . "  \"repurposing_plan\": [\n"
            . "    { \"day\": \"Day 1\", \"action\": \"\", \"platform\": \"\", \"format\": \"\" }\n"
            . "  ],\n"
            . "  \"total_potential_revenue\": \"\",\n"
            . "  \"automation_suggestions\": [\"suggestion1\", \"suggestion2\"]\n"
            . "}\n\nGenerate 6-8 income streams. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('multi_income_converter', $videoUrl, $prompt);
    }

    /**
     * Match channel with brand deals.
     */
    public function matchBrandDeals(string $channelUrl): array
    {
        $prompt = "You are a brand partnerships expert for YouTube creators. Find perfect brand partnerships and create outreach strategies for this channel.\n\n"
            . "Channel URL: {$channelUrl}\n\n"
            . "Provide analysis as JSON:\n"
            . "{\n"
            . "  \"channel_profile\": { \"niche\": \"\", \"audience_size\": \"\", \"engagement_rate\": \"\", \"brand_safety_score\": 0-100 },\n"
            . "  \"matchmaking_score\": 0-100,\n"
            . "  \"brand_matches\": [\n"
            . "    { \"brand\": \"\", \"industry\": \"\", \"match_score\": 0-100, \"deal_type\": \"sponsored|affiliate|ambassador|product\", \"estimated_rate\": \"\", \"reasoning\": \"\", \"pitch_angle\": \"\" }\n"
            . "  ],\n"
            . "  \"pitch_templates\": [\n"
            . "    { \"type\": \"cold_email|dm|media_kit\", \"template\": \"\" }\n"
            . "  ],\n"
            . "  \"outreach_strategy\": { \"best_platforms\": [], \"timing\": \"\", \"follow_up_cadence\": \"\" },\n"
            . "  \"tips\": [\"tip1\", \"tip2\", \"tip3\"]\n"
            . "}\n\nFind 8-10 brand matches. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('brand_deal_matchmaker', $channelUrl, $prompt);
    }

    /**
     * Scout licensing and syndication opportunities.
     */
    public function scoutLicensing(string $channelUrl): array
    {
        $prompt = "You are a content licensing and syndication expert. Find licensing opportunities for this YouTube creator's content.\n\n"
            . "Channel URL: {$channelUrl}\n\n"
            . "Provide analysis as JSON:\n"
            . "{\n"
            . "  \"content_analysis\": { \"niche\": \"\", \"content_type\": \"\", \"licensing_potential\": \"high|medium|low\", \"unique_value\": \"\" },\n"
            . "  \"licensing_score\": 0-100,\n"
            . "  \"opportunities\": [\n"
            . "    { \"type\": \"licensing|syndication|distribution\", \"platform\": \"\", \"description\": \"\", \"revenue_model\": \"\", \"estimated_monthly\": \"\", \"requirements\": \"\", \"action_steps\": [] }\n"
            . "  ],\n"
            . "  \"syndication_networks\": [\n"
            . "    { \"network\": \"\", \"type\": \"\", \"revenue_share\": \"\", \"best_for\": \"\" }\n"
            . "  ],\n"
            . "  \"legal_considerations\": [\"consideration1\", \"consideration2\"],\n"
            . "  \"action_plan\": [\n"
            . "    { \"step\": 1, \"action\": \"\", \"timeline\": \"\", \"expected_outcome\": \"\" }\n"
            . "  ]\n"
            . "}\n\nGenerate 5-7 opportunities and 4-5 networks. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('licensing_scout', $channelUrl, $prompt);
    }

    /**
     * Build automated revenue pipeline.
     */
    public function buildRevenuePipeline(string $channelUrl): array
    {
        $prompt = "You are a YouTube revenue automation consultant. Design an automated revenue system for this channel that runs with minimal manual effort.\n\n"
            . "Channel URL: {$channelUrl}\n\n"
            . "Provide analysis as JSON:\n"
            . "{\n"
            . "  \"channel_analysis\": { \"niche\": \"\", \"content_frequency\": \"\", \"current_automation_level\": \"low|medium|high\" },\n"
            . "  \"automation_score\": 0-100,\n"
            . "  \"revenue_streams\": [\n"
            . "    { \"stream\": \"\", \"automation_level\": \"full|partial|manual\", \"monthly_potential\": \"\", \"tools_needed\": [], \"setup_time\": \"\" }\n"
            . "  ],\n"
            . "  \"tool_stack\": [\n"
            . "    { \"tool\": \"\", \"purpose\": \"\", \"cost\": \"\", \"category\": \"content|marketing|sales|analytics\" }\n"
            . "  ],\n"
            . "  \"automation_workflows\": [\n"
            . "    { \"workflow\": \"\", \"trigger\": \"\", \"actions\": [], \"revenue_impact\": \"\" }\n"
            . "  ],\n"
            . "  \"implementation_timeline\": [\n"
            . "    { \"phase\": \"\", \"duration\": \"\", \"tasks\": [], \"milestone\": \"\" }\n"
            . "  ],\n"
            . "  \"total_automated_revenue\": \"\"\n"
            . "}\n\nGenerate 5-6 streams, 6-8 tools, and 3-4 workflows. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('revenue_automation', $channelUrl, $prompt);
    }

    /**
     * Core execution: call AI, parse result, save history.
     */
    protected function executeAnalysis(string $toolKey, string $input, string $prompt): array
    {
        $teamId = session('current_team_id', 0);
        $userId = auth()->id();

        // Check credit quota before proceeding
        $configKey = str_replace('_', '-', $toolKey);
        $credits = config("appaitools.enterprise_tools.{$configKey}.credits", 3);
        $quota = \Credit::checkQuota($teamId);
        if (!$quota['can_use']) {
            throw new \Exception($quota['message']);
        }

        $history = AiToolHistory::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'tool' => $toolKey,
            'platform' => 'youtube',
            'title' => $input,
            'input_data' => ['url' => $input, 'tool' => $toolKey],
            'status' => 2,
        ]);

        try {
            $aiResult = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);
            $rawText = $aiResult['data'][0] ?? '';
            $parsed = $this->parseJson($rawText);

            $history->update([
                'result_data' => $parsed,
                'status' => 1,
                'credits_used' => $credits,
            ]);

            // Track credit usage
            \Credit::trackUsage($credits, 'enterprise_tool', $toolKey, $teamId);

            return $parsed;
        } catch (\Exception $e) {
            Log::error("EnterpriseToolService [{$toolKey}]: " . $e->getMessage());
            $history->update(['status' => 0, 'result_data' => ['error' => $e->getMessage()]]);
            throw $e;
        }
    }

    /**
     * Parse JSON from AI response, handling markdown code blocks.
     */
    protected function parseJson(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Try to extract JSON object from text
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return ['raw_response' => $text, 'parse_error' => true];
    }
}
