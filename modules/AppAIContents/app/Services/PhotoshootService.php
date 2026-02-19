<?php

namespace Modules\AppAIContents\Services;

use AI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\AppAIContents\Models\ContentPhotoshoot;

class PhotoshootService
{
    public function generateFromTemplate(
        string $productImagePath,
        string $templateId,
        string $aspectRatio,
        int $teamId,
        ?int $dnaId = null
    ): ContentPhotoshoot {
        $photoshoot = ContentPhotoshoot::create([
            'team_id' => $teamId,
            'dna_id' => $dnaId,
            'type' => 'template',
            'product_image_path' => $productImagePath,
            'template_id' => $templateId,
            'aspect_ratio' => $aspectRatio,
            'status' => 'generating',
        ]);

        try {
            $templatePrompts = $this->getTemplatePrompts();
            $templatePrompt = $templatePrompts[$templateId] ?? 'Professional product photography, studio lighting';

            $productImageUrl = Storage::disk('public')->url($productImagePath);

            $prompt = "{$templatePrompt}. Product photography, high quality, commercial grade. "
                . "Aspect ratio: {$aspectRatio}. Clean, professional composition.";

            $result = AI::process($prompt, 'image', [
                'image_url' => $productImageUrl,
                'maxResult' => 4,
            ], $teamId);

            $results = [];
            foreach (($result['data'] ?? []) as $item) {
                if (is_array($item) && isset($item['b64_json'])) {
                    $ext = str_contains($item['mimeType'] ?? '', 'png') ? 'png' : 'jpg';
                    $contents = base64_decode($item['b64_json']);
                    $filename = "content-studio/{$teamId}/" . uniqid() . ".{$ext}";
                    Storage::disk('public')->put($filename, $contents);
                    $url = url('/storage/' . $filename);
                    $results[] = ['path' => $filename, 'url' => $url];
                } else {
                    $url = is_array($item) ? ($item['url'] ?? '') : $item;
                    if ($url) {
                        $path = $this->downloadAndStore($url, $teamId);
                        $results[] = ['path' => $path, 'url' => $url];
                    }
                }
            }

            $photoshoot->update([
                'status' => 'ready',
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            Log::error('PhotoshootService::generateFromTemplate failed', ['error' => $e->getMessage()]);
            $photoshoot->update(['status' => 'failed']);
        }

        return $photoshoot->fresh();
    }

    public function generateFreeform(
        string $prompt,
        array $referenceImages,
        string $aspectRatio,
        int $teamId,
        ?int $dnaId = null
    ): ContentPhotoshoot {
        $photoshoot = ContentPhotoshoot::create([
            'team_id' => $teamId,
            'dna_id' => $dnaId,
            'type' => 'freeform',
            'prompt' => $prompt,
            'aspect_ratio' => $aspectRatio,
            'status' => 'generating',
        ]);

        try {
            $options = ['maxResult' => 4];

            if (!empty($referenceImages)) {
                $options['image_url'] = Storage::disk('public')->url($referenceImages[0]);
            }

            $result = AI::process($prompt, 'image', $options, $teamId);

            $results = [];
            foreach (($result['data'] ?? []) as $item) {
                if (is_array($item) && isset($item['b64_json'])) {
                    $ext = str_contains($item['mimeType'] ?? '', 'png') ? 'png' : 'jpg';
                    $contents = base64_decode($item['b64_json']);
                    $filename = "content-studio/{$teamId}/" . uniqid() . ".{$ext}";
                    Storage::disk('public')->put($filename, $contents);
                    $url = url('/storage/' . $filename);
                    $results[] = ['path' => $filename, 'url' => $url];
                } else {
                    $url = is_array($item) ? ($item['url'] ?? '') : $item;
                    if ($url) {
                        $path = $this->downloadAndStore($url, $teamId);
                        $results[] = ['path' => $path, 'url' => $url];
                    }
                }
            }

            $photoshoot->update([
                'status' => 'ready',
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            Log::error('PhotoshootService::generateFreeform failed', ['error' => $e->getMessage()]);
            $photoshoot->update(['status' => 'failed']);
        }

        return $photoshoot->fresh();
    }

    protected function getTemplatePrompts(): array
    {
        return [
            'studio-white' => 'Product on clean white background, soft studio lighting, minimal shadows',
            'lifestyle' => 'Product in lifestyle setting, warm natural lighting, cozy atmosphere',
            'outdoor' => 'Product in outdoor natural setting, golden hour lighting, scenic background',
            'flat-lay' => 'Flat lay product arrangement, overhead view, styled props, clean aesthetic',
            'dramatic' => 'Product with dramatic moody lighting, dark background, high contrast',
            'seasonal' => 'Product with seasonal decorations, festive mood, holiday theme',
            'minimalist' => 'Ultra-minimalist product shot, single color background, geometric shadows',
            'luxury' => 'Luxury product presentation, marble surface, gold accents, premium feel',
            'tech' => 'Product with futuristic tech aesthetic, neon accents, dark sleek background',
        ];
    }

    protected function downloadAndStore(string $url, int $teamId): string
    {
        try {
            $contents = file_get_contents($url);
            $filename = "content-studio/{$teamId}/photoshoot/" . uniqid() . ".png";
            Storage::disk('public')->put($filename, $contents);
            return $filename;
        } catch (\Throwable $e) {
            Log::error('Photoshoot downloadAndStore failed', ['error' => $e->getMessage()]);
            return '';
        }
    }
}
