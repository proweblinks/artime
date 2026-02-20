<?php

namespace Modules\AppAIContents\Services;

use AI;
use Illuminate\Support\Facades\Log;
use Modules\AppAIContents\Models\ContentBusinessDna;
use Modules\AppAIContents\Models\ContentCampaign;
use Modules\AppAIContents\Models\ContentCampaignIdea;

class CampaignService
{
    public function generateIdeas(string $prompt, ContentBusinessDna $dna, int $teamId): array
    {
        $language = $dna->language ?? 'English';

        $aiPrompt = <<<PROMPT
You are a creative marketing strategist. Generate 3 compelling campaign ideas that match the user's request while staying true to the brand identity.

User's request: {$prompt}

Brand context:
- Name: {$dna->brand_name}
- Tagline: {$dna->tagline}
- Values: {$this->arrayToString($dna->brand_values)}
- Aesthetic: {$this->arrayToString($dna->brand_aesthetic)}
- Tone: {$this->arrayToString($dna->brand_tone)}
- Overview: {$dna->business_overview}
- Language: {$language}

Requirements:
- Each title should be catchy, action-oriented, and under 6 words
- Each description should be 2-3 sentences covering the concept, target audience, and expected outcome
- Ideas should directly address the user's request while reflecting the brand's aesthetic and tone
- Make each idea a distinct creative angle on the user's request
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
            $result = AI::process($aiPrompt, 'text', ['maxResult' => 1], $teamId);
            $text = $result['data'][0] ?? '';

            if (preg_match('/\[[\s\S]*\]/', $text, $match)) {
                $parsed = json_decode($match[0], true) ?? [];
                $ideas = [];

                foreach (array_slice($parsed, 0, 3) as $item) {
                    $ideas[] = ContentCampaignIdea::create([
                        'team_id' => $teamId,
                        'dna_id' => $dna->id,
                        'title' => $item['title'] ?? 'Untitled',
                        'description' => $item['description'] ?? '',
                        'prompt' => $prompt,
                        'is_dna_suggestion' => false,
                    ]);
                }

                return $ideas;
            }
        } catch (\Throwable $e) {
            Log::error('CampaignService::generateIdeas failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return [];
    }

    public function createCampaignFromIdea(ContentCampaignIdea $idea, string $aspectRatio, int $teamId): ContentCampaign
    {
        $idea->update(['status' => 'used']);

        return ContentCampaign::create([
            'team_id' => $teamId,
            'dna_id' => $idea->dna_id,
            'title' => $idea->title,
            'description' => $idea->description,
            'prompt' => $idea->prompt,
            'aspect_ratio' => $aspectRatio,
            'status' => 'generating',
        ]);
    }

    protected function arrayToString(?array $arr): string
    {
        return implode(', ', $arr ?? []);
    }
}
