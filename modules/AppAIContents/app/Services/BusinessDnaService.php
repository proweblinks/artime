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

    public function analyzeWebsite(string $url, int $teamId, ?int $dnaId = null): ContentBusinessDna
    {
        // Find existing DNA record (created by Livewire) or create new one
        $dna = $dnaId ? ContentBusinessDna::find($dnaId) : null;
        if (!$dna) {
            $dna = ContentBusinessDna::create([
                'team_id' => $teamId,
                'website_url' => $url,
                'status' => 'analyzing',
            ]);
        }

        try {
            // Scrape the website
            $scraped = $this->scraper->scrape($url);
            $dna->raw_scrape_data = $scraped;
            $dna->save();

            // Use AI to analyze the scraped content
            $analysis = $this->aiAnalyze($scraped, $teamId);

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
                'language' => $analysis['language'] ?? 'English',
                'language_code' => $analysis['language_code'] ?? ($scraped['language_code'] ?: 'en'),
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

    protected function aiAnalyze(array $scraped, int $teamId = 0): array
    {
        $textContent = mb_substr($scraped['text_content'] ?? '', 0, 3000);
        $title = $scraped['title'] ?? '';
        $meta = $scraped['meta'] ?? [];
        $description = $meta['description'] ?? $meta['og:description'] ?? '';
        $scrapedColors = !empty($scraped['colors']) ? json_encode($scraped['colors']) : '[]';
        $htmlLang = $scraped['language_code'] ?? '';
        $langHint = $htmlLang ? "HTML lang attribute detected: \"{$htmlLang}\"." : 'No HTML lang attribute found.';

        $prompt = <<<PROMPT
You are a brand identity expert. Analyze this website and extract a rich, detailed brand identity profile.

Website title: {$title}
Meta description: {$description}
Colors found on site: {$scrapedColors}
{$langHint}
Page content (truncated): {$textContent}

Instructions:
- language: Detect the PRIMARY language of the website content. Return the full English name of the language (e.g. "English", "Hebrew", "Spanish", "Japanese", "Arabic", "French", "German", "Portuguese", "Russian", "Chinese", "Korean", "Italian", "Dutch", "Turkish", "Thai", "Vietnamese", etc.)
- language_code: Return the ISO 639-1 two-letter language code (e.g. "en", "he", "es", "ja", "ar", "fr", "de", "pt", "ru", "zh", "ko", "it", "nl", "tr", "th", "vi")
- brand_name: The official brand or company name (not the page title)
- tagline: Extract the actual tagline from the site, or craft one IN THE DETECTED LANGUAGE that captures the brand's core promise in under 10 words
- colors: Return 4-6 hex colors that represent the brand. Prioritize colors actually used on the site. Include the primary brand color first, then secondary, accent, and neutral colors
- fonts: Identify fonts used on the site. Return 1-2 fonts with their CSS category (sans-serif, serif, monospace, display)
- brand_values: Return exactly 4 core values IN THE DETECTED LANGUAGE. Each should be a single specific word
- brand_aesthetic: Return exactly 5 compound descriptive terms using hyphens IN THE DETECTED LANGUAGE. Each term should combine two visual/design qualities. Be specific to this brand
- brand_tone: Return exactly 4 compound voice/communication descriptors using hyphens IN THE DETECTED LANGUAGE. Capture the nuance of how this brand communicates
- business_overview: Write a concise 2-3 sentence overview IN THE DETECTED LANGUAGE of what the business does, who it serves, and its key differentiator

CRITICAL: All text fields (tagline, brand_values, brand_aesthetic, brand_tone, business_overview) MUST be written in the detected language, NOT in English (unless the site is in English).

Return a valid JSON object with these exact keys:
{
    "language": "English name of the language",
    "language_code": "xx",
    "brand_name": "string",
    "tagline": "string in detected language",
    "colors": ["#hex1", "#hex2", "#hex3", "#hex4"],
    "fonts": [{"name": "FontName", "category": "sans-serif"}],
    "brand_values": ["value1", "value2", "value3", "value4"],
    "brand_aesthetic": ["compound-term1", "compound-term2", "compound-term3", "compound-term4", "compound-term5"],
    "brand_tone": ["compound-tone1", "compound-tone2", "compound-tone3", "compound-tone4"],
    "business_overview": "string in detected language"
}

Only return the JSON, no other text.
PROMPT;

        try {
            $result = AI::process($prompt, 'text', ['maxResult' => 1], $teamId);
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
        $aesthetics = $this->arrayToString($dna->brand_aesthetic);
        $tone = $this->arrayToString($dna->brand_tone);
        $language = $dna->language ?? 'English';

        $prompt = <<<PROMPT
You are a creative marketing strategist. Based on this brand identity, suggest 3 compelling social media campaign ideas that are specific to this brand and ready to execute.

Brand: {$dna->brand_name}
Tagline: {$dna->tagline}
Values: {$this->arrayToString($dna->brand_values)}
Aesthetic: {$aesthetics}
Tone: {$tone}
Overview: {$dna->business_overview}
Language: {$language}

Requirements:
- Each campaign title should be catchy, action-oriented, and under 6 words
- Each description should be 2-3 sentences explaining the campaign concept, target audience, and expected outcome
- Campaigns should be diverse: one brand awareness, one engagement-driven, one conversion-focused
- Make them specific to THIS brand, not generic marketing advice
- IMPORTANT: Write ALL titles and descriptions in {$language}

Return a JSON array of exactly 3 objects:
[
    {"title": "Campaign Title in {$language}", "description": "2-3 sentence campaign description in {$language}"},
    {"title": "Campaign Title in {$language}", "description": "2-3 sentence campaign description in {$language}"},
    {"title": "Campaign Title in {$language}", "description": "2-3 sentence campaign description in {$language}"}
]

Only return the JSON array, no other text.
PROMPT;

        try {
            $result = AI::process($prompt, 'text', ['maxResult' => 1], $dna->team_id);
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
