<?php

namespace Modules\AppAITools\Services;

use App\Facades\AI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\AppAITools\Models\AiToolHistory;
use Modules\AppAITools\Models\AiToolAsset;

class ThumbnailService
{
    /**
     * Generate thumbnails from a title and style.
     */
    public function generate(string $title, string $style, string $customPrompt = '', string $aspectRatio = '16:9'): array
    {
        $teamId = session('current_team_id', 0);
        $userId = auth()->id();

        $history = AiToolHistory::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'tool' => 'ai_thumbnails',
            'platform' => 'general',
            'title' => $title,
            'input_data' => [
                'title' => $title,
                'style' => $style,
                'custom_prompt' => $customPrompt,
                'aspect_ratio' => $aspectRatio,
            ],
            'status' => 2,
        ]);

        try {
            $styleName = config("appaitools.thumbnail_styles.{$style}", 'Bold Text');

            // Generate image prompt via AI
            $promptGenPrompt = "You are an expert thumbnail designer. Create a detailed image generation prompt for a YouTube-style thumbnail.\n\n"
                . "Video title: \"{$title}\"\n"
                . "Style: {$styleName}\n"
                . ($customPrompt ? "Additional details: {$customPrompt}\n" : '')
                . "\nRules:\n"
                . "- Describe the visual composition in detail\n"
                . "- Include colors, lighting, and mood\n"
                . "- Make it eye-catching and click-worthy\n"
                . "- Do NOT include any text in the image description (thumbnails text is added later)\n"
                . "- Focus on a single striking visual that tells the story\n"
                . "\nRespond with ONLY the image generation prompt, no explanation.";

            $promptResult = AI::process($promptGenPrompt, 'text', ['maxResult' => 1], $teamId);
            $imagePrompt = trim($promptResult['data'][0] ?? $title);

            // Map aspect ratio to size
            $sizeMap = [
                '16:9' => '1024x576',
                '9:16' => '576x1024',
                '1:1' => '1024x1024',
            ];
            $size = $sizeMap[$aspectRatio] ?? '1024x576';

            // Generate image
            $imageResult = AI::process($imagePrompt, 'image', [
                'size' => $size,
                'n' => 2,
            ], $teamId);

            if (!empty($imageResult['error'])) {
                throw new \Exception($imageResult['error']);
            }

            $images = [];
            $storagePath = "public/ai-tools/thumbnails/{$teamId}";

            foreach ($imageResult['data'] ?? [] as $idx => $imageData) {
                $imageInfo = ['index' => $idx];

                if (isset($imageData['url'])) {
                    $imageInfo['url'] = $imageData['url'];
                    $imageInfo['path'] = '';

                    // Try to download and save locally
                    try {
                        $contents = file_get_contents($imageData['url']);
                        if ($contents) {
                            $filename = "thumb_{$history->id}_{$idx}_" . time() . '.png';
                            Storage::put("{$storagePath}/{$filename}", $contents);
                            $imageInfo['path'] = "storage/ai-tools/thumbnails/{$teamId}/{$filename}";
                        }
                    } catch (\Exception $e) {
                        Log::info('ThumbnailService: Could not save locally', ['error' => $e->getMessage()]);
                    }
                } elseif (isset($imageData['b64_json'])) {
                    $filename = "thumb_{$history->id}_{$idx}_" . time() . '.png';
                    Storage::put("{$storagePath}/{$filename}", base64_decode($imageData['b64_json']));
                    $imageInfo['path'] = "storage/ai-tools/thumbnails/{$teamId}/{$filename}";
                    $imageInfo['url'] = asset($imageInfo['path']);
                }

                $images[] = $imageInfo;

                // Save asset record
                AiToolAsset::create([
                    'history_id' => $history->id,
                    'type' => 'thumbnail',
                    'file_path' => $imageInfo['path'] ?? '',
                    'metadata' => [
                        'prompt' => $imagePrompt,
                        'style' => $style,
                        'aspect_ratio' => $aspectRatio,
                        'size' => $size,
                    ],
                ]);
            }

            $totalTokens = ($promptResult['totalTokens'] ?? 0) + ($imageResult['totalTokens'] ?? 0);

            $result = [
                'images' => $images,
                'prompt' => $imagePrompt,
                'style' => $style,
                'aspect_ratio' => $aspectRatio,
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
     * Compare two thumbnails using AI vision.
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
            // Encode images to base64
            $image1Base64 = base64_encode(file_get_contents($imagePath1));
            $image2Base64 = base64_encode(file_get_contents($imagePath2));

            // Analyze thumbnail A
            $analysisA = $this->analyzeThumb($image1Base64, 'A', $teamId);

            // Analyze thumbnail B
            $analysisB = $this->analyzeThumb($image2Base64, 'B', $teamId);

            // Determine winner
            $scoreA = array_sum($analysisA['scores'] ?? []);
            $scoreB = array_sum($analysisB['scores'] ?? []);
            $winner = $scoreA >= $scoreB ? 'A' : 'B';

            // Get improvement tips
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

    /**
     * Analyze a single thumbnail image via AI vision.
     */
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
}
