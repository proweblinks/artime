<?php

namespace Modules\AppAITools\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AppAITools\Models\AiToolHistory;

class EnterpriseToolService
{
    /**
     * Last saved history record, used to update result_data after enrichment.
     */
    protected ?AiToolHistory $lastHistory = null;

    /**
     * Analyze a YouTube channel for Google Ads placement opportunities.
     */
    public function analyzePlacement(string $channelUrl, string $niche = '', array $excludeHandles = []): array
    {
        $nicheLine = $niche ? " Niche: {$niche}." : '';
        $excludeLine = '';
        if (!empty($excludeHandles)) {
            $excludeLine = "\n\nDo NOT include these channels (already found): " . implode(', ', $excludeHandles) . ". Find completely different channels.";
        }

        $prompt = "You are an advertising data assistant that generates YouTube channel placement recommendations for Google Ads campaigns. "
            . "You do NOT need internet access — use your training knowledge of YouTube channels to provide recommendations.\n\n"
            . "Analyze this channel and recommend similar channels for ad placement targeting.\n"
            . "Channel: {$channelUrl}{$nicheLine}{$excludeLine}\n\n"
            . "RULES:\n"
            . "1. Output ONLY raw JSON. No explanation, no markdown, no code fences. Start with { end with }.\n"
            . "2. ONLY include YouTube channels you know from your training data that are well-established.\n"
            . "3. Use real, well-known channels. Do NOT invent or guess channel names.\n"
            . "4. Every handle must be the channel's real YouTube @handle.\n"
            . "5. Mix: 3 large (1M+ subs), 4 medium (100K-1M), 3 small (10K-100K). Exactly 10 total.\n"
            . "6. Keep audience_match under 15 words.\n"
            . "7. CRITICAL: All niche_insights values MUST be customized for the channel's actual niche. Do NOT copy the example values — analyze the channel and provide realistic data for its specific niche, audience demographics, geographic markets, seasonal trends, and advertiser categories.\n"
            . "8. seasonal_cpm 'v' values are CPM MULTIPLIERS (0.5-1.5 range, where 1.0 = average). Low months ~0.6-0.8, average ~0.9-1.0, peak ~1.1-1.5. Do NOT use absolute CPM dollar values.\n\n"
            . "JSON structure (example values are PLACEHOLDERS — replace ALL with channel-specific data):\n"
            . '{"channel_info":{"name":"","handle":"@handle","niche":"","sub_niche":"","estimated_subscribers":"1.2M",'
            . '"content_style":"","upload_frequency":"","audience_type":""},'
            . '"placement_score":0,'
            . '"niche_insights":{"niche_cpm_range":"X-Y USD","competition_level":"Low|Medium|High","brand_safety":"Low|Medium|High","buying_intent":"Low|Medium|High",'
            . '"audience_demographics":"describe target demo","best_ad_formats":["format1","format2"],'
            . '"peak_months":["month1","month2"],"top_advertiser_categories":["industry1","industry2","industry3"],'
            . '"audience_interests":["interest1","interest2","interest3"],'
            . '"geographic_top5":[{"country":"XX","pct":0},{"country":"XX","pct":0},{"country":"XX","pct":0},{"country":"XX","pct":0},{"country":"XX","pct":0}],'
            . '"age_distribution":[{"range":"13-17","pct":0},{"range":"18-24","pct":0},{"range":"25-34","pct":0},{"range":"35-44","pct":0},{"range":"45+","pct":0}],'
            . '"gender_split":{"male":0,"female":0},'
            . '"best_content_types":["type1","type2","type3"],"optimal_video_length":"X-Y min","avg_engagement_rate":"X%",'
            . '"device_split":{"mobile":0,"desktop":0,"tv":0},'
            . '"seasonal_cpm":[{"m":"Jan","v":0.0},{"m":"Feb","v":0.0},{"m":"Mar","v":0.0},{"m":"Apr","v":0.0},{"m":"May","v":0.0},{"m":"Jun","v":0.0},{"m":"Jul","v":0.0},{"m":"Aug","v":0.0},{"m":"Sep","v":0.0},{"m":"Oct","v":0.0},{"m":"Nov","v":0.0},{"m":"Dec","v":0.0}]},'
            . '"placements":[{"channel_name":"","handle":"@handle","channel_url":"https://youtube.com/@handle",'
            . '"subscribers":"2.5M","relevance_score":92,"estimated_cpm":"4-8 USD","content_type":"",'
            . '"audience_match":"Short reason","recommended_ad_format":"Skippable in-stream","tier":"large"}],'
            . '"campaign_strategy":{"recommended_daily_budget":"20-50 USD","expected_cpm_range":"4-10 USD",'
            . '"expected_ctr":"0.5-1.2%","recommended_bid_strategy":"Target CPM",'
            . '"ad_group_structure":[{"group_name":"","channels":["@h1","@h2"],"rationale":""}]},'
            . '"google_ads_keywords":["keyword1","keyword2"],'
            . '"tips":["tip1","tip2","tip3"]}';

        $result = $this->executeAnalysis('placement_finder', $channelUrl, $prompt, 5000);

        // If AI refused or parse failed, throw so the user sees an error
        if (!empty($result['parse_error'])) {
            throw new \Exception('AI could not generate placement results. Please try again or use a different channel URL.');
        }

        // Enrich with real YouTube thumbnails and persist back to DB
        $enriched = $this->enrichPlacementsWithThumbnails($result, $channelUrl);
        $this->persistEnrichedResult($enriched);

        return $enriched;
    }

    /**
     * Find additional placement channels (excluding already found ones).
     */
    public function findMorePlacements(string $channelUrl, string $niche = '', array $excludeHandles = []): array
    {
        $nicheLine = $niche ? " Niche: {$niche}." : '';
        $excludeLine = "\n\nEXCLUDE these channels (already found): " . implode(', ', $excludeHandles) . ".\nFind 10 completely DIFFERENT channels not in that list.";

        $prompt = "You are an advertising data assistant that generates YouTube channel placement recommendations for Google Ads campaigns. "
            . "You do NOT need internet access — use your training knowledge of YouTube channels.\n\n"
            . "Find MORE placement channels similar to this channel for ad targeting.\n"
            . "Channel: {$channelUrl}{$nicheLine}{$excludeLine}\n\n"
            . "RULES:\n"
            . "1. Output ONLY raw JSON. No explanation, no markdown, no code fences. Start with { end with }.\n"
            . "2. ONLY include YouTube channels you know from your training data that are well-established.\n"
            . "3. Use real, well-known channels. Do NOT invent or guess channel names.\n"
            . "4. Every handle must be the channel's real YouTube @handle.\n"
            . "5. Mix: 3 large (1M+), 4 medium (100K-1M), 3 small (10K-100K). Exactly 10 total.\n"
            . "6. Keep audience_match under 15 words.\n\n"
            . "Return ONLY the placements array:\n"
            . '{"placements":[{"channel_name":"","handle":"@handle","channel_url":"https://youtube.com/@handle",'
            . '"subscribers":"2.5M","relevance_score":92,"estimated_cpm":"4-8 USD","content_type":"",'
            . '"audience_match":"Short reason","recommended_ad_format":"Skippable in-stream","tier":"large"}]}';

        $result = $this->executeAnalysis('placement_finder', $channelUrl, $prompt, 3000);

        if (!empty($result['parse_error'])) {
            throw new \Exception('AI could not generate more placements. Please try again.');
        }

        // Enrich with real YouTube thumbnails and persist back to DB
        $enriched = $this->enrichPlacementsWithThumbnails($result);
        $this->persistEnrichedResult($enriched);

        return $enriched;
    }

