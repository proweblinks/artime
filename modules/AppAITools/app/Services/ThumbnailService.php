<?php

namespace Modules\AppAITools\Services;

use App\Facades\AI;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\AppAITools\Models\AiToolHistory;
use Modules\AppAITools\Models\AiToolAsset;

class ThumbnailService
{
    /**
     * Store a reference image to disk.
     */
    public function storeReferenceImage(int $teamId, string $base64, string $ext = 'png'): string
    {
        $dir = "public/ai-tools/thumbnail-refs/{$teamId}";
        $filename = 'ref-' . Str::random(16) . '.' . $ext;
        Storage::put("{$dir}/{$filename}", base64_decode($base64));
        return "{$dir}/{$filename}";
    }

    /**
     * Load a reference image from disk as base64.
     */
    public function loadReferenceImage(string $storageKey): ?string
    {
        if (!$storageKey || !Storage::exists($storageKey)) {
            return null;
        }
        return base64_encode(Storage::get($storageKey));
    }

    /**
     * Generate thumbnails using the PRO multi-mode system.
     */
    public function generatePro(array $params): array
    {
        $teamId = session('current_team_id', 0);
        $userId = auth()->id();
        $mode = $params['mode'] ?? 'quick';
        $variations = min(max($params['variations'] ?? 2, 1), 4);

        $history = AiToolHistory::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'tool' => 'ai_thumbnails',
            'platform' => 'general',
            'title' => $params['title'] ?? 'Thumbnail',
            'input_data' => [
                'mode' => $mode,
                'title' => $params['title'] ?? '',
                'category' => $params['category'] ?? 'general',
                'style' => $params['style'] ?? 'professional',
                'variations' => $variations,
                'custom_prompt' => $params['customPrompt'] ?? '',
                'has_reference' => !empty($params['referenceStorageKey']),
                'has_youtube' => !empty($params['youtubeData']),
            ],
            'status' => 2,
        ]);

        try {
            // Build prompt using ThumbnailPromptBuilder
            $promptBuilder = new ThumbnailPromptBuilder();
            $imagePrompt = $promptBuilder->build($params);

            $images = [];
            $storagePath = "public/ai-tools/thumbnails/{$teamId}";
            $totalTokens = 0;

            if ($mode === 'quick') {
                // Quick mode: generate via AI::process (text-to-image)
                $images = $this->generateQuickMode($imagePrompt, $variations, $storagePath, $history->id, $teamId, $totalTokens);
            } else {
                // Reference/Upgrade mode: use GeminiService image-to-image
                $refBase64 = null;
                if (!empty($params['referenceStorageKey'])) {
                    $refBase64 = $this->loadReferenceImage($params['referenceStorageKey']);
                }

                $additionalImages = [];
                if (!empty($params['faceLockStorageKey'])) {
                    $faceBase64 = $this->loadReferenceImage($params['faceLockStorageKey']);
                    if ($faceBase64) {
                        $additionalImages[] = ['base64' => $faceBase64, 'mimeType' => 'image/png'];
                        $imagePrompt .= "\n\nFACE LOCK: PRESERVE the exact facial identity from the additional face reference image.";
                    }
                }

                if ($refBase64) {
                    $images = $this->generateWithReference($imagePrompt, $refBase64, $variations, $storagePath, $history->id, $additionalImages);
                } else {
                    // Fallback to quick mode if no reference available
                    $images = $this->generateQuickMode($imagePrompt, $variations, $storagePath, $history->id, $teamId, $totalTokens);
                }
            }

            // Save asset records
            foreach ($images as $idx => $imageInfo) {
                AiToolAsset::create([
                    'history_id' => $history->id,
                    'type' => 'thumbnail',
                    'file_path' => $imageInfo['path'] ?? '',
                    'metadata' => [
                        'prompt' => $imagePrompt,
                        'mode' => $mode,
                        'style' => $params['style'] ?? 'professional',
                        'category' => $params['category'] ?? 'general',
                    ],
                ]);
            }

            $result = [
                'images' => $images,
                'prompt' => $imagePrompt,
                'mode' => $mode,
                'style' => $params['style'] ?? 'professional',
                'history_id' => $history->id,
            ];

            $history->update([
                'result_data' => $result,
                'status' => 1,
                'credits_used' => $totalTokens,
            ]);

            return $result;

        } catch (\Exception $e) {
            $history->update(['status' => 0, 'result_data' => ['error' => $e->getMessage()]]);
            throw $e;
        }
    }

    /**
     * Quick mode: text-to-image generation.
     */
    protected function generateQuickMode(string $prompt, int $variations, string $storagePath, int $historyId, int $teamId, int &$totalTokens): array
    {
        $images = [];

        for ($v = 0; $v < $variations; $v++) {
            $variationPrompt = $prompt;
            if ($v > 0) {
                $variationPrompt .= "\n\nVARIATION {$v}: Generate a distinctly different visual interpretation while keeping the same subject matter and quality.";
            }

            $imageResult = AI::process($variationPrompt, 'image', [
                'size' => '1024x576',
                'n' => 1,
            ], $teamId);

            if (!empty($imageResult['error'])) {
                Log::warning('ThumbnailService quick mode error', ['error' => $imageResult['error'], 'variation' => $v]);
                continue;
            }

            $totalTokens += ($imageResult['totalTokens'] ?? 0);

            foreach ($imageResult['data'] ?? [] as $imageData) {
                $imageInfo = $this->saveImageData($imageData, $storagePath, $historyId, $v);
                if ($imageInfo) {
                    $images[] = $imageInfo;
                }
            }
        }

        if (empty($images)) {
            throw new \Exception(__('Failed to generate thumbnails. Please try again.'));
        }

        return $images;
    }

    /**
     * Reference/Upgrade mode: image-to-image with GeminiService.
     */
    protected function generateWithReference(string $prompt, string $refBase64, int $variations, string $storagePath, int $historyId, array $additionalImages = []): array
    {
        $images = [];
        $gemini = app(GeminiService::class);

        for ($v = 0; $v < $variations; $v++) {
            $variationPrompt = $prompt;
            if ($v > 0) {
                $variationPrompt .= "\n\nVARIATION {$v}: Create a distinctly different composition and visual approach while maintaining the same quality and reference style.";
            }

            try {
                $options = [
                    'aspectRatio' => '16:9',
                    'mimeType' => 'image/png',
                ];

                if (!empty($additionalImages)) {
                    $options['additionalImages'] = $additionalImages;
                }

                $result = $gemini->generateImageFromImage($refBase64, $variationPrompt, $options);

                if (!empty($result['success']) && !empty($result['imageData'])) {
                    $filename = "thumb_{$historyId}_{$v}_" . time() . '.png';
                    Storage::put("{$storagePath}/{$filename}", base64_decode($result['imageData']));
                    $path = str_replace('public/', 'storage/', $storagePath) . "/{$filename}";

                    $images[] = [
                        'index' => $v,
                        'path' => $path,
                        'url' => asset($path),
                    ];
                } else {
                    Log::warning('ThumbnailService reference mode: generation failed', [
                        'variation' => $v,
                        'error' => $result['error'] ?? 'Unknown',
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('ThumbnailService reference mode exception', [
                    'variation' => $v,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (empty($images)) {
            throw new \Exception(__('Failed to generate thumbnails with reference. Please try again.'));
        }

        return $images;
    }

    /**
     * Save image data (URL or base64) to storage.
     */
    protected function saveImageData(array $imageData, string $storagePath, int $historyId, int $index): ?array
    {
        $imageInfo = ['index' => $index];

        if (isset($imageData['url'])) {
            $imageInfo['url'] = $imageData['url'];
            $imageInfo['path'] = '';

            try {
                $contents = file_get_contents($imageData['url']);
                if ($contents) {
                    $filename = "thumb_{$historyId}_{$index}_" . time() . '.png';
                    Storage::put("{$storagePath}/{$filename}", $contents);
                    $imageInfo['path'] = str_replace('public/', 'storage/', $storagePath) . "/{$filename}";
                    $imageInfo['url'] = asset($imageInfo['path']);
                }
            } catch (\Exception $e) {
                Log::info('ThumbnailService: Could not save locally', ['error' => $e->getMessage()]);
            }
        } elseif (isset($imageData['b64_json'])) {
            $filename = "thumb_{$historyId}_{$index}_" . time() . '.png';
            Storage::put("{$storagePath}/{$filename}", base64_decode($imageData['b64_json']));
            $imageInfo['path'] = str_replace('public/', 'storage/', $storagePath) . "/{$filename}";
            $imageInfo['url'] = asset($imageInfo['path']);
        } else {
            return null;
        }

        return $imageInfo;
    }

    /**
     * Upscale a thumbnail to HD (4x) using GeminiService.
     */
    public function upscaleImage(string $imagePath, ?int $historyId = null): array
    {
        // Convert web path to storage path
        $diskPath = str_replace('storage/', 'public/', $imagePath);

        if (!Storage::exists($diskPath)) {
            throw new \Exception(__('Original image not found.'));
        }

        $imageBase64 = base64_encode(Storage::get($diskPath));
        $gemini = app(GeminiService::class);

        $result = $gemini->generateImageFromImage(
            $imageBase64,
            'Upscale this image to the highest possible resolution. Maintain ALL details, textures, colors, and composition exactly as they are. Do not change, add, or remove any elements. Only increase resolution and sharpness.',
            [
                'aspectRatio' => '16:9',
                'resolution' => '4K',
                'mimeType' => 'image/png',
            ]
        );

        if (empty($result['success']) || empty($result['imageData'])) {
            throw new \Exception($result['error'] ?? __('Upscaling failed. Please try again.'));
        }

        // Save HD version
        $pathInfo = pathinfo($diskPath);
        $hdFilename = $pathInfo['filename'] . '_hd.' . ($pathInfo['extension'] ?? 'png');
        $hdDiskPath = $pathInfo['dirname'] . '/' . $hdFilename;

        Storage::put($hdDiskPath, base64_decode($result['imageData']));

        $hdWebPath = str_replace('public/', 'storage/', $hdDiskPath);

        return [
            'path' => $hdWebPath,
            'url' => asset($hdWebPath),
        ];
    }

    /**
     * Inpaint edit a thumbnail using GeminiService mask editing.
     */
    public function inpaintEdit(string $imagePath, string $maskBase64, string $editPrompt): array
    {
        $diskPath = str_replace('storage/', 'public/', $imagePath);

        if (!Storage::exists($diskPath)) {
            throw new \Exception(__('Original image not found.'));
        }

        $imageBase64 = base64_encode(Storage::get($diskPath));
        $gemini = app(GeminiService::class);

        $result = $gemini->editImageWithMask(
            $imageBase64,
            $maskBase64,
            $editPrompt,
            [
                'aspectRatio' => '16:9',
                'imageMimeType' => 'image/png',
            ]
        );

        if (empty($result['success']) || empty($result['imageData'])) {
            throw new \Exception($result['error'] ?? __('Inpaint edit failed. Please try again.'));
        }

        // Save edited version (replace original)
        $teamId = session('current_team_id', 0);
        $storagePath = "public/ai-tools/thumbnails/{$teamId}";
        $filename = "thumb_edited_" . time() . '_' . Str::random(6) . '.png';

        Storage::put("{$storagePath}/{$filename}", base64_decode($result['imageData']));

        $webPath = str_replace('public/', 'storage/', $storagePath) . "/{$filename}";

        return [
            'path' => $webPath,
            'url' => asset($webPath),
        ];
    }

    /**
     * Compare two thumbnails using AI vision (existing method).
     */
    public function compare(string $imagePath1, string $imagePath2): array
    {
        $teamId = session('current_team_id', 0);
        $userId = auth()->id();

        $history = AiToolHistory::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'tool' => 'thumbnail_arena',
            'platform' => 'general',
            'title' => 'Thumbnail Comparison',
            'input_data' => ['has_images' => true],
            'status' => 2,
        ]);

        try {
            $image1Base64 = base64_encode(file_get_contents($imagePath1));
            $image2Base64 = base64_encode(file_get_contents($imagePath2));

            $analysisA = $this->analyzeThumb($image1Base64, 'A', $teamId);
            $analysisB = $this->analyzeThumb($image2Base64, 'B', $teamId);

            $scoreA = array_sum($analysisA['scores'] ?? []);
            $scoreB = array_sum($analysisB['scores'] ?? []);
            $winner = $scoreA >= $scoreB ? 'A' : 'B';

            $improvementPrompt = "Based on these thumbnail analyses, provide 5 specific improvement tips:\n"
                . "Thumbnail A scores: " . json_encode($analysisA['scores'] ?? []) . "\n"
                . "Thumbnail B scores: " . json_encode($analysisB['scores'] ?? []) . "\n"
                . "Respond with ONLY a JSON array of 5 tip strings.";

            $tipsResult = AI::process($improvementPrompt, 'text', ['maxResult' => 1], $teamId);
            $tips = json_decode($tipsResult['data'][0] ?? '[]', true) ?: [];

            $result = [
                'winner' => $winner,
                'winner_reason' => "Thumbnail {$winner} scored higher overall with better visual impact.",
                'analysis' => [
                    'a' => $analysisA,
                    'b' => $analysisB,
                ],
                'improvements' => $tips,
            ];

            $history->update([
                'result_data' => $result,
                'status' => 1,
            ]);

            return $result;

        } catch (\Exception $e) {
            $history->update(['status' => 0, 'result_data' => ['error' => $e->getMessage()]]);
            throw $e;
        }
    }

    protected function analyzeThumb(string $base64Image, string $label, int $teamId): array
    {
        $prompt = "Analyze this thumbnail image (Thumbnail {$label}) for click-through effectiveness. "
            . "Score each category from 0-100:\n"
            . "1. visual_hierarchy - How well does it guide the eye?\n"
            . "2. color_contrast - Are colors attention-grabbing?\n"
            . "3. emotional_impact - Does it evoke emotion/curiosity?\n"
            . "4. text_readability - Is any text clear and readable?\n"
            . "5. mobile_friendliness - Does it work at small sizes?\n"
            . "\nRespond with ONLY a JSON object: {\"scores\": {\"visual_hierarchy\": N, \"color_contrast\": N, \"emotional_impact\": N, \"text_readability\": N, \"mobile_friendliness\": N}, \"feedback\": \"brief overall feedback\"}";

        $result = AI::process($prompt, 'vision', [
            'image_base64' => $base64Image,
            'mimeType' => 'image/png',
        ], $teamId);

        if (!empty($result['error'])) {
            return ['scores' => ['visual_hierarchy' => 50, 'color_contrast' => 50, 'emotional_impact' => 50, 'text_readability' => 50, 'mobile_friendliness' => 50], 'feedback' => 'Analysis unavailable.'];
        }

        $text = $result['data'][0] ?? '{}';
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/', '', $text);

        return json_decode(trim($text), true) ?: ['scores' => [], 'feedback' => ''];
    }

    /**
     * Legacy generate method for backward compatibility.
     */
    public function generate(string $title, string $style, string $customPrompt = '', string $aspectRatio = '16:9'): array
    {
        return $this->generatePro([
            'mode' => 'quick',
            'title' => $title,
            'category' => 'general',
            'style' => $style,
            'variations' => 2,
            'customPrompt' => $customPrompt,
        ]);
    }
}
