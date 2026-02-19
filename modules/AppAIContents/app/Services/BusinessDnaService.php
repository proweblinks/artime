<?php

namespace Modules\AppAIContents\Services;

use AI;
use Illuminate\Support\Facades\Log;
use Modules\AppAIContents\Models\ContentBusinessDna;
use Modules\AppAIContents\Models\ContentCampaignIdea;

class BusinessDnaService
{
    protected WebScraperService $scraper;

    public function __construct(WebScraperService $scraper)
    {
        $this->scraper = $scraper;
    }

    public function analyzeWebsite(string $url, int $teamId): ContentBusinessDna
    {
        // Create or update DNA record
        $dna = ContentBusinessDna::updateOrCreate(
            ['team_id' => $teamId],
            [
                'website_url' => $url,
                'status' => 'analyzing',
            ]
        );

        try {
            // Scrape the website
            $scraped = $this->scraper->scrape($url);
            $dna->raw_scrape_data = $scraped;
            $dna->save();

            // Use AI to analyze the scraped content
            $analysis = $this->aiAnalyze($scraped);

            // Update DNA with analysis results
            $dna->update([
                'brand_name' => $analysis['brand_name'] ?? $scraped['title'] ?? '',
                'colors' => !empty($analysis['colors']) ? $analysis['colors'] : $scraped['colors'],
                'fonts' => !empty($analysis['fonts']) ? $analysis['fonts'] : $scraped['fonts'],
                'tagline' => $analysis['tagline'] ?? '',
                'brand_values' => $analysis['brand_values'] ?? [],
                'brand_aesthetic' => $analysis['brand_aesthetic'] ?? [],
                'brand_tone' => $analysis['brand_tone'] ?? [],
                'business_overview' => $analysis['business_overview'] ?? '',
                'images' => array_map(fn($url) => ['url' => $url, 'caption' => ''], array_slice($scraped['images'], 0, 12)),
                'status' => 'ready',
            ]);

            // Generate DNA-based campaign suggestions
            $this->generateDnaSuggestions($dna);

            return $dna->fresh();
        } catch (\Throwable $e) {
            Log::error('BusinessDnaService::analyzeWebsite failed', ['error' => $e->getMessage()]);
            $dna->update(['status' => 'failed']);
            throw $e;
        }
    }

    protected function aiAnalyze(array $scraped): array
    {
        $textContent = mb_substr($scraped['text_content'] ?? '', 0, 3000);
        $title = $scraped['title'] ?? '';
        $meta = $scraped['meta'] ?? [];
        $description = $meta['description'] ?? $meta['og:description'] ?? '';

        $prompt = <<<PROMPT
Analyze this website and extract the brand identity. Return a valid JSON object with these exact keys:

Website title: {$title}
Meta description: {$description}
Page content (truncated): {$textContent}

Return JSON:
{
    "brand_name": "The brand/company name",
    "tagline": "A catchy tagline that represents the brand",
    "colors": ["#hex1", "#hex2", "#hex3", "#hex4"],
    "fonts": [{"name": "FontName", "category": "sans-serif"}],
    "brand_values": ["value1", "value2", "value3", "value4"],
    "brand_aesthetic": ["aesthetic1", "aesthetic2", "aesthetic3"],
    "brand_tone": ["tone1", "tone2", "tone3"],
    "business_overview": "2-3 sentence overview of what the business does"
}

Only return the JSON, no other text.
PROMPT;

        try {
            $result = AI::process($prompt, 'text', ['maxResult' => 1]);
            $text = $result['data'][0] ?? '';

            // Extract JSON from response
            if (preg_match('/\{[\s\S]*\}/', $text, $match)) {
                return json_decode($match[0], true) ?? [];
            }
        } catch (\Throwable $e) {
            Log::warning('AI DNA analysis failed, using scraped defaults', ['error' => $e->getMessage()]);
        }

        return [];
    }

    public function generateDnaSuggestions(ContentBusinessDna $dna): array
    {
        $prompt = <<<PROMPT
Based on this brand identity, suggest 3 marketing campaign ideas.

Brand: {$dna->brand_name}
Tagline: {$dna->tagline}
Values: {$this->arrayToString($dna->brand_values)}
Overview: {$dna->business_overview}

Return a JSON array of exactly 3 objects:
[
    {"title": "Campaign Title", "description": "Brief campaign description (1-2 sentences)"},
    {"title": "Campaign Title", "description": "Brief campaign description (1-2 sentences)"},
    {"title": "Campaign Title", "description": "Brief campaign description (1-2 sentences)"}
]

Only return the JSON array, no other text.
PROMPT;

        try {
            $result = AI::process($prompt, 'text', ['maxResult' => 1]);
            $text = $result['data'][0] ?? '';

            if (preg_match('/\[[\s\S]*\]/', $text, $match)) {
                $suggestions = json_decode($match[0], true) ?? [];

                // Delete old DNA suggestions
                ContentCampaignIdea::where('team_id', $dna->team_id)
                    ->where('dna_id', $dna->id)
                    ->where('is_dna_suggestion', true)
                    ->delete();

                $ideas = [];
                foreach (array_slice($suggestions, 0, 3) as $s) {
                    $ideas[] = ContentCampaignIdea::create([
                        'team_id' => $dna->team_id,
                        'dna_id' => $dna->id,
                        'title' => $s['title'] ?? 'Untitled',
                        'description' => $s['description'] ?? '',
                        'is_dna_suggestion' => true,
                    ]);
                }

                return $ideas;
            }
        } catch (\Throwable $e) {
            Log::warning('AI DNA suggestions failed', ['error' => $e->getMessage()]);
        }

        return [];
    }

    protected function arrayToString(?array $arr): string
    {
        return implode(', ', $arr ?? []);
    }
}