    /**
     * Enrich placement results with real YouTube channel thumbnails.
     * Uses YouTube Data API to batch-fetch channel info by handle.
     */
    protected function enrichPlacementsWithThumbnails(array $result, ?string $sourceChannelUrl = null): array
    {
        try {
            $ytService = app(YouTubeDataService::class);

            // Fetch source channel thumbnail
            if ($sourceChannelUrl && isset($result['channel_info'])) {
                try {
                    $channelData = $ytService->getChannelData($sourceChannelUrl);
                    if ($channelData && !empty($channelData['thumbnail'])) {
                        $result['channel_info']['thumbnail_url'] = $channelData['thumbnail'];
                        // Also enrich with real subscriber count if available
                        if (!empty($channelData['subscribers'])) {
                            $result['channel_info']['real_subscribers'] = $this->formatSubscriberCount($channelData['subscribers']);
                        }
                        if (!empty($channelData['title'])) {
                            $result['channel_info']['name'] = $channelData['title'];
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug("Could not fetch source channel thumbnail: " . $e->getMessage());
                }
            }

            // Fetch thumbnails for placement channels
            if (!empty($result['placements'])) {
                foreach ($result['placements'] as $idx => &$placement) {
                    $handle = $placement['handle'] ?? '';
                    if (empty($handle)) continue;

                    try {
                        $handleClean = ltrim($handle, '@');
                        $placementUrl = "https://youtube.com/@{$handleClean}";
                        $channelData = $ytService->getChannelData($placementUrl);

                        if ($channelData && !empty($channelData['thumbnail'])) {
                            $placement['thumbnail_url'] = $channelData['thumbnail'];
                            // Update with real subscriber count
                            if (!empty($channelData['subscribers'])) {
                                $placement['subscribers'] = $this->formatSubscriberCount($channelData['subscribers']);
                            }
                            if (!empty($channelData['title'])) {
                                $placement['channel_name'] = $channelData['title'];
                            }
                        }
                    } catch (\Exception $e) {
                        Log::debug("Could not fetch thumbnail for {$handle}: " . $e->getMessage());
                    }
                }
                unset($placement);
            }
        } catch (\Exception $e) {
            Log::warning("Thumbnail enrichment failed: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Save enriched result data back to the last history record.
     */
    protected function persistEnrichedResult(array $result): void
    {
        if ($this->lastHistory) {
            $this->lastHistory->update(['result_data' => $result]);
        }
    }

    /**
     * Format a raw subscriber count into a human-readable string.
     */
    protected function formatSubscriberCount(int $count): string
    {
        if ($count >= 1000000) {
            return round($count / 1000000, 1) . 'M';
        }
        if ($count >= 1000) {
            return round($count / 1000, 1) . 'K';
        }
        return (string) $count;
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

    // ── YouTube Context Helpers (reused by cross-platform tools) ──

    /**
     * Fetch real YouTube video data and format as context for AI prompts.
     */
    protected function fetchYouTubeVideoContext(string $youtubeUrl): ?array
    {
        try {
            $ytService = app(YouTubeDataService::class);
            $videoData = $ytService->getVideoData($youtubeUrl);
            if (!$videoData) return null;

            $tags = array_slice($videoData['tags'] ?? [], 0, 15);
            $description = mb_substr($videoData['description'] ?? '', 0, 300);
            $views = number_format($videoData['views'] ?? 0);
            $likes = number_format($videoData['likes'] ?? 0);
            $comments = number_format($videoData['comments'] ?? 0);

            $contextText = "=== REAL YOUTUBE VIDEO DATA (from YouTube API) ===\n"
                . "Title: {$videoData['title']}\n"
                . "Channel: {$videoData['channel']}\n"
                . "Views: {$views}\n"
                . "Likes: {$likes}\n"
                . "Comments: {$comments}\n"
                . "Duration: {$videoData['duration']}\n"
                . "Tags: " . implode(', ', $tags) . "\n"
                . "Description: {$description}\n"
                . "=== END REAL DATA ===";

            return [
                'context_text' => $contextText,
                'video_data' => $videoData,
            ];
        } catch (\Exception $e) {
            Log::debug("fetchYouTubeVideoContext failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch real YouTube channel data + recent videos and format as context for AI prompts.
     */
    protected function fetchYouTubeChannelContext(string $channelUrl, int $videoLimit = 15): ?array
    {
        try {
            $ytService = app(YouTubeDataService::class);
            $channelData = $ytService->getChannelData($channelUrl);
            if (!$channelData) return null;

            $videos = [];
            if (!empty($channelData['id'])) {
                $videos = $ytService->getChannelVideos($channelData['id'], $videoLimit);
            }

            // Calculate metrics from videos
            $totalViews = 0;
            $totalLikes = 0;
            $videoCount = count($videos);
            foreach ($videos as $v) {
                $totalViews += $v['views'] ?? 0;
                $totalLikes += $v['likes'] ?? 0;
            }
            $avgViews = $videoCount > 0 ? round($totalViews / $videoCount) : 0;
            $avgLikes = $videoCount > 0 ? round($totalLikes / $videoCount) : 0;
            $engagementRate = $avgViews > 0 ? round(($avgLikes / $avgViews) * 100, 2) : 0;

            $subs = $this->formatSubscriberCount($channelData['subscribers'] ?? 0);

            // Build top 10 video lines
            $topVideos = array_slice($videos, 0, 10);
            $videoLines = '';
            foreach ($topVideos as $i => $v) {
                $vViews = number_format($v['views'] ?? 0);
                $vLikes = number_format($v['likes'] ?? 0);
                $videoLines .= ($i + 1) . ". \"{$v['title']}\" — {$vViews} views, {$vLikes} likes\n";
            }

            $contextText = "=== REAL YOUTUBE CHANNEL DATA (from YouTube API) ===\n"
                . "Channel: {$channelData['title']}\n"
                . "Subscribers: {$subs}\n"
                . "Total Views: " . number_format($channelData['total_views'] ?? 0) . "\n"
                . "Video Count: {$channelData['video_count']}\n"
                . "Avg Views (recent {$videoCount}): " . number_format($avgViews) . "\n"
                . "Avg Likes (recent {$videoCount}): " . number_format($avgLikes) . "\n"
                . "Engagement Rate: {$engagementRate}%\n"
                . "Top Videos:\n{$videoLines}"
                . "=== END REAL DATA ===";

            return [
                'context_text' => $contextText,
                'channel_data' => $channelData,
                'videos' => $videos,
                'metrics' => [
                    'avg_views' => $avgViews,
                    'avg_likes' => $avgLikes,
                    'engagement_rate' => $engagementRate,
                ],
            ];
        } catch (\Exception $e) {
            Log::debug("fetchYouTubeChannelContext failed: " . $e->getMessage());
            return null;
        }
    }

    // ── Cross-Platform YouTube↔TikTok Tools ──────────────────────

    /**
     * Convert a YouTube video into TikTok content strategy.
     */
    public function convertYoutubeToTiktok(string $youtubeUrl, string $tiktokStyle = ''): array
    {
        $ytContext = $this->fetchYouTubeVideoContext($youtubeUrl);

        $contextBlock = '';
        if ($ytContext) {
            $contextBlock = "\n\n" . $ytContext['context_text'] . "\n\nUse the REAL YouTube data above to ground your analysis. Reference actual view counts, tags, and content structure.";
        }

        $styleLine = $tiktokStyle ? " Preferred TikTok style: {$tiktokStyle}." : '';

        $prompt = "You are a cross-platform content strategist specializing in adapting YouTube content for TikTok. "
            . "Analyze this YouTube video and create a comprehensive TikTok adaptation plan.\n\n"
            . "YouTube URL: {$youtubeUrl}{$styleLine}{$contextBlock}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"adaptation_score":0,'
            . '"video_overview":{"title":"","channel":"","views":"","duration":"","top_tags":[""],"content_type":""},'
            . '"hook_rewrites":[{"original_angle":"","tiktok_hook":"","style":"","why_effective":""}],'
            . '"clip_suggestions":[{"segment":"","timestamp_hint":"","duration":"","tiktok_format":"","hook":""}],'
            . '"hashtag_strategy":{"primary":["#tag"],"secondary":["#tag"],"trending":["#tag"]},'
            . '"sound_suggestions":[{"type":"original|trending","description":"","why_fits":""}],'
            . '"caption_rewrites":[{"style":"","caption":"","cta":""}],'
            . '"format_tips":[{"tip":"","impact":"high|medium|low"}],'
            . '"cross_platform_tips":["tip1","tip2"]'
            . '}'
            . "\n\nGenerate 3-5 hook rewrites, 3-5 clip suggestions, 3 caption rewrites, and 3-5 format tips. Respond with ONLY valid JSON.";

        $result = $this->executeAnalysis('tiktok_yt_converter', $youtubeUrl, $prompt, 5000);

        // Merge real YouTube data into result
        if ($ytContext) {
            $vd = $ytContext['video_data'];
            $result['youtube_insights'] = [
                'thumbnail' => $vd['thumbnail'] ?? '',
                'title' => $vd['title'] ?? '',
                'channel' => $vd['channel'] ?? '',
                'views' => number_format($vd['views'] ?? 0),
                'likes' => number_format($vd['likes'] ?? 0),
                'comments' => number_format($vd['comments'] ?? 0),
                'duration' => $vd['duration'] ?? '',
                'tags' => array_slice($vd['tags'] ?? [], 0, 10),
            ];
            $this->persistEnrichedResult($result);
        }

        return $result;
    }

    /**
     * Analyze cross-platform audience arbitrage between YouTube and TikTok.
     */
    public function analyzeYoutubeTiktokArbitrage(string $youtubeChannel, string $tiktokNiche = ''): array
    {
        $ytContext = $this->fetchYouTubeChannelContext($youtubeChannel, 20);

        $contextBlock = '';
        if ($ytContext) {
            $contextBlock = "\n\n" . $ytContext['context_text'] . "\n\nUse the REAL YouTube data above. Reference actual subscriber counts, video performance, and content themes to find gaps and opportunities on TikTok.";
        }

        $nicheLine = $tiktokNiche ? " TikTok niche focus: {$tiktokNiche}." : '';

        $prompt = "You are a cross-platform audience growth strategist. Analyze this YouTube channel's data and identify content gaps and first-mover opportunities on TikTok.\n\n"
            . "YouTube Channel: {$youtubeChannel}{$nicheLine}{$contextBlock}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"arbitrage_score":0,'
            . '"youtube_overview":{"channel_name":"","subscribers":"","avg_views":"","top_content_themes":[""],"posting_frequency":""},'
            . '"content_gaps":[{"topic":"","youtube_performance":"","tiktok_saturation":"low|medium|high","opportunity_level":"high|medium|low","reasoning":""}],'
            . '"first_mover_opportunities":[{"idea":"","format":"","estimated_reach":"","urgency":"high|medium|low","reference_video":""}],'
            . '"audience_overlap":{"estimated_overlap":"","unique_youtube_audience":"","tiktok_growth_potential":"","demographic_shift":""},'
            . '"cross_platform_strategy":{"content_pillars":[""],"posting_cadence":"","repurpose_ratio":"","growth_timeline":""},'
            . '"quick_wins":[{"action":"","expected_result":"","effort":"low|medium","timeframe":""}]'
            . '}'
            . "\n\nGenerate 4-6 content gaps, 3-5 first-mover opportunities, and 3-5 quick wins. Respond with ONLY valid JSON.";

        $result = $this->executeAnalysis('tiktok_yt_arbitrage', $youtubeChannel, $prompt, 5000);

        // Merge real YouTube data into result
        if ($ytContext) {
            $cd = $ytContext['channel_data'];
            $m = $ytContext['metrics'];
            $result['youtube_insights'] = [
                'thumbnail' => $cd['thumbnail'] ?? '',
                'title' => $cd['title'] ?? '',
                'subscribers' => $this->formatSubscriberCount($cd['subscribers'] ?? 0),
                'total_views' => number_format($cd['total_views'] ?? 0),
                'video_count' => $cd['video_count'] ?? 0,
                'avg_views' => number_format($m['avg_views'] ?? 0),
                'avg_likes' => number_format($m['avg_likes'] ?? 0),
                'engagement_rate' => ($m['engagement_rate'] ?? 0) . '%',
            ];
            $this->persistEnrichedResult($result);
        }

        return $result;
    }

    // ── TikTok Tools ──────────────────────────────────────────────

    /**
     * Build TikTok hashtag strategy for a niche.
     */
    public function analyzeTiktokHashtagStrategy(string $niche, string $contentType = '', string $youtubeChannel = ''): array
    {
        $ctLine = $contentType ? " Content type: {$contentType}." : '';

        $ytContextBlock = '';
        if ($youtubeChannel) {
            $ytContext = $this->fetchYouTubeChannelContext($youtubeChannel);
            if ($ytContext) {
                $ytContextBlock = "\n\n" . $ytContext['context_text'] . "\n\nMap YouTube tags and content themes to TikTok hashtag opportunities. Use real YouTube data to inform hashtag relevance.";
            }
        }

        $prompt = "You are a TikTok marketing expert specializing in hashtag strategy and discoverability. "
            . "Analyze the niche and build a comprehensive hashtag strategy.\n\n"
            . "Niche: {$niche}{$ctLine}{$ytContextBlock}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"hashtag_score":0,'
            . '"primary_hashtags":[{"tag":"#tag","avg_views":"","competition":"low|medium|high","trending":true}],'
            . '"secondary_hashtags":[{"tag":"#tag","avg_views":"","purpose":""}],'
            . '"hashtag_sets":[{"name":"Set name","tags":["#tag1","#tag2"],"best_for":"","estimated_reach":""}],'
            . '"trending_now":[{"tag":"#tag","growth_rate":"","peak_window":""}],'
            . '"strategy_tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nGenerate 8-10 primary hashtags, 5-8 secondary, 3-4 hashtag sets, and 3-5 trending. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('tiktok_hashtag_strategy', $niche, $prompt);
    }

    /**
     * Analyze TikTok SEO for a profile/caption.
     */
    public function analyzeTiktokSeo(string $profile, string $caption = ''): array
    {
        $capLine = $caption ? "\nCaption to analyze: {$caption}" : '';

        $prompt = "You are a TikTok SEO expert. Analyze this profile's discoverability and provide optimization recommendations.\n\n"
            . "Profile: {$profile}{$capLine}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"seo_score":0,'
            . '"profile_analysis":{"bio_score":0,"username_score":0,"keyword_density":"","improvements":[""]},'
            . '"caption_analysis":{"readability":"","keyword_usage":"","cta_present":true,"hashtag_placement":""},'
            . '"keyword_opportunities":[{"keyword":"","search_volume":"","competition":"low|medium|high","recommendation":""}],'
            . '"content_pillars":[{"topic":"","search_demand":"","content_ideas":[""]}],'
            . '"optimization_tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nGenerate 6-8 keyword opportunities and 3-4 content pillars. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('tiktok_seo_analyzer', $profile, $prompt);
    }

    /**
     * Optimize posting times for a TikTok profile.
     */
    public function analyzeTiktokPostingTime(string $profile, string $timezone = '', string $contentType = '', string $youtubeChannel = ''): array
    {
        $tzLine = $timezone ? " Timezone: {$timezone}." : '';
        $ctLine = $contentType ? " Content type: {$contentType}." : '';

        $ytContextBlock = '';
        if ($youtubeChannel) {
            $ytContext = $this->fetchYouTubeChannelContext($youtubeChannel);
            if ($ytContext) {
                $ytContextBlock = "\n\n" . $ytContext['context_text'] . "\n\nUse YouTube publishing patterns to infer audience activity windows. Cross-reference YouTube upload times with engagement data.";
            }
        }

        $prompt = "You are a TikTok analytics expert specializing in posting optimization. Analyze this creator's optimal posting schedule.\n\n"
            . "Profile: {$profile}{$tzLine}{$ctLine}{$ytContextBlock}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"timing_score":0,'
            . '"best_times":[{"day":"Monday","times":["9:00 AM","7:00 PM"],"engagement_level":"high|medium|low"}],'
            . '"weekly_schedule":[{"day":"","post_count":0,"best_slots":[""],"content_type":""}],'
            . '"peak_hours":{"weekday":[""],"weekend":[""]},'
            . '"avoid_times":[{"time":"","reason":""}],'
            . '"frequency_recommendation":{"posts_per_day":0,"posts_per_week":0,"reasoning":""},'
            . '"tips":["tip1","tip2"]'
            . '}'
            . "\n\nProvide data for all 7 days. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('tiktok_posting_time', $profile, $prompt);
    }

    /**
     * Analyze a TikTok hook for retention.
     */
    public function analyzeTiktokHook(string $hookText, string $niche = '', string $youtubeChannel = ''): array
    {
        $nicheLine = $niche ? " Niche: {$niche}." : '';

        $ytContextBlock = '';
        if ($youtubeChannel) {
            $ytContext = $this->fetchYouTubeChannelContext($youtubeChannel);
            if ($ytContext) {
                $ytContextBlock = "\n\n" . $ytContext['context_text'] . "\n\nReference YouTube intro patterns from this channel's top-performing videos. Suggest hooks that leverage proven YouTube engagement patterns for TikTok.";
            }
        }

        $prompt = "You are a TikTok content expert specializing in viewer retention and hook psychology. Analyze this hook and provide improvements.\n\n"
            . "Hook text: {$hookText}{$nicheLine}{$ytContextBlock}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"hook_score":0,'
            . '"analysis":{"attention_grab":0,"curiosity_gap":0,"emotional_trigger":0,"clarity":0,"pacing":""},'
            . '"strengths":[""],'
            . '"weaknesses":[""],'
            . '"improved_versions":[{"hook":"","style":"","why_better":""}],'
            . '"hook_formulas":[{"name":"","template":"","example":""}],'
            . '"retention_tips":["tip1","tip2"]'
            . '}'
            . "\n\nScore all sub-scores 0-100. Generate 3-5 improved versions and 3-4 hook formulas. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('tiktok_hook_analyzer', $hookText, $prompt);
    }

    /**
     * Analyze trending sounds for a TikTok niche.
     */
    public function analyzeTiktokSoundTrends(string $niche, string $contentStyle = ''): array
    {
        $csLine = $contentStyle ? " Content style: {$contentStyle}." : '';

        $prompt = "You are a TikTok audio trends expert. Analyze trending sounds and recommend audio strategies for this niche.\n\n"
            . "Niche: {$niche}{$csLine}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"sound_score":0,'
            . '"trending_sounds":[{"name":"","artist":"","usage_count":"","growth_rate":"","peak_status":"rising|peak|declining","best_for":""}],'
            . '"emerging_sounds":[{"name":"","current_usage":"","predicted_peak":"","why_trending":""}],'
            . '"evergreen_sounds":[{"name":"","category":"","best_use_case":""}],'
            . '"sound_strategy":{"original_vs_trending":"","timing":"","niche_fit":""},'
            . '"tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nGenerate 6-8 trending sounds, 3-5 emerging, and 3-4 evergreen. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('tiktok_sound_trends', $niche, $prompt);
    }

    /**
     * Predict viral potential of TikTok content.
     */
    public function analyzeTiktokViralPotential(string $contentDescription, string $niche = '', string $followerCount = '', string $youtubeUrl = ''): array
    {
        $nicheLine = $niche ? " Niche: {$niche}." : '';
        $fcLine = $followerCount ? " Follower count: {$followerCount}." : '';

        $ytContextBlock = '';
        if ($youtubeUrl) {
            $ytContext = $this->fetchYouTubeVideoContext($youtubeUrl);
            if ($ytContext) {
                $ytContextBlock = "\n\n" . $ytContext['context_text'] . "\n\nUse YouTube performance data to calibrate viral prediction. If the YouTube video performed well, factor that into TikTok viral probability.";
            }
        }

        $prompt = "You are a TikTok viral content analyst. Predict the viral potential of this content concept and provide optimization suggestions.\n\n"
            . "Content concept: {$contentDescription}{$nicheLine}{$fcLine}{$ytContextBlock}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"viral_score":0,'
            . '"prediction":{"estimated_views":"","confidence":"","viral_probability":""},'
            . '"viral_signals":[{"signal":"","score":0,"analysis":""}],'
            . '"strengths":[""],'
            . '"risks":[""],'
            . '"optimization_suggestions":[{"area":"","current":"","suggested":"","impact":"high|medium|low"}],'
            . '"similar_viral_content":[{"description":"","views":"","why_viral":""}],'
            . '"tips":["tip1","tip2"]'
            . '}'
            . "\n\nGenerate 5-7 viral signals, 3-5 optimization suggestions, and 2-3 similar viral examples. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('tiktok_viral_predictor', $contentDescription, $prompt);
    }

    /**
     * Calculate TikTok Creator Fund earnings.
     */
    public function analyzeTiktokCreatorFund(string $profile, string $avgViews = '', string $followerCount = ''): array
    {
        $avLine = $avgViews ? " Average views: {$avgViews}." : '';
        $fcLine = $followerCount ? " Follower count: {$followerCount}." : '';

        $prompt = "You are a TikTok monetization expert specializing in Creator Fund and Creativity Program. Calculate earnings and optimize revenue for this creator.\n\n"
            . "Profile: {$profile}{$avLine}{$fcLine}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"fund_score":0,'
            . '"earnings_estimate":{"daily":"","weekly":"","monthly":"","yearly":""},'
            . '"fund_breakdown":{"creativity_program":"","creator_fund":"","estimated_rpm":""},'
            . '"eligibility":{"status":"","requirements_met":[""],"requirements_missing":[""]},'
            . '"revenue_comparison":[{"source":"","estimated_monthly":"","difficulty":"easy|medium|hard","status":"active|potential"}],'
            . '"optimization_tips":[{"tip":"","potential_increase":"","effort":"easy|medium|hard"}],'
            . '"growth_milestones":[{"followers":"","estimated_monthly":"","unlock":""}]'
            . '}'
            . "\n\nGenerate 4-5 revenue comparisons, 4-5 optimization tips, and 3-4 milestones. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('tiktok_creator_fund', $profile, $prompt);
    }

    /**
     * Plan duet and stitch opportunities for a TikTok creator.
     */
    public function analyzeTiktokDuetStitch(string $profile, string $niche = '', string $goal = ''): array
    {
        $nicheLine = $niche ? " Niche: {$niche}." : '';
        $goalLine = $goal ? " Goal: {$goal}." : '';

        $prompt = "You are a TikTok collaboration strategist. Find the best duet and stitch opportunities for this creator.\n\n"
            . "Profile: {$profile}{$nicheLine}{$goalLine}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"collaboration_score":0,'
            . '"duet_opportunities":[{"creator":"","followers":"","content_type":"","engagement_rate":"","duet_idea":"","potential_reach":""}],'
            . '"stitch_opportunities":[{"creator":"","video_topic":"","stitch_angle":"","why_effective":""}],'
            . '"trending_duets":[{"trend":"","how_to_participate":"","timing":""}],'
            . '"strategy":{"frequency":"","best_times":"","content_mix":""},'
            . '"tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nGenerate 5-6 duet opportunities, 4-5 stitch opportunities, and 3-4 trending duets. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('tiktok_duet_stitch', $profile, $prompt);
    }

    /**
     * Find brand partnership opportunities for a TikTok creator.
     */
    public function analyzeTiktokBrandPartnership(string $profile, string $niche = '', string $followerCount = ''): array
    {
        $nicheLine = $niche ? " Niche: {$niche}." : '';
        $fcLine = $followerCount ? " Follower count: {$followerCount}." : '';

        $prompt = "You are a TikTok brand partnerships expert. Find brand matches and create outreach strategies for this creator.\n\n"
            . "Profile: {$profile}{$nicheLine}{$fcLine}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"partnership_score":0,'
            . '"brand_matches":[{"brand":"","industry":"","match_score":0,"deal_type":"","estimated_rate":"","why_match":""}],'
            . '"pitch_templates":[{"brand_type":"","subject_line":"","pitch_body":"","key_metrics_to_include":[""]}],'
            . '"rate_card":{"sponsored_post":"","brand_integration":"","series_deal":"","affiliate":""},'
            . '"outreach_strategy":{"best_platforms":[""],"timing":"","follow_up":""},'
            . '"tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nGenerate 6-8 brand matches and 2-3 pitch templates. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('tiktok_brand_partnership', $profile, $prompt);
    }

    /**
     * Optimize TikTok Shop strategy.
     */
    public function analyzeTiktokShop(string $profile, string $productType = '', string $priceRange = ''): array
    {
        $ptLine = $productType ? " Product type: {$productType}." : '';
        $prLine = $priceRange ? " Price range: {$priceRange}." : '';

        $prompt = "You are a TikTok Shop optimization expert. Analyze this shop and provide product, affiliate, and content strategies.\n\n"
            . "Profile/Shop: {$profile}{$ptLine}{$prLine}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"shop_score":0,'
            . '"shop_overview":{"estimated_revenue":"","product_count":"","top_category":"","conversion_rate":""},'
            . '"product_recommendations":[{"product":"","category":"","price_range":"","demand_level":"high|medium|low","competition":"low|medium|high","profit_margin":""}],'
            . '"affiliate_opportunities":[{"product":"","commission_rate":"","avg_sales":"","content_angle":""}],'
            . '"content_strategy":[{"content_type":"","product_showcase":"","estimated_conversion":"","example":""}],'
            . '"pricing_optimization":{"current_assessment":"","recommendations":[""]},'
            . '"tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nGenerate 5-6 product recommendations, 4-5 affiliate opportunities, and 3-4 content strategies. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('tiktok_shop_optimizer', $profile, $prompt);
    }

    // ── Instagram Cross-Platform Tools ─────────────────────────

    /**
     * Convert a YouTube video into Instagram Reels strategy.
     */
    public function convertYoutubeToReels(string $youtubeUrl, string $reelsStyle = ''): array
    {
        $ytContext = $this->fetchYouTubeVideoContext($youtubeUrl);

        $contextBlock = '';
        if ($ytContext) {
            $contextBlock = "\n\n" . $ytContext['context_text'] . "\n\nUse the REAL YouTube data above to ground your analysis. Reference actual view counts, tags, and content structure.";
        }

        $styleLine = $reelsStyle ? " Preferred Reels style: {$reelsStyle}." : '';

        $prompt = "You are a cross-platform content strategist specializing in adapting YouTube content for Instagram Reels. "
            . "Analyze this YouTube video and create a comprehensive Reels adaptation plan.\n\n"
            . "YouTube URL: {$youtubeUrl}{$styleLine}{$contextBlock}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"adaptation_score":0,'
            . '"video_overview":{"title":"","channel":"","views":"","duration":"","top_tags":[""],"content_type":""},'
            . '"reels_adaptations":[{"segment":"","duration":"15s|30s|60s|90s","hook":"","caption":"","format":"talking_head|b-roll|montage|tutorial"}],'
            . '"hook_rewrites":[{"original_angle":"","reels_hook":"","style":"","why_effective":""}],'
            . '"hashtag_strategy":{"primary":["#tag"],"niche":["#tag"],"trending":["#tag"]},'
            . '"audio_suggestions":[{"type":"original|trending","description":"","why_fits":""}],'
            . '"caption_rewrites":[{"style":"","caption":"","cta":""}],'
            . '"cover_image_tips":[{"tip":"","style":""}],'
            . '"cross_platform_tips":["tip1","tip2"]'
            . '}'
            . "\n\nGenerate 3-5 reels adaptations, 3-5 hook rewrites, 3 caption rewrites, and 3-5 audio suggestions. Respond with ONLY valid JSON.";

        $result = $this->executeAnalysis('ig_yt_reels_converter', $youtubeUrl, $prompt, 5000);

        if ($ytContext) {
            $vd = $ytContext['video_data'];
            $result['youtube_insights'] = [
                'thumbnail' => $vd['thumbnail'] ?? '',
                'title' => $vd['title'] ?? '',
                'channel' => $vd['channel'] ?? '',
                'views' => number_format($vd['views'] ?? 0),
                'likes' => number_format($vd['likes'] ?? 0),
                'comments' => number_format($vd['comments'] ?? 0),
                'duration' => $vd['duration'] ?? '',
                'tags' => array_slice($vd['tags'] ?? [], 0, 10),
            ];
            $this->persistEnrichedResult($result);
        }

        return $result;
    }

    /**
     * Analyze cross-platform audience arbitrage between YouTube and Instagram.
     */
    public function analyzeYoutubeInstagramArbitrage(string $youtubeChannel, string $igNiche = ''): array
    {
        $ytContext = $this->fetchYouTubeChannelContext($youtubeChannel, 20);

        $contextBlock = '';
        if ($ytContext) {
            $contextBlock = "\n\n" . $ytContext['context_text'] . "\n\nUse the REAL YouTube data above. Reference actual subscriber counts, video performance, and content themes to find gaps and opportunities on Instagram.";
        }

        $nicheLine = $igNiche ? " Instagram niche focus: {$igNiche}." : '';

        $prompt = "You are a cross-platform audience growth strategist. Analyze this YouTube channel's data and identify content gaps and first-mover opportunities on Instagram.\n\n"
            . "YouTube Channel: {$youtubeChannel}{$nicheLine}{$contextBlock}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"arbitrage_score":0,'
            . '"youtube_overview":{"channel_name":"","subscribers":"","avg_views":"","top_content_themes":[""],"posting_frequency":""},'
            . '"content_gaps":[{"topic":"","youtube_performance":"","ig_saturation":"low|medium|high","opportunity_level":"high|medium|low","best_ig_format":"reels|carousel|story|post","reasoning":""}],'
            . '"first_mover_opportunities":[{"idea":"","format":"reels|carousel|story","estimated_reach":"","urgency":"high|medium|low","reference_video":""}],'
            . '"audience_overlap":{"estimated_overlap":"","unique_youtube_audience":"","ig_growth_potential":"","demographic_shift":""},'
            . '"cross_platform_strategy":{"content_pillars":[""],"posting_cadence":"","repurpose_ratio":"","growth_timeline":""},'
            . '"format_recommendations":[{"youtube_type":"","ig_format":"","adaptation_tips":""}],'
            . '"quick_wins":[{"action":"","expected_result":"","effort":"low|medium","timeframe":""}]'
            . '}'
            . "\n\nGenerate 4-6 content gaps, 3-5 first-mover opportunities, 3-4 format recommendations, and 3-5 quick wins. Respond with ONLY valid JSON.";

        $result = $this->executeAnalysis('ig_yt_arbitrage', $youtubeChannel, $prompt, 5000);

        if ($ytContext) {
            $cd = $ytContext['channel_data'];
            $m = $ytContext['metrics'];
            $result['youtube_insights'] = [
                'thumbnail' => $cd['thumbnail'] ?? '',
                'title' => $cd['title'] ?? '',
                'subscribers' => $this->formatSubscriberCount($cd['subscribers'] ?? 0),
                'total_views' => number_format($cd['total_views'] ?? 0),
                'video_count' => $cd['video_count'] ?? 0,
                'avg_views' => number_format($m['avg_views'] ?? 0),
                'avg_likes' => number_format($m['avg_likes'] ?? 0),
                'engagement_rate' => ($m['engagement_rate'] ?? 0) . '%',
            ];
            $this->persistEnrichedResult($result);
        }

        return $result;
    }

    // ── Instagram Tools ─────────────────────────────────────────

    /**
     * Analyze Instagram Reels monetization potential.
     */
    public function analyzeInstagramReelsMonetization(string $profile, string $avgViews = '', string $followerCount = '', string $youtubeChannel = ''): array
    {
        $avLine = $avgViews ? " Average Reels views: {$avgViews}." : '';
        $fcLine = $followerCount ? " Follower count: {$followerCount}." : '';

        $ytContextBlock = '';
        if ($youtubeChannel) {
            $ytContext = $this->fetchYouTubeChannelContext($youtubeChannel);
            if ($ytContext) {
                $ytContextBlock = "\n\n" . $ytContext['context_text'] . "\n\nUse YouTube data to compare cross-platform monetization opportunities and benchmark earnings.";
            }
        }

        $prompt = "You are an Instagram monetization expert specializing in Reels revenue optimization. Analyze this creator's Reels monetization potential.\n\n"
            . "Profile: {$profile}{$avLine}{$fcLine}{$ytContextBlock}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"reels_score":0,'
            . '"earnings_estimate":{"daily":"","weekly":"","monthly":""},'
            . '"monetization_paths":[{"source":"","estimated_monthly":"","eligibility":"","action":""}],'
            . '"content_optimization":[{"tip":"","impact":"high|medium|low","effort":"low|medium|high"}],'
            . '"benchmark_comparison":{"your_metrics":"","niche_avg":"","top_creators":""},'
            . '"growth_milestones":[{"followers":"","monthly_estimate":"","unlock":""}]'
            . '}'
            . "\n\nGenerate 4-6 monetization paths, 4-5 optimization tips, and 4 growth milestones. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('ig_reels_monetization', $profile, $prompt);
    }

    /**
     * Analyze Instagram SEO for a profile/caption.
     */
    public function analyzeInstagramSeo(string $profile, string $caption = '', string $youtubeChannel = ''): array
    {
        $capLine = $caption ? "\nCaption to analyze: {$caption}" : '';

        $ytContextBlock = '';
        if ($youtubeChannel) {
            $ytContext = $this->fetchYouTubeChannelContext($youtubeChannel);
            if ($ytContext) {
                $ytContextBlock = "\n\n" . $ytContext['context_text'] . "\n\nMap YouTube keywords and tags to Instagram SEO opportunities. Use real YouTube data to inform keyword strategy.";
            }
        }

        $prompt = "You are an Instagram SEO expert. Analyze this profile's discoverability and provide optimization recommendations for Instagram search.\n\n"
            . "Profile: {$profile}{$capLine}{$ytContextBlock}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"seo_score":0,'
            . '"profile_analysis":{"bio_score":0,"username_score":0,"keyword_density":"","improvements":[""]},'
            . '"search_optimization":{"keywords_found":[""],"missing_keywords":[""],"alt_text_score":""},'
            . '"caption_analysis":{"readability":"","keyword_usage":"","cta_present":true,"hashtag_placement":""},'
            . '"keyword_opportunities":[{"keyword":"","search_volume":"","competition":"low|medium|high","recommendation":""}],'
            . '"content_pillars":[{"topic":"","search_demand":"","content_ideas":[""]}],'
            . '"optimization_tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nGenerate 6-8 keyword opportunities and 3-4 content pillars. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('ig_seo_optimizer', $profile, $prompt);
    }

    /**
     * Plan Instagram Story engagement strategy.
     */
    public function analyzeInstagramStoryEngagement(string $profile, string $goal = '', string $industry = ''): array
    {
        $goalLine = $goal ? " Story goal: {$goal}." : '';
        $indLine = $industry ? " Industry: {$industry}." : '';

        $prompt = "You are an Instagram Stories engagement expert. Build a comprehensive story strategy that drives interaction and conversions.\n\n"
            . "Profile: {$profile}{$goalLine}{$indLine}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"engagement_score":0,'
            . '"story_framework":[{"day":"Monday","theme":"","sticker_type":"poll|quiz|question|slider|countdown","cta":"","goal":""}],'
            . '"interactive_elements":[{"type":"poll|quiz|question|slider|countdown|link","usage":"","expected_engagement":"","best_for":""}],'
            . '"funnel_strategy":{"awareness":[""],"consideration":[""],"conversion":[""]},'
            . '"content_calendar":[{"time_slot":"","content_type":"","hook":""}],'
            . '"tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nProvide a 7-day story framework, 5-6 interactive elements, and 5-6 content calendar slots. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('ig_story_planner', $profile, $prompt);
    }

    /**
     * Build Instagram Carousel content strategy.
     */
    public function analyzeInstagramCarousel(string $topic, string $niche = '', string $slideCount = ''): array
    {
        $nicheLine = $niche ? " Niche: {$niche}." : '';
        $slideLine = $slideCount ? " Preferred slides: {$slideCount}." : '';

        $prompt = "You are an Instagram carousel content expert specializing in high-save, high-share carousel posts. Build carousel strategies for this topic.\n\n"
            . "Topic: {$topic}{$nicheLine}{$slideLine}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"carousel_score":0,'
            . '"carousel_templates":[{"title":"","hook_slide":"","slides":[{"slide_num":1,"headline":"","content":"","visual_tip":""}],"cta_slide":"","estimated_saves":""}],'
            . '"design_tips":[{"tip":"","impact":"high|medium|low"}],'
            . '"caption_templates":[{"style":"","caption":"","hashtags":[""]}],'
            . '"posting_strategy":{"best_times":"","frequency":"","series_ideas":[""]},'
            . '"tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nGenerate 3 carousel templates (each with 5-10 slides), 4-5 design tips, and 3 caption templates. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('ig_carousel_builder', $topic, $prompt);
    }

    /**
     * Find Instagram collaboration partners.
     */
    public function analyzeInstagramCollab(string $profile, string $niche = '', string $followerCount = '', string $youtubeChannel = ''): array
    {
        $nicheLine = $niche ? " Niche: {$niche}." : '';
        $fcLine = $followerCount ? " Follower count: {$followerCount}." : '';

        $ytContextBlock = '';
        if ($youtubeChannel) {
            $ytContext = $this->fetchYouTubeChannelContext($youtubeChannel);
            if ($ytContext) {
                $ytContextBlock = "\n\n" . $ytContext['context_text'] . "\n\nUse YouTube collaboration data to find Instagram partners. Cross-reference YouTube collab patterns with Instagram opportunities.";
            }
        }

        $prompt = "You are an Instagram collaboration strategist. Find ideal collab partners and build outreach strategies.\n\n"
            . "Profile: {$profile}{$nicheLine}{$fcLine}{$ytContextBlock}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"collab_score":0,'
            . '"creator_matches":[{"handle":"","followers":"","niche":"","engagement_rate":"","overlap_score":0,"collab_idea":"","collab_format":"reels|carousel|story|live|post"}],'
            . '"outreach_templates":[{"approach":"","message":"","key_points":[""]}],'
            . '"collab_formats":[{"format":"","benefits":"","effort":"low|medium|high","reach_multiplier":""}],'
            . '"strategy":{"frequency":"","content_split":"","cross_promotion":""},'
            . '"tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nGenerate 5-6 creator matches, 3 outreach templates, and 4-5 collab formats. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('ig_collab_matcher', $profile, $prompt);
    }

    /**
     * Optimize Instagram Link-in-Bio.
     */
    public function analyzeInstagramLinkBio(string $profile, string $currentLinks = ''): array
    {
        $linksLine = $currentLinks ? "\nCurrent link-in-bio URLs:\n{$currentLinks}" : '';

        $prompt = "You are an Instagram bio and link-in-bio conversion expert. Analyze and optimize the bio and link strategy for maximum conversions.\n\n"
            . "Profile: {$profile}{$linksLine}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"bio_score":0,'
            . '"bio_analysis":{"current_assessment":"","improvements":[""],"keyword_usage":"","cta_effectiveness":""},'
            . '"link_structure":[{"position":1,"link_type":"","label":"","purpose":"","expected_ctr":""}],'
            . '"conversion_tips":[{"tip":"","impact":"high|medium|low","effort":"low|medium|high"}],'
            . '"funnel_design":{"awareness_links":[""],"consideration_links":[""],"conversion_links":[""]},'
            . '"ab_test_ideas":[{"element":"","variant_a":"","variant_b":"","hypothesis":""}],'
            . '"tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nGenerate 5-6 link structure recommendations, 4-5 conversion tips, and 3-4 A/B test ideas. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('ig_link_bio', $profile, $prompt);
    }

    /**
     * Design Instagram DM automation strategy.
     */
    public function analyzeInstagramDmAutomation(string $profile, string $productType = '', string $audienceSize = ''): array
    {
        $ptLine = $productType ? " Product/service type: {$productType}." : '';
        $asLine = $audienceSize ? " Audience size: {$audienceSize}." : '';

        $prompt = "You are an Instagram DM automation strategist specializing in keyword-triggered funnels and lead generation. Design a DM automation strategy.\n\n"
            . "Profile: {$profile}{$ptLine}{$asLine}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"automation_score":0,'
            . '"keyword_triggers":[{"keyword":"","response_type":"","message_template":"","conversion_goal":""}],'
            . '"funnel_blueprints":[{"name":"","trigger":"","steps":[{"step":1,"action":"","delay":"","message":""}],"expected_conversion":""}],'
            . '"compliance":{"rules":[""],"best_practices":[""],"risks":[""]},'
            . '"revenue_estimate":{"leads_per_month":"","conversion_rate":"","estimated_revenue":""},'
            . '"tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nGenerate 5-6 keyword triggers, 2-3 funnel blueprints (each with 3-5 steps), and compliance guidance. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('ig_dm_automation', $profile, $prompt);
    }

    /**
     * Track and optimize Instagram hashtag strategy.
     */
    public function analyzeInstagramHashtag(string $niche, string $contentType = '', string $youtubeChannel = ''): array
    {
        $ctLine = $contentType ? " Content type: {$contentType}." : '';

        $ytContextBlock = '';
        if ($youtubeChannel) {
            $ytContext = $this->fetchYouTubeChannelContext($youtubeChannel);
            if ($ytContext) {
                $ytContextBlock = "\n\n" . $ytContext['context_text'] . "\n\nMap YouTube tags and content themes to Instagram hashtag opportunities. Use real YouTube data to inform hashtag relevance.";
            }
        }

        $prompt = "You are an Instagram hashtag expert specializing in discoverability and reach optimization. Build a comprehensive hashtag strategy.\n\n"
            . "Niche: {$niche}{$ctLine}{$ytContextBlock}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"hashtag_score":0,'
            . '"primary_hashtags":[{"tag":"#tag","avg_reach":"","competition":"low|medium|high","trending":true}],'
            . '"secondary_hashtags":[{"tag":"#tag","avg_reach":"","purpose":""}],'
            . '"hashtag_sets":[{"name":"Set name","tags":["#tag1","#tag2"],"best_for":"","estimated_reach":""}],'
            . '"banned_shadowban_check":[{"tag":"#tag","status":"safe|risky|banned","risk":""}],'
            . '"strategy_tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nGenerate 8-10 primary hashtags, 5-8 secondary, 3-4 hashtag sets, and 4-5 shadowban checks. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('ig_hashtag_tracker', $niche, $prompt);
    }

    /**
     * Analyze Instagram aesthetic and brand cohesion.
     */
    public function analyzeInstagramAesthetic(string $profile, string $brandStyle = ''): array
    {
        $bsLine = $brandStyle ? " Brand style: {$brandStyle}." : '';

        $prompt = "You are an Instagram visual branding expert. Analyze this profile's aesthetic consistency and brand cohesion.\n\n"
            . "Profile: {$profile}{$bsLine}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"aesthetic_score":0,'
            . '"grid_analysis":{"consistency_score":0,"color_palette":[""],"dominant_style":"","mood":""},'
            . '"brand_cohesion":{"logo_presence":"","font_consistency":"","color_adherence":"","voice_consistency":""},'
            . '"content_mix":{"reels_pct":0,"carousels_pct":0,"stories_pct":0,"posts_pct":0,"recommended_mix":{"reels":0,"carousels":0,"stories":0,"posts":0}},'
            . '"improvement_areas":[{"area":"","current":"","recommended":"","impact":"high|medium|low"}],'
            . '"style_recommendations":[{"element":"","suggestion":"","examples":[""]}],'
            . '"tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nGenerate 4-5 improvement areas and 4-5 style recommendations. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('ig_aesthetic_analyzer', $profile, $prompt);
    }

    /**
     * Optimize Instagram Shopping tags and strategy.
     */
    public function analyzeInstagramShopping(string $profile, string $productType = '', string $priceRange = ''): array
    {
        $ptLine = $productType ? " Product type: {$productType}." : '';
        $prLine = $priceRange ? " Price range: {$priceRange}." : '';

        $prompt = "You are an Instagram Shopping optimization expert. Analyze this shop and provide tagging, product, and content strategies.\n\n"
            . "Profile/Shop: {$profile}{$ptLine}{$prLine}\n\n"
            . "Provide analysis as JSON:\n"
            . '{"shop_score":0,'
            . '"shop_overview":{"estimated_revenue":"","product_visibility":"","tag_usage":"","conversion_rate":""},'
            . '"tagging_strategy":[{"content_type":"reels|carousel|post|story","tag_placement":"","products_per_post":"","best_practices":""}],'
            . '"product_recommendations":[{"product":"","category":"","price_range":"","demand":"high|medium|low","competition":"low|medium|high","margin":""}],'
            . '"content_integration":[{"format":"reels|carousel|story|live","shopping_feature":"","conversion_tip":"","example":""}],'
            . '"pricing_optimization":{"assessment":"","recommendations":[""]},'
            . '"tips":["tip1","tip2","tip3"]'
            . '}'
            . "\n\nGenerate 4 tagging strategies, 5-6 product recommendations, and 4 content integrations. Respond with ONLY valid JSON.";

        return $this->executeAnalysis('ig_shopping_optimizer', $profile, $prompt);
    }

    /**
     * Core execution: call AI, parse result, save history.
     */
    protected function executeAnalysis(string $toolKey, string $input, string $prompt, int $maxTokens = 0): array
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

        $this->lastHistory = AiToolHistory::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'tool' => $toolKey,
            'platform' => config("appaitools.enterprise_tools.{$configKey}.platform", 'youtube'),
            'title' => $input,
            'input_data' => ['url' => $input, 'tool' => $toolKey],
            'status' => 2,
        ]);
        $history = $this->lastHistory;

        try {
            if ($maxTokens > 0) {
                $provider = get_option('ai_platform', 'openai');
                $aiResult = AI::processWithOverride($prompt, $provider, null, 'text', [
                    'maxResult' => 1,
                    'max_tokens' => $maxTokens,
                ], $teamId);
            } else {
                $aiResult = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);
            }
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
     * Parse JSON from AI response, handling markdown code blocks, truncated and malformed responses.
     */
    protected function parseJson(string $text): array
    {
        $text = trim($text);

        // Remove markdown code blocks anywhere in text
        $text = preg_replace('/```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```/', '', $text);
        $text = trim($text);

        // Try direct parse
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Extract JSON object from surrounding text (AI may prefix with explanation)
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Robust repair: walk the string, skip extra closers, close unclosed openers
        $jsonStart = strpos($text, '{');
        if ($jsonStart !== false) {
            $jsonStr = substr($text, $jsonStart);
            $len = strlen($jsonStr);
            $repaired = '';
            $stack = [];
            $inString = false;
            $escape = false;

            for ($i = 0; $i < $len; $i++) {
                $ch = $jsonStr[$i];

                if ($escape) {
                    $escape = false;
                    $repaired .= $ch;
                    continue;
                }
                if ($ch === '\\' && $inString) {
                    $escape = true;
                    $repaired .= $ch;
                    continue;
                }
                if ($ch === '"') {
                    $inString = !$inString;
                    $repaired .= $ch;
                    continue;
                }
                if ($inString) {
                    $repaired .= $ch;
                    continue;
                }

                // Track openers
                if ($ch === '{' || $ch === '[') {
                    $stack[] = $ch;
                    $repaired .= $ch;
                    continue;
                }

                // For closers, only emit if they match the last opener
                if ($ch === '}') {
                    if (!empty($stack) && end($stack) === '{') {
                        array_pop($stack);
                        $repaired .= $ch;
                    }
                    // else: extra closer — skip it
                    continue;
                }
                if ($ch === ']') {
                    if (!empty($stack) && end($stack) === '[') {
                        array_pop($stack);
                        $repaired .= $ch;
                    }
                    continue;
                }

                $repaired .= $ch;
            }

            // Remove trailing incomplete value
            $repaired = rtrim($repaired, " \t\n\r,:");
            $repaired = preg_replace('/"[^"]*$/', '""', $repaired);

            // Close any remaining open structures (in reverse order)
            while (!empty($stack)) {
                $opener = array_pop($stack);
                $repaired .= ($opener === '{') ? '}' : ']';
            }

            $decoded = json_decode($repaired, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return ['raw_response' => $text, 'parse_error' => true];
    }
}
