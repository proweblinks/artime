<?php

namespace Modules\AppAIContents\Services;

use AI;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
            // Step 1: Scraping website
            $this->updateProgress($dna, 1, 'Scraping website...');
            $scraped = $this->scraper->scrape($url);
            $dna->raw_scrape_data = $scraped;
            $dna->save();

            // Step 2: Analyzing brand identity
            $this->updateProgress($dna, 2, 'Analyzing brand identity...');
            $analysis = $this->aiAnalyze($scraped, $teamId);

            // Step 3: Validating & downloading images
            $this->updateProgress($dna, 3, 'Validating images...');
            $validatedImages = $this->validateAndFilterImages($scraped['images'] ?? []);
            $storedImages = $this->downloadAndStoreImages($validatedImages, $teamId);

            // Auto-download logo if found
            $logoPath = null;
            if (!empty($scraped['logo'])) {
                $logoPath = $this->downloadLogo($scraped['logo'], $teamId);
            }

            // Step 4: Generating campaign ideas
            $this->updateProgress($dna, 4, 'Generating campaign ideas...');

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
                'images' => $storedImages,
                'logo_path' => $logoPath,
            ]);

            // Generate DNA-based campaign suggestions
            $this->generateDnaSuggestions($dna);

            // Step 5: Complete
            $this->updateProgress($dna, 5, 'Complete!');
            $dna->update(['status' => 'ready']);

            return $dna->fresh();
        } catch (\Throwable $e) {
            Log::error('BusinessDnaService::analyzeWebsite failed', ['error' => $e->getMessage()]);
            $dna->update(['status' => 'failed', 'progress_message' => 'Analysis failed: ' . mb_substr($e->getMessage(), 0, 100)]);
            throw $e;
        }
    }

    protected function updateProgress(ContentBusinessDna $dna, int $step, string $message): void
    {
        $dna->update([
            'progress_step' => $step,
            'progress_message' => $message,
        ]);
    }

    /**
     * Server-side image validation via HEAD requests.
     * Filters out broken URLs, tiny files, and non-image content types.
     */
    public function validateAndFilterImages(array $candidates): array
    {
        $validated = [];

        // Limit candidates to check (avoid too many HTTP requests)
        $toCheck = array_slice($candidates, 0, 20);

        foreach ($toCheck as $candidate) {
            $url = $candidate['url'] ?? '';
            if (empty($url)) continue;

            try {
                $response = Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ARTimeBot/1.0)'])
                    ->head($url);

                if (!$response->successful()) continue;

                $contentType = $response->header('Content-Type') ?? '';
                if (!str_starts_with($contentType, 'image/')) continue;

                $contentLength = (int)($response->header('Content-Length') ?? 0);

                // Skip tiny images (< 5KB — likely icons/bullets)
                if ($contentLength > 0 && $contentLength < 5120) continue;

                $validated[] = [
                    'url' => $url,
                    'source' => $candidate['source'] ?? 'unknown',
                    'size' => $contentLength,
                ];
            } catch (\Throwable $e) {
                // Skip silently — broken URL, timeout, etc.
                continue;
            }
        }

        // Sort: og:image/twitter:image first, then by file size descending
        usort($validated, function ($a, $b) {
            $prioritySources = ['og:image', 'twitter:image'];
            $aIsPriority = in_array($a['source'], $prioritySources);
            $bIsPriority = in_array($b['source'], $prioritySources);

            if ($aIsPriority && !$bIsPriority) return -1;
            if (!$aIsPriority && $bIsPriority) return 1;

            return ($b['size'] ?? 0) <=> ($a['size'] ?? 0);
        });

        return array_slice($validated, 0, 12);
    }

    /**
     * Download validated images to local storage.
     */
    protected function downloadAndStoreImages(array $validatedImages, int $teamId): array
    {
        $stored = [];
        $disk = Storage::disk('public');

        foreach ($validatedImages as $img) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ARTimeBot/1.0)'])
                    ->get($img['url']);

                if (!$response->successful()) continue;

                $contentType = $response->header('Content-Type') ?? 'image/jpeg';
                $ext = match (true) {
                    str_contains($contentType, 'png') => 'png',
                    str_contains($contentType, 'gif') => 'gif',
                    str_contains($contentType, 'webp') => 'webp',
                    default => 'jpg',
                };

                $filename = 'img_' . uniqid() . '.' . $ext;
                $path = "content-studio/{$teamId}/images/{$filename}";

                $disk->put($path, $response->body());

                $stored[] = [
                    'url' => url('/public/storage/' . $path),
                    'path' => $path,
                    'caption' => '',
                    'source' => $img['source'] ?? 'scraped',
                ];
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $stored;
    }

    /**
     * Download logo to local storage.
     */
    protected function downloadLogo(string $logoUrl, int $teamId): ?string
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ARTimeBot/1.0)'])
                ->get($logoUrl);

            if (!$response->successful()) return null;

            $contentType = $response->header('Content-Type') ?? 'image/png';
            $ext = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'svg') => 'svg',
                str_contains($contentType, 'ico') => 'ico',
                str_contains($contentType, 'webp') => 'webp',
                default => 'png',
            };

            $path = "content-studio/{$teamId}/logo/logo_" . uniqid() . '.' . $ext;
            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Throwable $e) {
            Log::warning('Logo download failed', ['url' => $logoUrl, 'error' => $e->getMessage()]);
            return null;
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
