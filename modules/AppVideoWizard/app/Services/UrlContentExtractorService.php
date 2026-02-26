<?php

namespace Modules\AppVideoWizard\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppAIContents\Services\WebScraperService;
use Modules\AppAITools\Services\YouTubeDataService;

class UrlContentExtractorService
{
    /**
     * Auto-detect source type from URL domain.
     */
    public function detectSourceType(string $url): string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        // YouTube
        if (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')) {
            return 'youtube_video';
        }

        // LinkedIn
        if (str_contains($host, 'linkedin.com')) {
            return 'linkedin';
        }

        // Twitter/X
        if (str_contains($host, 'twitter.com') || str_contains($host, 'x.com')) {
            return 'twitter';
        }

        // Newsletters
        $newsletterDomains = ['substack.com', 'beehiiv.com', 'mailchi.mp', 'buttondown.email', 'convertkit.com'];
        foreach ($newsletterDomains as $domain) {
            if (str_contains($host, $domain)) {
                return 'newsletter';
            }
        }

        // News outlets
        $newsDomains = [
            'bbc.com', 'bbc.co.uk', 'cnn.com', 'reuters.com', 'apnews.com',
            'theguardian.com', 'nytimes.com', 'washingtonpost.com', 'wsj.com',
            'techcrunch.com', 'theverge.com', 'arstechnica.com', 'wired.com',
            'bloomberg.com', 'cnbc.com', 'forbes.com', 'businessinsider.com',
            'aljazeera.com', 'france24.com', 'dw.com', 'bild.de', 'spiegel.de',
            'ynet.co.il', 'haaretz.com', 'timesofisrael.com',
        ];
        foreach ($newsDomains as $domain) {
            if (str_contains($host, $domain)) {
                return 'news';
            }
        }

