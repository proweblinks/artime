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
        $aiPrompt = <<<PROMPT
Generate 3 creative marketing campaign ideas based on this prompt and brand identity.

User prompt: {$prompt}
Brand: {$dna->brand_name}
Tagline: {$dna->tagline}
Values: {$this->arrayToString($dna->brand_values)}
Aesthetic: {$this->arrayToString($dna->brand_aesthetic)}
Tone: {$this->arrayToString($dna->brand_tone)}
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
            $result = AI::process($aiPrompt, 'text', ['maxResult' => 1]);
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
