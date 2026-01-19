<?php

namespace Modules\AppVideoWizard\Services;

use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

/**
 * Smart Reference Generation Service.
 *
 * Automatically extracts character portraits from hero frame (Scene 0)
 * to establish visual consistency for all subsequent scenes.
 *
 * Uses Gemini Vision for character analysis and Image-to-Image for portrait extraction.
 */
class SmartReferenceService
{
    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Analyze scene image to detect and match characters from Character Bible.
     *
     * @param string $imageBase64 Base64 encoded scene image
     * @param array $characterBible The Character Bible data structure
     * @return array Result with success, detectedCharacters, and any errors
     */
    public function analyzeSceneForCharacters(string $imageBase64, array $characterBible): array
    {
        $characters = $characterBible['characters'] ?? [];
        if (empty($characters)) {
            return [
                'success' => false,
                'error' => 'No characters in Bible',
                'detectedCharacters' => [],
            ];
        }

        // Build character list for AI prompt
        $characterList = [];
        foreach ($characters as $idx => $char) {
            $characterList[] = [
                'index' => $idx,
                'name' => $char['name'] ?? 'Unknown',
                'description' => $char['description'] ?? '',
            ];
        }

        $prompt = $this->buildAnalysisPrompt($characterList);

        try {
            $result = $this->geminiService->generateVision($prompt, [
                'image_base64' => $imageBase64,
                'mimeType' => 'image/png',
            ]);

            if (!empty($result['error'])) {
                Log::error('SmartReference: Vision analysis failed', ['error' => $result['error']]);
                return [
                    'success' => false,
                    'error' => $result['error'],
                    'detectedCharacters' => [],
                ];
            }

            // Parse response - Gemini returns data in candidates array
            $response = $result['data']['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if (empty($response)) {
                Log::warning('SmartReference: Empty response from vision API');
                return [
                    'success' => false,
                    'error' => 'Empty response from vision API',
                    'detectedCharacters' => [],
                ];
            }

            $parsed = $this->parseAnalysisResponse($response, $characters);

            Log::info('SmartReference: Scene analysis completed', [
                'charactersInBible' => count($characters),
                'detectedCharacters' => count($parsed['characters']),
            ]);

            return [
                'success' => true,
                'detectedCharacters' => $parsed['characters'],
                'raw' => $response,
            ];

        } catch (\Exception $e) {
            Log::error('SmartReference: Analysis failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'detectedCharacters' => [],
            ];
        }
    }

