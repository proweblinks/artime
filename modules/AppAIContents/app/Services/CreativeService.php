<?php

namespace Modules\AppAIContents\Services;

use AI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\AppAIContents\Models\ContentBusinessDna;
use Modules\AppAIContents\Models\ContentCampaign;
use Modules\AppAIContents\Models\ContentCreative;
use Modules\AppAIContents\Models\ContentCreativeVersion;

class CreativeService
{
    public function generateCreatives(ContentCampaign $campaign, int $count = 4): void
    {
        $dna = $campaign->dna;

        for ($i = 0; $i < $count; $i++) {
            try {
                // Generate image
                $imageResult = $this->generateImage($campaign, $dna, $i);

                // Generate text overlay content
                $textContent = $this->generateTextContent($campaign, $dna, $i);

                $creative = ContentCreative::create([
                    'campaign_id' => $campaign->id,
                    'team_id' => $campaign->team_id,
                    'type' => 'image',
                    'image_path' => $imageResult['path'] ?? null,
                    'image_url' => $imageResult['url'] ?? null,
                    'header_text' => $textContent['header'] ?? '',
                    'description_text' => $textContent['description'] ?? '',
                    'cta_text' => $textContent['cta'] ?? '',
                    'sort_order' => $i,
                    'metadata' => [
                        'generation_prompt' => $imageResult['prompt'] ?? '',
                        'variation_index' => $i,
                    ],
                ]);

                // Save first version
                ContentCreativeVersion::create([
                    'creative_id' => $creative->id,
                    'version_number' => 1,
                    'image_path' => $creative->image_path,
                    'image_url' => $creative->image_url,
                    'header_text' => $creative->header_text,
                    'description_text' => $creative->description_text,
                    'cta_text' => $creative->cta_text,
                    'metadata' => $creative->metadata,
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::error("CreativeService: Failed to generate creative {$i}", ['error' => $e->getMessage()]);
            }
        }

        $campaign->update(['status' => 'ready']);
    }

    protected function generateImage(ContentCampaign $campaign, ContentBusinessDna $dna, int $variationIndex): array
    {
        $brandColors = implode(', ', $dna->colors ?? ['#03fcf4', '#1a1a2e']);
        $aesthetic = implode(', ', $dna->brand_aesthetic ?? ['modern-clean']);
        $tone = implode(', ', $dna->brand_tone ?? ['professional']);
        $variations = [
            'dramatic cinematic lighting with depth',
            'soft natural ambient light, airy feel',
            'bold high-contrast with vivid colors',
            'subtle gradient tones, elegant and refined',
        ];
        $variation = $variations[$variationIndex % count($variations)];

        $aspectLabel = match($campaign->aspect_ratio) {
            '1:1' => 'square composition',
            '4:5' => 'portrait feed composition (4:5)',
            default => 'vertical story composition (9:16)',
        };

        $prompt = "Create a premium social media marketing visual for the campaign '{$campaign->title}'. "
            . "Brand: {$dna->brand_name}. Visual style: {$aesthetic}. Mood: {$variation}. "
            . "Use brand colors ({$brandColors}) as inspiration for the color palette. "
            . "The image should feel {$tone}. {$aspectLabel}. "
            . "Professional quality, editorial grade. Do NOT include any text or words in the image.";

        $aspectMap = [
            '9:16' => ['width' => 720, 'height' => 1280],
            '1:1'  => ['width' => 1024, 'height' => 1024],
            '4:5'  => ['width' => 864, 'height' => 1080],
        ];

        $dimensions = $aspectMap[$campaign->aspect_ratio] ?? $aspectMap['9:16'];

        try {
            $result = AI::process($prompt, 'image', [
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'maxResult' => 1,
            ], $campaign->team_id);

            $item = $result['data'][0] ?? null;

            if ($item) {
                // Handle base64 response (OpenAI/Gemini)
                if (is_array($item) && isset($item['b64_json'])) {
                    $ext = str_contains($item['mimeType'] ?? '', 'png') ? 'png' : 'jpg';
                    $contents = base64_decode($item['b64_json']);
                    $filename = "content-studio/{$campaign->team_id}/" . uniqid() . ".{$ext}";
                    Storage::disk('public')->put($filename, $contents);
                    $url = url('/public/storage/' . $filename);
                    return ['path' => $filename, 'url' => $url, 'prompt' => $prompt];
                }

                // Handle URL response (FAL.AI)
                $imageUrl = is_array($item) ? ($item['url'] ?? null) : $item;
                if ($imageUrl && is_string($imageUrl)) {
                    $path = $this->downloadAndStore($imageUrl, $campaign->team_id);
                    return ['path' => $path, 'url' => $imageUrl, 'prompt' => $prompt];
                }
            }
        } catch (\Throwable $e) {
            Log::error('CreativeService::generateImage failed', ['error' => $e->getMessage()]);
        }

        return ['path' => null, 'url' => null, 'prompt' => $prompt];
    }

    protected function generateTextContent(ContentCampaign $campaign, ContentBusinessDna $dna, int $index): array
    {
        $prompt = <<<PROMPT
You are a copywriter creating text overlays for a social media marketing creative (variation #{$index}).

Campaign: {$campaign->title}
Campaign brief: {$campaign->description}
Brand: {$dna->brand_name}
Brand tone: {$this->arrayToString($dna->brand_tone)}
Brand values: {$this->arrayToString($dna->brand_values)}

Requirements:
- header: A bold, attention-grabbing headline (max 8 words). Should stop the scroll.
- description: Supporting copy that expands on the headline (max 20 words). Concise and compelling.
- cta: A strong call-to-action (2-4 words). Action-oriented.
- Each variation (#{$index}) must use a DIFFERENT angle/approach from others

Return a JSON object:
{
    "header": "Bold headline here",
    "description": "Supporting text here",
    "cta": "Action words"
}

Only return the JSON, no other text.
PROMPT;

        try {
            $result = AI::process($prompt, 'text', ['maxResult' => 1], $campaign->team_id);
            $text = $result['data'][0] ?? '';

            if (preg_match('/\{[\s\S]*\}/', $text, $match)) {
                return json_decode($match[0], true) ?? [];
            }
        } catch (\Throwable $e) {
            Log::warning('CreativeService: Text generation failed', ['error' => $e->getMessage()]);
        }

        return [
            'header' => $campaign->title,
            'description' => $campaign->description ?? '',
            'cta' => 'Learn More',
        ];
    }

    public function fixLayout(ContentCreative $creative): void
    {
        $campaign = $creative->campaign;
        $dna = $campaign->dna;

        // Regenerate image with slightly different prompt
        $imageResult = $this->generateImage($campaign, $dna, $creative->current_version);

        $newVersion = $creative->current_version + 1;

        // Create new version
        ContentCreativeVersion::create([
            'creative_id' => $creative->id,
            'version_number' => $newVersion,
            'image_path' => $imageResult['path'] ?? $creative->image_path,
            'image_url' => $imageResult['url'] ?? $creative->image_url,
            'header_text' => $creative->header_text,
            'description_text' => $creative->description_text,
            'cta_text' => $creative->cta_text,
            'metadata' => ['fix_layout' => true, 'prompt' => $imageResult['prompt'] ?? ''],
            'created_at' => now(),
        ]);

        $creative->update([
            'image_path' => $imageResult['path'] ?? $creative->image_path,
            'image_url' => $imageResult['url'] ?? $creative->image_url,
            'current_version' => $newVersion,
        ]);
    }

    public function helpMeWrite(string $field, ContentCreative $creative): string
    {
        $campaign = $creative->campaign;
        $dna = $campaign->dna;

        $fieldLabels = [
            'header' => 'a bold headline (max 8 words)',
            'description' => 'supporting description text (max 25 words)',
            'cta' => 'a call to action (2-4 words)',
        ];

        $fieldLabel = $fieldLabels[$field] ?? 'marketing text';

        $prompt = "Generate {$fieldLabel} for a marketing creative. "
            . "Campaign: {$campaign->title}. Brand: {$dna->brand_name}. "
            . "Tone: " . implode(', ', $dna->brand_tone ?? ['professional']) . ". "
            . "Return only the text, nothing else.";

        try {
            $result = AI::process($prompt, 'text', ['maxResult' => 1], $creative->team_id);
            return trim($result['data'][0] ?? '', " \t\n\r\"'");
        } catch (\Throwable $e) {
            Log::warning('helpMeWrite failed', ['error' => $e->getMessage()]);
            return '';
        }
    }

    public function duplicateCreative(ContentCreative $creative, string $newAspectRatio): ContentCreative
    {
        $newCreative = $creative->replicate();
        $newCreative->sort_order = $creative->sort_order + 1;
        $newCreative->current_version = 1;
        $newCreative->save();

        // Save version
        ContentCreativeVersion::create([
            'creative_id' => $newCreative->id,
            'version_number' => 1,
            'image_path' => $newCreative->image_path,
            'image_url' => $newCreative->image_url,
            'header_text' => $newCreative->header_text,
            'description_text' => $newCreative->description_text,
            'cta_text' => $newCreative->cta_text,
            'created_at' => now(),
        ]);

        // If different aspect ratio, regenerate image
        if ($newAspectRatio !== $creative->campaign->aspect_ratio) {
            // Update campaign aspect ratio reference in metadata
            $newCreative->update([
                'metadata' => array_merge($newCreative->metadata ?? [], ['aspect_ratio' => $newAspectRatio]),
            ]);
        }

        return $newCreative;
    }

    public function animateCreative(ContentCreative $creative, bool $withText = true): void
    {
        $campaign = $creative->campaign;
        $dna = $campaign->dna;

        $prompt = "Animate this marketing image for {$dna->brand_name}. "
            . "Subtle motion: camera zoom, parallax effect, floating elements. "
            . "Duration: 3-5 seconds. Smooth, professional movement.";

        try {
            $result = AI::process($prompt, 'video', [
                'image_url' => $creative->image_url,
                'maxResult' => 1,
            ], $creative->team_id);

            $videoUrl = $result['data'][0]['url'] ?? ($result['data'][0] ?? null);

            if ($videoUrl && is_string($videoUrl)) {
                $path = $this->downloadAndStore($videoUrl, $creative->team_id, 'mp4');
                $creative->update([
                    'type' => 'video',
                    'video_path' => $path,
                    'video_url' => $videoUrl,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('animateCreative failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function downloadAndStore(string $url, int $teamId, string $ext = 'png'): string
    {
        try {
            $contents = file_get_contents($url);
            $filename = "content-studio/{$teamId}/" . uniqid() . ".{$ext}";

            Storage::disk('public')->put($filename, $contents);

            return $filename;
        } catch (\Throwable $e) {
            Log::error('downloadAndStore failed', ['url' => $url, 'error' => $e->getMessage()]);
            return '';
        }
    }

    protected function arrayToString(?array $arr): string
    {
        return implode(', ', $arr ?? []);
    }
}