        return 'article';
    }

    /**
     * Extract content from URL using the appropriate service.
     */
    public function extract(string $url, string $sourceType): array
    {
        Log::info('UrlContentExtractorService::extract', ['url' => $url, 'source_type' => $sourceType]);

        if ($sourceType === 'youtube_video') {
            return $this->extractYouTube($url);
        }

        return $this->extractWeb($url, $sourceType);
    }

    /**
     * Extract YouTube video data.
     */
    protected function extractYouTube(string $url): array
    {
        $ytService = app(YouTubeDataService::class);
        $videoData = $ytService->getVideoData($url);

        if (!$videoData) {
            throw new \Exception('Could not fetch YouTube video data. Check the URL and API keys.');
        }

        return [
            'source_type' => 'youtube_video',
            'url' => $url,
            'title' => $videoData['title'] ?? '',
            'text_content' => $videoData['description'] ?? '',
            'images' => !empty($videoData['thumbnail']) ? [['url' => $videoData['thumbnail'], 'source' => 'youtube']] : [],
            'meta' => [
                'channel' => $videoData['channel'] ?? '',
                'views' => $videoData['views'] ?? 0,
                'likes' => $videoData['likes'] ?? 0,
                'duration' => $videoData['duration'] ?? '',
                'tags' => $videoData['tags'] ?? [],
                'published_at' => $videoData['published_at'] ?? '',
            ],
        ];
    }

    /**
     * Extract web content using WebScraperService.
     */
    protected function extractWeb(string $url, string $sourceType): array
    {
        $scraper = app(WebScraperService::class);
        $data = $scraper->scrape($url);

        return [
            'source_type' => $sourceType,
            'url' => $url,
            'title' => $data['title'] ?? '',
            'text_content' => $data['text_content'] ?? '',
            'images' => $data['images'] ?? [],
            'meta' => $data['meta'] ?? [],
            'logo' => $data['logo'] ?? null,
            'colors' => $data['colors'] ?? [],
            'fonts' => $data['fonts'] ?? [],
            'language_code' => $data['language_code'] ?? '',
        ];
    }

    /**
     * AI-analyze extracted content to produce a structured content brief.
     */
    public function analyzeContent(array $extractedContent, ?string $userPrompt = null): array
    {
        $engine = get_option('story_mode_ai_engine', 'gemini');
        $model = get_option('story_mode_ai_model', 'gemini-2.5-flash');
        $teamId = auth()->user()?->team_id ?? 0;

        $title = $extractedContent['title'] ?? 'Unknown';
        $textContent = mb_substr($extractedContent['text_content'] ?? '', 0, 3000);
        $sourceType = $extractedContent['source_type'] ?? 'article';
        $meta = json_encode($extractedContent['meta'] ?? []);

        $prompt = <<<PROMPT
You are a content analyst for a video production platform. Analyze the following content and return a structured JSON brief.

SOURCE TYPE: {$sourceType}
TITLE: {$title}
CONTENT:
{$textContent}

METADATA: {$meta}
PROMPT;

        if ($userPrompt) {
            $prompt .= "\n\nUSER'S ANGLE/INSTRUCTION: {$userPrompt}";
        }

        $prompt .= <<<'PROMPT'


Return ONLY valid JSON with this structure:
{
  "key_facts": [{"fact": "...", "importance": 1-10}],
  "narrative_angle": "explainer|news|opinion|promo|tutorial|entertainment",
  "suggested_title": "Short catchy title for the video",
  "content_category": "technology|business|health|science|politics|entertainment|lifestyle|sports|education|other",
  "tone": "formal|casual|dramatic|inspirational|humorous|urgent|conversational",
  "target_audience": "Brief description of ideal viewer",
  "summary": "2-3 sentence core message of the content"
}

Extract 5-10 key facts, ranked by importance. The suggested_title should be punchy and video-friendly (under 60 characters). The narrative_angle should reflect the best way to present this content as a short video.
PROMPT;

        $response = \AI::processWithOverride(
            $prompt,
            $engine,
            $model,
            'text',
            ['temperature' => 0.5, 'max_tokens' => 2000],
            $teamId
        );

        if (!empty($response['error'])) {
            throw new \Exception('AI analysis failed: ' . $response['error']);
        }

        $text = $response['data'][0] ?? $response['text'] ?? $response['result'] ?? '';
        $json = $this->extractJson($text);

        if (!$json) {
            Log::warning('UrlContentExtractorService::analyzeContent - Failed to parse AI response', [
                'response_preview' => mb_substr($text, 0, 300),
            ]);
            // Return minimal fallback
            return [
                'key_facts' => [['fact' => $extractedContent['title'] ?? 'Content from URL', 'importance' => 8]],
                'narrative_angle' => 'explainer',
                'suggested_title' => $extractedContent['title'] ?? 'Video Summary',
                'content_category' => 'other',
                'tone' => 'conversational',
                'target_audience' => 'General audience',
                'summary' => mb_substr($extractedContent['text_content'] ?? '', 0, 200),
            ];
        }

        return $json;
    }

    /**
     * Build an enhanced prompt for StoryModeScriptService from content brief + user prompt.
     */
    public function buildEnhancedPrompt(array $contentBrief, ?string $userPrompt = null): string
    {
        $title = $contentBrief['suggested_title'] ?? 'Video Summary';
        $summary = $contentBrief['summary'] ?? '';
        $tone = $contentBrief['tone'] ?? 'conversational';
        $angle = $contentBrief['narrative_angle'] ?? 'explainer';
        $audience = $contentBrief['target_audience'] ?? 'general audience';

        // Top 8 facts by importance
        $facts = $contentBrief['key_facts'] ?? [];
        usort($facts, fn($a, $b) => ($b['importance'] ?? 0) <=> ($a['importance'] ?? 0));
        $topFacts = array_slice($facts, 0, 8);
        $factsText = implode("\n", array_map(
            fn($f, $i) => ($i + 1) . ". " . ($f['fact'] ?? ''),
            $topFacts,
            array_keys($topFacts)
        ));

        $prompt = "Create a narrated video script about: {$title}\n\n";
        $prompt .= "CORE MESSAGE: {$summary}\n\n";
        $prompt .= "KEY FACTS:\n{$factsText}\n\n";
        $prompt .= "STYLE: {$angle} format, {$tone} tone, for {$audience}.\n";

        if ($userPrompt) {
            $prompt .= "\nADDITIONAL DIRECTION: {$userPrompt}\n";
        }

        $prompt .= "\nMake it engaging, visual, and suitable for a short narrated video (30-60 seconds). "
                 . "Focus on the most compelling facts. Use vivid language that pairs well with visual imagery.";

        return $prompt;
    }

    /**
     * Extract JSON from AI response text (handles markdown code fences).
     */
    protected function extractJson(string $text): ?array
    {
        // Try direct parse
        $decoded = json_decode($text, true);
        if ($decoded !== null) {
            return $decoded;
        }

        // Try extracting from markdown code fence
        if (preg_match('/```(?:json)?\s*\n?([\s\S]*?)\n?```/', $text, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        // Try finding JSON object in text
        if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        return null;
    }
}
