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
     * Extracts the TOPIC/SUBJECT — not a description of the source itself.
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
You are a content researcher for a video production platform. Your job is to extract the REAL TOPIC from source material — the people, events, ideas, and stories WITHIN the content — NOT to describe the source itself.

CRITICAL RULES:
- If the source is a YouTube video, the topic is what the video is ABOUT (the artist, the song, the event, the subject matter), NOT the video itself (never mention views, likes, 4K quality, thumbnails, subscribe, video duration, or "this video").
- If the source is a news article, the topic is the EVENT or STORY being reported, NOT "this article discusses..."
- If the source is a social media post, the topic is the IDEA or OPINION expressed, NOT "this post says..."
- Extract facts about the SUBJECT MATTER, not about the source medium.
- The output will be used to create an ORIGINAL narrated video — not a review or summary of the source.

SOURCE MATERIAL:
Title: {$title}
Content: {$textContent}
Context: {$meta}
PROMPT;

        if ($userPrompt) {
            $prompt .= "\n\nUSER'S CREATIVE DIRECTION: {$userPrompt}";
        }

        $prompt .= <<<'PROMPT'


Return ONLY valid JSON with this structure:
{
  "subject": "The real-world topic/subject this content is about (a person, event, idea, phenomenon — NOT the source itself)",
  "key_facts": [{"fact": "...", "importance": 1-10}],
  "narrative_angle": "explainer|news|opinion|story|tutorial|entertainment|biography|cultural|historical",
  "suggested_title": "Short catchy title about the SUBJECT (never reference the source)",
  "content_category": "technology|business|health|science|politics|entertainment|lifestyle|sports|education|culture|music|history|other",
  "tone": "formal|casual|dramatic|inspirational|humorous|urgent|conversational|nostalgic|mysterious",
  "target_audience": "Brief description of ideal viewer",
  "summary": "2-3 sentence summary about the SUBJECT/TOPIC (not about the source material)"
}

Rules for key_facts:
- Extract 5-10 facts about the SUBJECT, not about the source medium
- Include interesting backstory, context, and lesser-known details
- For music: artist history, song creation story, cultural impact, fan reactions
- For news: the actual event, key players, consequences, broader context
- For people: biography highlights, achievements, controversies, legacy
- Rank by how interesting/compelling each fact would be in a video narrative
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
                'subject' => $extractedContent['title'] ?? 'Unknown topic',
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
     * Creates ORIGINAL narration about the topic — never references the source.
     */
    public function buildEnhancedPrompt(array $contentBrief, ?string $userPrompt = null): string
    {
        $subject = $contentBrief['subject'] ?? $contentBrief['suggested_title'] ?? 'this topic';
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

        $prompt = "Create an ORIGINAL narrated video script about: {$subject}\n\n";
        $prompt .= "VIDEO TITLE: {$title}\n\n";
        $prompt .= "CORE NARRATIVE: {$summary}\n\n";
        $prompt .= "RESEARCH FACTS (use as source material, weave into the narrative naturally):\n{$factsText}\n\n";
        $prompt .= "STYLE: {$angle} format, {$tone} tone, for {$audience}.\n\n";

        if ($userPrompt) {
            $prompt .= "USER'S CREATIVE DIRECTION: {$userPrompt}\n\n";
        }

        $prompt .= "CRITICAL RULES:\n";
        $prompt .= "- Write as if YOU are telling this story — original narration, not a summary of something else.\n";
        $prompt .= "- NEVER mention or reference: the source URL, article, video, post, YouTube, website, channel, \"this video\", \"click the link\", \"subscribe\", \"check out\", video quality, view counts, or any platform.\n";
        $prompt .= "- NEVER use promotional language like \"grab your copy\", \"follow the link\", \"available now\".\n";
        $prompt .= "- DO tell a compelling story using the facts provided. Add context, emotion, and vivid imagery.\n";
        $prompt .= "- Make it sound like a mini-documentary narrator, NOT like someone describing a webpage.\n";
        $prompt .= "- Focus on the most compelling human angle — what makes this story interesting to PEOPLE.\n";
        $prompt .= "- Use vivid, visual language that pairs well with cinematic imagery.\n";
        $prompt .= "- Target 30-60 seconds of spoken narration.\n";

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
