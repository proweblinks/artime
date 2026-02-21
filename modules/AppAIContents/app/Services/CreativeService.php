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
    const STYLE_PRESETS = [
        'photographic'  => ['label' => 'Photographic',  'modifier' => 'Shot on a professional DSLR camera, natural lighting, shallow depth of field, realistic photograph'],
        'cinematic'     => ['label' => 'Cinematic',      'modifier' => 'Cinematic film still, anamorphic lens, dramatic lighting, 35mm film grain, movie-like composition'],
        'golden_hour'   => ['label' => 'Golden Hour',    'modifier' => 'Golden hour warm sunlight, soft lens flare, warm tones, magic hour photography'],
        'aerial'        => ['label' => 'Aerial View',    'modifier' => 'Aerial drone photography, bird\'s eye view, sweeping landscape, high altitude perspective'],
        'lifestyle'     => ['label' => 'Lifestyle',      'modifier' => 'Lifestyle photography, candid moment, authentic feel, warm and inviting atmosphere'],
        'studio'        => ['label' => 'Studio',         'modifier' => 'Professional studio photography, clean backdrop, perfect lighting, product showcase quality'],
        'documentary'   => ['label' => 'Documentary',    'modifier' => 'Documentary style, raw and authentic, photojournalistic approach, real moments captured'],
        'minimalist'    => ['label' => 'Minimalist',     'modifier' => 'Minimalist composition, clean lines, negative space, elegant simplicity'],
        'urban'         => ['label' => 'Urban',          'modifier' => 'Urban photography, city environment, modern architecture, street-level perspective'],
        'nature'        => ['label' => 'Nature',         'modifier' => 'Nature photography, organic elements, environmental portrait, natural world beauty'],
    ];

    public function generateCreatives(ContentCampaign $campaign, int $count = 4): void
    {
        $dna = $campaign->dna;
        $sortOrder = 0;

        // Slot 1: AI-generated photorealistic image
        try {
            $this->generateAiCreative($campaign, $dna, $sortOrder++, 'photographic');
        } catch (\Throwable $e) {
            Log::error("CreativeService: AI creative failed", ['error' => $e->getMessage()]);
        }

        // Slots 2-4: Brand images from DNA library
        $brandImages = $this->selectBrandImages($dna, $campaign, $count - 1);

        foreach ($brandImages as $brandImage) {
            try {
                $this->generateBrandImageCreative($campaign, $dna, $brandImage, $sortOrder++);
            } catch (\Throwable $e) {
                Log::error("CreativeService: Brand image creative failed", ['error' => $e->getMessage()]);
            }
        }

        // Fallback: if fewer than 3 brand images available, fill remaining with AI
        $created = ContentCreative::where('campaign_id', $campaign->id)->count();
        while ($created < $count) {
            try {
                $this->generateAiCreative($campaign, $dna, $sortOrder++);
                $created++;
            } catch (\Throwable $e) {
                Log::error("CreativeService: Fallback AI creative failed", ['error' => $e->getMessage()]);
                break;
            }
        }

        $campaign->update(['status' => 'ready']);
    }

    protected function generateAiCreative(ContentCampaign $campaign, ContentBusinessDna $dna, int $sortOrder, ?string $stylePreset = null): ?ContentCreative
    {
        $imageResult = $this->generatePhotorealisticImage($campaign, $dna, $stylePreset);
        $textContent = $this->generateTextContent($campaign, $dna, $sortOrder);

        $creative = ContentCreative::create([
            'campaign_id' => $campaign->id,
            'team_id' => $campaign->team_id,
            'type' => 'image',
            'source_type' => 'ai',
            'style_preset' => $stylePreset ?? 'photographic',
            'image_path' => $imageResult['path'] ?? null,
            'image_url' => $imageResult['url'] ?? null,
            'header_text' => $textContent['header'] ?? '',
            'description_text' => $textContent['description'] ?? '',
            'cta_text' => $textContent['cta'] ?? '',
            'sort_order' => $sortOrder,
            'metadata' => [
                'generation_prompt' => $imageResult['prompt'] ?? '',
                'variation_index' => $sortOrder,
            ],
        ]);

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

        return $creative;
    }

    protected function generateBrandImageCreative(ContentCampaign $campaign, ContentBusinessDna $dna, array $brandImage, int $sortOrder): ?ContentCreative
    {
        $dimensions = $this->getAspectDimensions($campaign->aspect_ratio);
        $croppedPath = $this->smartCropImage($brandImage['path'], $dimensions['width'], $dimensions['height'], $campaign->team_id);

        $imagePath = $croppedPath ?? $brandImage['path'];
        $imageUrl = url('/public/storage/' . $imagePath);

        $textContent = $this->generateTextContent($campaign, $dna, $sortOrder);

        $creative = ContentCreative::create([
            'campaign_id' => $campaign->id,
            'team_id' => $campaign->team_id,
            'type' => 'image',
            'source_type' => 'brand_image',
            'source_image_path' => $brandImage['path'],
            'image_path' => $imagePath,
            'image_url' => $imageUrl,
            'header_text' => $textContent['header'] ?? '',
            'description_text' => $textContent['description'] ?? '',
            'cta_text' => $textContent['cta'] ?? '',
            'sort_order' => $sortOrder,
            'metadata' => [
                'brand_image_caption' => $brandImage['caption'] ?? '',
                'brand_image_source' => $brandImage['source'] ?? '',
                'variation_index' => $sortOrder,
            ],
        ]);

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

        return $creative;
    }

    protected function generatePhotorealisticImage(ContentCampaign $campaign, ContentBusinessDna $dna, ?string $stylePreset = null): array
    {
        $brandColors = implode(', ', $dna->colors ?? ['#03fcf4', '#1a1a2e']);
        $aesthetic = implode(', ', $dna->brand_aesthetic ?? ['modern-clean']);
        $tone = implode(', ', $dna->brand_tone ?? ['professional']);
        $overview = $dna->business_overview ?? $dna->brand_name;

        $preset = $stylePreset ?? 'photographic';
        $styleModifier = self::STYLE_PRESETS[$preset]['modifier'] ?? self::STYLE_PRESETS['photographic']['modifier'];

        $aspectLabel = match($campaign->aspect_ratio) {
            '1:1' => 'square composition',
            '4:5' => 'portrait feed composition (4:5)',
            default => 'vertical story composition (9:16)',
        };

        $prompt = "Create a premium photorealistic social media image for the campaign '{$campaign->title}'. "
            . "Business: {$dna->brand_name} — {$overview}. "
            . "Visual style: {$aesthetic}. {$styleModifier}. "
            . "Use brand colors ({$brandColors}) as inspiration. The image should feel {$tone}. "
            . "{$aspectLabel}. MUST be a photorealistic photograph, NOT an illustration, NOT a cartoon, NOT a 3D render. "
            . "Professional quality, editorial grade. Do NOT include any text or words in the image.";

        $dimensions = $this->getAspectDimensions($campaign->aspect_ratio);

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
            Log::error('CreativeService::generatePhotorealisticImage failed', ['error' => $e->getMessage()]);
        }

        return ['path' => null, 'url' => null, 'prompt' => $prompt];
    }

    protected function selectBrandImages(ContentBusinessDna $dna, ContentCampaign $campaign, int $count = 3): array
    {
        $images = $dna->images ?? [];

        // Filter to images with valid paths that exist on disk
        $validImages = [];
        foreach ($images as $img) {
            if (!empty($img['path']) && Storage::disk('public')->exists($img['path'])) {
                $validImages[] = $img;
            }
        }

        if (empty($validImages)) {
            return [];
        }

        if (count($validImages) <= $count) {
            return $validImages;
        }

        // Use AI to pick the best images for this campaign
        try {
            $imageList = '';
            foreach ($validImages as $i => $img) {
                $caption = $img['caption'] ?? 'No caption';
                $source = $img['source'] ?? 'unknown';
                $imageList .= "[" . ($i + 1) . "] {$caption} (source: {$source})\n";
            }

            $prompt = "Given this campaign '{$campaign->title}' ({$campaign->description}) for brand '{$dna->brand_name}' ({$dna->business_overview}), "
                . "select the {$count} most relevant images from this list:\n{$imageList}\n"
                . "Return ONLY a JSON array of the numbers, e.g. [1, 3, 5]. No other text.";

            $result = AI::process($prompt, 'text', ['maxResult' => 1], $campaign->team_id);
            $text = $result['data'][0] ?? '';

            if (preg_match('/\[[\d,\s]+\]/', $text, $match)) {
                $indices = json_decode($match[0], true);
                if (is_array($indices)) {
                    $selected = [];
                    foreach ($indices as $idx) {
                        $i = (int)$idx - 1;
                        if (isset($validImages[$i])) {
                            $selected[] = $validImages[$i];
                        }
                    }
                    if (count($selected) >= 1) {
                        return array_slice($selected, 0, $count);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('CreativeService: AI image selection failed, using first N', ['error' => $e->getMessage()]);
        }

        // Fallback: first N images (og:image priority — already sorted by BusinessDnaService)
        return array_slice($validImages, 0, $count);
    }

    protected function smartCropImage(string $sourcePath, int $targetWidth, int $targetHeight, int $teamId): ?string
    {
        if (!extension_loaded('gd')) {
            Log::warning('CreativeService: GD extension not available, skipping smart crop');
            return null;
        }

        $fullPath = Storage::disk('public')->path($sourcePath);

        if (!file_exists($fullPath)) {
            return null;
        }

        try {
            $info = getimagesize($fullPath);
            if (!$info) return null;

            $sourceW = $info[0];
            $sourceH = $info[1];
            $mime = $info['mime'];

            $source = match($mime) {
                'image/jpeg' => imagecreatefromjpeg($fullPath),
                'image/png' => imagecreatefrompng($fullPath),
                'image/webp' => imagecreatefromwebp($fullPath),
                default => null,
            };

            if (!$source) return null;

            // Calculate center crop to target aspect ratio
            $targetRatio = $targetWidth / $targetHeight;
            $sourceRatio = $sourceW / $sourceH;

            if ($sourceRatio > $targetRatio) {
                // Source is wider — crop sides
                $cropH = $sourceH;
                $cropW = (int)($sourceH * $targetRatio);
                $cropX = (int)(($sourceW - $cropW) / 2);
                $cropY = 0;
            } else {
                // Source is taller — crop top/bottom
                $cropW = $sourceW;
                $cropH = (int)($sourceW / $targetRatio);
                $cropX = 0;
                $cropY = (int)(($sourceH - $cropH) / 2);
            }

            $cropped = imagecrop($source, [
                'x' => $cropX,
                'y' => $cropY,
                'width' => $cropW,
                'height' => $cropH,
            ]);

            if (!$cropped) {
                imagedestroy($source);
                return null;
            }

            // Scale to target dimensions
            $scaled = imagescale($cropped, $targetWidth, $targetHeight);
            imagedestroy($cropped);
            imagedestroy($source);

            if (!$scaled) return null;

            $filename = "content-studio/{$teamId}/" . uniqid() . ".jpg";
            $savePath = Storage::disk('public')->path($filename);

            // Ensure directory exists
            $dir = dirname($savePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            imagejpeg($scaled, $savePath, 90);
            imagedestroy($scaled);

            return $filename;
        } catch (\Throwable $e) {
            Log::error('CreativeService::smartCropImage failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function getAspectDimensions(string $aspectRatio): array
    {
        return match($aspectRatio) {
            '1:1'  => ['width' => 1024, 'height' => 1024],
            '4:5'  => ['width' => 864, 'height' => 1080],
            default => ['width' => 720, 'height' => 1280],
        };
    }

    protected function generateTextContent(ContentCampaign $campaign, ContentBusinessDna $dna, int $index): array
    {
        $language = $dna->language ?? 'English';

        $prompt = <<<PROMPT
You are a copywriter creating text overlays for a social media marketing creative (variation #{$index}).

Campaign: {$campaign->title}
Campaign brief: {$campaign->description}
Brand: {$dna->brand_name}
Brand tone: {$this->arrayToString($dna->brand_tone)}
Brand values: {$this->arrayToString($dna->brand_values)}
Language: {$language}

Requirements:
- header: A bold, attention-grabbing headline (max 8 words). Should stop the scroll.
- description: Supporting copy that expands on the headline (max 20 words). Concise and compelling.
- cta: A strong call-to-action (2-4 words). Action-oriented.
- Each variation (#{$index}) must use a DIFFERENT angle/approach from others
- IMPORTANT: Write ALL text (header, description, cta) in {$language}

Return a JSON object:
{
    "header": "Bold headline in {$language}",
    "description": "Supporting text in {$language}",
    "cta": "Action words in {$language}"
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

        if ($creative->isBrandImage() && $creative->source_image_path) {
            // Re-crop the original brand image
            $dimensions = $this->getAspectDimensions($campaign->aspect_ratio);
            $newPath = $this->smartCropImage($creative->source_image_path, $dimensions['width'], $dimensions['height'], $creative->team_id);
            $imageResult = [
                'path' => $newPath ?? $creative->image_path,
                'url' => $newPath ? url('/public/storage/' . $newPath) : $creative->image_url,
                'prompt' => 'Brand image re-crop',
            ];
        } else {
            // AI: regenerate with same or default style preset
            $imageResult = $this->generatePhotorealisticImage($campaign, $dna, $creative->style_preset);
        }

        $newVersion = ($creative->current_version ?? 0) + 1;

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

    public function generateStyledCreative(ContentCreative $creative, string $stylePreset): void
    {
        $campaign = $creative->campaign;
        $dna = $campaign->dna;

        $imageResult = $this->generatePhotorealisticImage($campaign, $dna, $stylePreset);

        $newVersion = ($creative->current_version ?? 0) + 1;

        ContentCreativeVersion::create([
            'creative_id' => $creative->id,
            'version_number' => $newVersion,
            'image_path' => $imageResult['path'] ?? $creative->image_path,
            'image_url' => $imageResult['url'] ?? $creative->image_url,
            'header_text' => $creative->header_text,
            'description_text' => $creative->description_text,
            'cta_text' => $creative->cta_text,
            'metadata' => ['style_preset' => $stylePreset, 'prompt' => $imageResult['prompt'] ?? ''],
            'created_at' => now(),
        ]);

        $creative->update([
            'image_path' => $imageResult['path'] ?? $creative->image_path,
            'image_url' => $imageResult['url'] ?? $creative->image_url,
            'style_preset' => $stylePreset,
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

        $language = $dna->language ?? 'English';

        $prompt = "Generate {$fieldLabel} for a marketing creative. "
            . "Campaign: {$campaign->title}. Brand: {$dna->brand_name}. "
            . "Tone: " . implode(', ', $dna->brand_tone ?? ['professional']) . ". "
            . "IMPORTANT: Write the text in {$language}. "
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

        if ($newAspectRatio !== $creative->campaign->aspect_ratio) {
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

    public function generateSingleAiCreative(ContentCampaign $campaign): void
    {
        $dna = $campaign->dna;
        $sortOrder = ContentCreative::where('campaign_id', $campaign->id)->max('sort_order') + 1;

        $this->generateAiCreative($campaign, $dna, $sortOrder, 'photographic');

        $campaign->update(['status' => 'ready']);
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
