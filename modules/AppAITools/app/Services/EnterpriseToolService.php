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

    // ── TikTok Tools ──────────────────────────────────────────────

    /**
     * Build TikTok hashtag strategy for a niche.
     */
    public function analyzeTiktokHashtagStrategy(string $niche, string $contentType = ''): array
    {
        $ctLine = $contentType ? " Content type: {$contentType}." : '';

        $prompt = "You are a TikTok marketing expert specializing in hashtag strategy and discoverability. "
            . "Analyze the niche and build a comprehensive hashtag strategy.\n\n"
            . "Niche: {$niche}{$ctLine}\n\n"
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
    public function analyzeTiktokPostingTime(string $profile, string $timezone = '', string $contentType = ''): array
    {
        $tzLine = $timezone ? " Timezone: {$timezone}." : '';
        $ctLine = $contentType ? " Content type: {$contentType}." : '';

        $prompt = "You are a TikTok analytics expert specializing in posting optimization. Analyze this creator's optimal posting schedule.\n\n"
            . "Profile: {$profile}{$tzLine}{$ctLine}\n\n"
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
    public function analyzeTiktokHook(string $hookText, string $niche = ''): array
    {
        $nicheLine = $niche ? " Niche: {$niche}." : '';

        $prompt = "You are a TikTok content expert specializing in viewer retention and hook psychology. Analyze this hook and provide improvements.\n\n"
            . "Hook text: {$hookText}{$nicheLine}\n\n"
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
    public function analyzeTiktokViralPotential(string $contentDescription, string $niche = '', string $followerCount = ''): array
    {
        $nicheLine = $niche ? " Niche: {$niche}." : '';
        $fcLine = $followerCount ? " Follower count: {$followerCount}." : '';

        $prompt = "You are a TikTok viral content analyst. Predict the viral potential of this content concept and provide optimization suggestions.\n\n"
            . "Content concept: {$contentDescription}{$nicheLine}{$fcLine}\n\n"
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