    /**
     * Extract isolated portrait from scene image using image-to-image generation.
     *
     * @param string $sceneBase64 Base64 encoded scene image
     * @param array $charAnalysis Analysis data for the character (position, confidence, etc.)
     * @param array $charData Character data from the Bible
     * @return array Result with success, base64 portrait data, and mimeType
     */
    public function extractCharacterPortrait(string $sceneBase64, array $charAnalysis, array $charData): array
    {
        $name = $charData['name'] ?? 'Character';
        $description = $charData['description'] ?? '';
        $position = $charAnalysis['position'] ?? 'center';

        // Build Character DNA details if available
        $dnaDetails = $this->buildCharacterDnaPrompt($charData);

        $extractionPrompt = $this->buildExtractionPrompt($name, $description, $position, $dnaDetails);

        try {
            $result = $this->geminiService->generateImageFromImage($sceneBase64, $extractionPrompt, [
                'model' => 'gemini-2.5-flash-preview-05-20', // Best available model for image generation
                'aspectRatio' => '1:1', // Portrait format
                'resolution' => '2K',
            ]);

            if ($result['success'] && !empty($result['imageData'])) {
                Log::info('SmartReference: Portrait extracted successfully', [
                    'characterName' => $name,
                    'imageDataLength' => strlen($result['imageData']),
                ]);

                return [
                    'success' => true,
                    'base64' => $result['imageData'],
                    'mimeType' => $result['mimeType'] ?? 'image/png',
                ];
            }

            Log::warning('SmartReference: Portrait extraction returned no image', [
                'characterName' => $name,
                'error' => $result['error'] ?? 'No image generated',
            ]);

            return [
                'success' => false,
                'error' => $result['error'] ?? 'No image generated',
            ];

        } catch (\Exception $e) {
            Log::error('SmartReference: Portrait extraction failed', [
                'characterName' => $name,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Capture location reference from scene image (empty establishing shot).
     *
     * @param string $sceneBase64 Base64 encoded scene image
     * @param array $locationData Location data from Location Bible
     * @return array Result with success, base64 image data, and mimeType
     */
    public function captureLocationReference(string $sceneBase64, array $locationData): array
    {
        $name = $locationData['name'] ?? 'Location';
        $description = $locationData['description'] ?? '';

        $prompt = $this->buildLocationExtractionPrompt($name, $description);

        try {
            $result = $this->geminiService->generateImageFromImage($sceneBase64, $prompt, [
                'model' => 'gemini-2.5-flash-preview-05-20',
                'aspectRatio' => '16:9', // Widescreen for establishing shots
                'resolution' => '2K',
            ]);

            if ($result['success'] && !empty($result['imageData'])) {
                Log::info('SmartReference: Location reference captured', [
                    'locationName' => $name,
                    'imageDataLength' => strlen($result['imageData']),
                ]);

                return [
                    'success' => true,
                    'base64' => $result['imageData'],
                    'mimeType' => $result['mimeType'] ?? 'image/png',
                ];
            }

            return [
                'success' => false,
                'error' => $result['error'] ?? 'No image generated',
            ];

        } catch (\Exception $e) {
            Log::error('SmartReference: Location capture failed', [
                'locationName' => $name,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build the analysis prompt for character detection.
     */
    protected function buildAnalysisPrompt(array $characterList): string
    {
        $charJson = json_encode($characterList, JSON_PRETTY_PRINT);

        return <<<PROMPT
Analyze this image and identify which characters from the Character Bible appear in it.

CHARACTER BIBLE:
{$charJson}

For each person visible in the image, determine:
1. Which character from the Bible they match (by name/index)
2. Their position in the frame (left, center, right, foreground, background)
3. Confidence score (0.0-1.0) of the match based on description alignment
4. Brief description of how they appear in this specific image

Return ONLY valid JSON (no markdown, no explanation):
{
  "characters": [
    {
      "bibleIndex": 0,
      "name": "Character Name",
      "position": "center",
      "confidence": 0.85,
      "description": "Brief description of how they appear in this image"
    }
  ],
  "totalPeopleDetected": 2,
  "unmatchedCount": 0
}

IMPORTANT:
- Match characters based on their descriptions in the Bible
- Only return characters with confidence >= 0.5
- If a person in the image doesn't match any Bible character, increment unmatchedCount
- If no characters are detected, return: {"characters": [], "totalPeopleDetected": 0, "unmatchedCount": 0}
PROMPT;
    }

    /**
     * Build the extraction prompt for isolated character portrait.
     */
    protected function buildExtractionPrompt(string $name, string $description, string $position, string $dnaDetails = ''): string
    {
        $dnaSection = !empty($dnaDetails) ? "\n\nCharacter Visual Details:\n{$dnaDetails}" : '';

        return <<<PROMPT
Extract an isolated portrait of {$name} from this scene image.

Character Description: {$description}
Position in scene: {$position}{$dnaSection}

Generate a clean, professional portrait following these requirements:

CRITICAL REQUIREMENTS:
- EXACTLY ONE PERSON: {$name} only, completely isolated from the scene
- Professional studio-style portrait with clean neutral background
- Same face, facial features, hair, and clothing as in the source image
- Must be the SAME person from the scene - preserve their exact appearance
- Head and shoulders framing, slight three-quarter turn for character
- Clean neutral gray or soft gradient studio backdrop
- Professional photography lighting, sharp focus on face
- Photorealistic quality, 8K detail

PRESERVE FROM SOURCE:
- Exact facial features, skin tone, expressions
- Hair color, style, length, and texture
- Clothing/outfit visible in the scene
- Any distinctive features (glasses, accessories, etc.)

DO NOT:
- Include any other people
- Include any scene background elements
- Change the character's appearance in any way
- Add elements not present in the source image

OUTPUT: A professional casting-style portrait photo of the EXACT same person from the scene.
PROMPT;
    }

    /**
     * Build Character DNA details for the extraction prompt.
     */
    protected function buildCharacterDnaPrompt(array $charData): string
    {
        $parts = [];

        // Hair details
        $hair = $charData['hair'] ?? [];
        if (!empty(array_filter($hair))) {
            $hairParts = [];
            if (!empty($hair['color'])) $hairParts[] = $hair['color'];
            if (!empty($hair['length'])) $hairParts[] = $hair['length'];
            if (!empty($hair['style'])) $hairParts[] = $hair['style'];
            if (!empty($hair['texture'])) $hairParts[] = $hair['texture'] . ' texture';
            if (!empty($hairParts)) {
                $parts[] = 'Hair: ' . implode(', ', $hairParts);
            }
        }

        // Wardrobe details
        $wardrobe = $charData['wardrobe'] ?? [];
        if (!empty(array_filter($wardrobe))) {
            $wardrobeParts = [];
            if (!empty($wardrobe['outfit'])) $wardrobeParts[] = $wardrobe['outfit'];
            if (!empty($wardrobe['colors'])) $wardrobeParts[] = 'in ' . $wardrobe['colors'];
            if (!empty($wardrobe['style'])) $wardrobeParts[] = $wardrobe['style'] . ' style';
            if (!empty($wardrobeParts)) {
                $parts[] = 'Wardrobe: ' . implode(', ', $wardrobeParts);
            }
        }

        // Makeup details
        $makeup = $charData['makeup'] ?? [];
        if (!empty(array_filter($makeup))) {
            $makeupParts = [];
            if (!empty($makeup['style'])) $makeupParts[] = $makeup['style'];
            if (!empty($makeup['details'])) $makeupParts[] = $makeup['details'];
            if (!empty($makeupParts)) {
                $parts[] = 'Makeup: ' . implode(', ', $makeupParts);
            }
        }

        // Accessories
        $accessories = $charData['accessories'] ?? [];
        if (!empty($accessories)) {
            $parts[] = 'Accessories: ' . implode(', ', $accessories);
        }

        return implode("\n", $parts);
    }

    /**
     * Build the location extraction prompt.
     */
    protected function buildLocationExtractionPrompt(string $name, string $description): string
    {
        return <<<PROMPT
Create a clean establishing shot of the location shown in this image.

Location Name: {$name}
Location Description: {$description}

Generate an empty establishing shot:
- Same location/environment as the source image
- Remove all people from the scene
- Maintain exact lighting, atmosphere, and mood
- Keep all architectural/environmental details
- Cinematic wide-angle establishing shot
- Photorealistic quality

This should be a "clean plate" version of the location for visual reference.
PROMPT;
    }

    /**
     * Parse the analysis response and match to Bible characters.
     */
    protected function parseAnalysisResponse(string $response, array $bibleCharacters): array
    {
        // Clean JSON from response (remove markdown code blocks if present)
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $response = trim($response);

        $parsed = json_decode($response, true);

        if (!is_array($parsed) || !isset($parsed['characters'])) {
            Log::warning('SmartReference: Failed to parse analysis response', [
                'response' => substr($response, 0, 500),
            ]);
            return ['characters' => []];
        }

        // Filter to only high-confidence matches
        $matched = [];
        foreach ($parsed['characters'] as $char) {
            $confidence = $char['confidence'] ?? 0;
            $bibleIndex = $char['bibleIndex'] ?? null;

            // Require at least 0.7 confidence for automatic extraction
            if ($confidence >= 0.7 && $bibleIndex !== null && isset($bibleCharacters[$bibleIndex])) {
                $matched[] = array_merge($char, [
                    'bibleCharacter' => $bibleCharacters[$bibleIndex],
                ]);
            }
        }

        Log::debug('SmartReference: Parsed characters', [
            'totalInResponse' => count($parsed['characters'] ?? []),
            'highConfidenceMatches' => count($matched),
        ]);

        return ['characters' => $matched];
    }
}
