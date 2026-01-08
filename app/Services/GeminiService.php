<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\AIModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;

class GeminiService
{
    protected Client $client;
    protected string $apiKey;
    protected array $cachedModels = [];

    protected array $fallbacks = [
        'text'           => 'gemini-2.5-flash',
        'image'          => 'gemini-2.5-pro',
        'video'          => 'gemini-2.5-pro',
        'vision'         => 'gemini-2.5-pro',
        'embedding'      => 'gemini-embedding',
        'speech'         => 'gemini-tts',
        'speech_to_text' => 'gemini-stt',
        'audio'          => 'gemini-stt',
    ];

    public function __construct()
    {
        $this->apiKey = (string) get_option("ai_gemini_api_key", "");
        // Use the general Gemini API endpoint
        $this->client = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
        ]);
    }

    /**
     * Gets the configured or default model key for a specific category.
     */
    protected function getModel(string $category, ?string $default = null): string
    {
        $default ??= $this->fallbacks[$category] ?? 'gemini-2.5-flash';

        if (empty($this->cachedModels)) {
            $this->cachedModels = array_keys($this->getModels());
        }

        $optionKey = "ai_gemini_model_{$category}";
        $model     = get_option($optionKey, $default);

        if (!in_array($model, $this->cachedModels, true)) {
            $model = $default;
            try {
                DB::table('options')->updateOrInsert(
                    ['key' => $optionKey],
                    ['value' => $default, 'updated_at' => now()]
                );
            } catch (\Throwable $e) {
                Log::warning("Failed to update default Gemini model for {$category}: " . $e->getMessage());
            }
        }

        return $model;
    }

    /**
     * Retrieves a list of active models from the database.
     */
    public function getModels(): array
    {
        try {
            $models = AIModel::query()
                ->where('provider', 'gemini')
                ->where('is_active', 1)
                ->orderBy('category')
                ->orderBy('name')
                ->get(['model_key', 'name']);

            return $models->pluck('name', 'model_key')->toArray();
        } catch (\Throwable $e) {
            Log::error("Error fetching Gemini models from DB: " . $e->getMessage());
            return [];
        }
    }

    // --- Core API Helpers ---

    /**
     * General helper to send requests to the Gemini API (:generateContent endpoint).
     */
    protected function sendGenerateContentRequest(string $model, array $payload, array $generationConfig = []): array
    {
        $payload['generationConfig'] = $generationConfig;
        
        // Remove empty config to avoid API errors if config is empty
        if (empty($payload['generationConfig'])) {
            unset($payload['generationConfig']);
        }

        return $this->makeAPICall($model, "models/{$model}:generateContent", $payload);
    }
    
    /**
     * General helper to send requests to a custom API endpoint (e.g., :predict for Imagen).
     */
    protected function makeAPICall(string $model, string $endpoint, array $payload): array
    {
        try {
            $response = $this->client->request('POST', $endpoint, [
                'query'   => ['key' => $this->apiKey],
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => json_encode($payload),
                'timeout' => 60,
            ]);

            $body = json_decode($response->getBody(), true);
            return $body;

        } catch (ClientException $e) {
            $response  = $e->getResponse();
            $errorBody = $response ? (string)$response->getBody() : null;
            $message   = $e->getMessage();

            // Parse JSON error to get detailed message
            if ($errorBody) {
                $decoded = json_decode($errorBody, true);
                if (isset($decoded['error']['message'])) {
                    $message = $decoded['error']['message'];
                }
            }

            Log::error("Gemini API Client Error", [
                'model'   => $model,
                'status'  => $response?->getStatusCode(),
                'body'    => $errorBody,
            ]);

            throw new \Exception($message, $e->getCode(), $e);

        } catch (\Throwable $e) {
            Log::error("Gemini API Fatal Error", [
                'model' => $model,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    // --- Text Generation ---

    /** * Generates text content using a Gemini model.
     */
    public function generateText(
        string|array $content,
        int $maxLength,
        ?int $maxResult = null,
        string $category = 'text',
        array $options = []
    ): array {
        $model = $this->getModel($category);

        $parts = is_array($content) ? $content : [["text" => $content]];
        $payload = [
            "contents" => [["parts" => $parts]]
        ];

        $generationConfig = [
            'candidate_count'   => $maxResult ?? 1,
            'max_output_tokens' => $maxLength,
            'temperature'       => $options['temperature'] ?? 0.7,
            'top_p'             => $options['top_p'] ?? 0.95,
        ];
        
        try {
            $body = $this->sendGenerateContentRequest($model, $payload, $generationConfig);

            $result = [];
            if (!empty($body['candidates'])) {
                foreach ($body['candidates'] as $candidate) {
                    $result[] = $candidate['content']['parts'][0]['text'] ?? '';
                }
            }

            $usage = $body['usageMetadata'] ?? [];
            return $this->successResponse(
                $model,
                $result,
                [
                    'promptTokens'     => $usage['promptTokenCount'] ?? 0,
                    'completionTokens' => $usage['candidatesTokenCount'] ?? 0,
                    'totalTokens'      => $usage['totalTokenCount'] ?? 0,
                ]
            );

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, $category);
        }
    }

    // --- Image Generation (DALL-E style) ---

    /**
     * Generates images using either Imagen or Gemini Image models.
     */
    public function generateImage(string $prompt, array $options = [], string $category = 'image'): array
    {
        $model = $options['model'] ?? $this->getModel($category);

        try {
            if (str_starts_with($model, 'imagen-')) {
                // Use dedicated logic for Imagen (Vertex AI :predict endpoint)
                return $this->generateWithImagen($model, $prompt, $options);
            }

            if (str_contains($model, 'flash-image') || str_contains($model, 'gemini-')) {
                // Use dedicated logic for Gemini Image (:generateContent endpoint)
                return $this->generateWithGeminiImage($model, $prompt, $options);
            }

            // Fallback for unsupported model
            return $this->errorResponse($model, new \Exception("Unsupported image model: {$model}"), $category);

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, $category);
        }
    }

    /**
     * Handles image generation for Imagen models (Vertex AI - :predict).
     */
    protected function generateWithImagen(string $model, string $prompt, array $options): array
    {
        $sampleCount = $options['count'] ?? 1;
        $imageSize = $options['size'] ?? "1024x1024";

        $payload = [
            "instances" => [
                [
                    "prompt" => $prompt
                ]
            ],
            "parameters" => [
                "sampleCount" => $sampleCount,
                "imageSize"   => $imageSize
            ]
        ];

        try {
            $body = $this->makeAPICall($model, "models/{$model}:predict", $payload);

            $images = [];
            foreach ($body['predictions'] ?? [] as $pred) {
                if (!empty($pred['bytesBase64Encoded'])) {
                    $images[] = [
                        'b64_json' => $pred['bytesBase64Encoded'],
                        'mimeType' => 'image/png',
                    ];
                }
            }

            return $this->successResponse($model, $images);

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, 'image');
        }
    }

    /**
     * Handles image generation for Gemini Image models (:generateContent endpoint).
     * FIX: Uses 'generationConfig' instead of 'config' in the payload.
     */
    protected function generateWithGeminiImage(string $model, string $prompt, array $options = []): array
    {
        // --- 1. Define Expanded Styles & Tones for Better Results ---
        $defaultStyles = [
            'photorealistic, 8k professional photograph, cinematic lighting', // Enhanced realistic photography
            'minimalist flat illustration, vector graphic style', // Modern flat illustration
            'hyper-detailed 3D render, subsurface scattering', // Enhanced 3D render
            'Japanese Ukiyo-e woodblock print style', // Unique artistic style
            'cinematic digital painting, moody colors', // Enhanced digital art
            'synthwave aesthetic, neon glow', // Modern concept art
            'oil painting, impasto texture, dramatic brushstrokes', // Classical art
        ];

        $defaultTones = [
            'professional, high-quality, sharp focus',
            'playful, cartoonish, vibrant colors',
            'elegant, sophisticated, soft pastel palette',
            'energetic, dynamic, motion blur effect',
            'minimalistic, clean, ample negative space',
            'dramatic, high contrast, chiaroscuro lighting',
            'cozy, warm, shallow depth of field', // New tone
        ];

        // --- 2. Use provided options or select a random fallback ---
        $style = $options['style'] ?? $defaultStyles[array_rand($defaultStyles)];
        $tone  = $options['tone']  ?? $defaultTones[array_rand($defaultTones)];

        // An optional negative prompt to guide the model away from unwanted elements
        $negativePrompt = $options['negativePrompt'] ?? 'ugly, deformed, blurry, low resolution, watermark, text, signature, words, letters, numbers, logo, screenshot, cartoon, out of frame';

        // --- 3. Configuration (Using more explicit checks) ---
        $generationConfig = [
            'responseModalities' => ['image', 'text'], // Request image output
        ];

        // NOTE: aspectRatio is NOT supported in generationConfig for Gemini image generation models
        // The gemini-2.0-flash-exp-image-generation model generates at a fixed resolution
        // Instead, we include aspect ratio guidance in the prompt itself below
        $requestedAspectRatio = $options['aspectRatio'] ?? '16:9';

        // Handle number of images (sampleCount is also not supported in this model)
        // Keeping code structure but commented out for future model support
        // if (!empty($options['count']) && (int)$options['count'] > 1) {
        //     $generationConfig['sampleCount'] = (int)$options['count'];
        // }

        // Add aspect ratio guidance to the prompt
        $aspectRatioGuidance = match($requestedAspectRatio) {
            '9:16' => 'Generate in portrait/vertical orientation (9:16 aspect ratio, taller than wide).',
            '1:1' => 'Generate in square format (1:1 aspect ratio, equal width and height).',
            '4:5' => 'Generate in portrait format (4:5 aspect ratio, slightly taller than wide).',
            '3:4' => 'Generate in portrait format (3:4 aspect ratio, portrait orientation).',
            default => 'Generate in widescreen landscape format (16:9 aspect ratio, wider than tall).',
        };

        // Enhanced image prompt with aspect ratio guidance
        $imagePrompt = <<<EOT
    Generate a single, high-quality, aesthetically pleasing image based on the following content.

    Content for Visual Representation: "{$prompt}"

    Generation Parameters:
    - Art Style and Quality: {$style}
    - Vibe and Tone: {$tone}
    - Image Format: {$aspectRatioGuidance}
    - Focus: Use creative visual metaphors, abstract concepts, or a detailed scene to tell the story of the content.
    - Negative Guidelines (AVOID): {$negativePrompt}
    - Crucial Constraint: DO NOT include any text, words, letters, numbers, logos, or watermarks.

    EOT;


        // --- 5. API Payload Construction ---
        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $imagePrompt]
                    ]
                ]
            ],
            // The API might expect generationConfig to be at the top level for image generation
            // Depending on your API client wrapper, this might be needed here or handled by the wrapper.
            // Assuming your `sendGenerateContentRequest` handles merging.
        ];

        try {
            // Log the request for debugging
            Log::info("Gemini Image Generation Request", [
                'model' => $model,
                'promptLength' => strlen($imagePrompt),
                'aspectRatio' => $requestedAspectRatio,
                'style' => substr($style, 0, 50),
            ]);

            // Assume $this->sendGenerateContentRequest handles the model and config correctly
            $body = $this->sendGenerateContentRequest($model, $payload, $generationConfig);

            // --- 6. Detailed Response Logging for Debugging ---
            Log::info("Gemini Image Generation Response", [
                'model' => $model,
                'hasBody' => !empty($body),
                'bodyKeys' => is_array($body) ? array_keys($body) : 'not_array',
                'candidatesCount' => count($body['candidates'] ?? []),
                'promptFeedback' => $body['promptFeedback'] ?? null,
            ]);

            // Check for content filtering / safety blocks FIRST
            if (isset($body['promptFeedback']['blockReason'])) {
                $blockReason = $body['promptFeedback']['blockReason'];
                $safetyRatings = $body['promptFeedback']['safetyRatings'] ?? [];

                Log::warning("Gemini Image Generation Blocked", [
                    'model' => $model,
                    'blockReason' => $blockReason,
                    'safetyRatings' => $safetyRatings,
                    'prompt' => substr($prompt, 0, 200),
                ]);

                throw new \Exception("Image generation blocked: {$blockReason}. The prompt may contain content that violates safety guidelines.");
            }

            // --- 7. Parsing the Response (Cleaner loop and error handling) ---
            $images = [];
            foreach ($body['candidates'] ?? [] as $candidateIndex => $candidate) {
                // Log each candidate for debugging
                Log::debug("Gemini Candidate {$candidateIndex}", [
                    'finishReason' => $candidate['finishReason'] ?? 'not_set',
                    'contentParts' => count($candidate['content']['parts'] ?? []),
                    'safetyRatings' => $candidate['safetyRatings'] ?? [],
                ]);

                // Check for candidate-level blocks
                if (isset($candidate['finishReason']) && in_array($candidate['finishReason'], ['SAFETY', 'BLOCKED', 'RECITATION'])) {
                    Log::warning("Gemini candidate blocked", [
                        'index' => $candidateIndex,
                        'finishReason' => $candidate['finishReason'],
                        'safetyRatings' => $candidate['safetyRatings'] ?? [],
                    ]);
                    continue;
                }

                // Look for image data in all parts (not just the first one)
                foreach ($candidate['content']['parts'] ?? [] as $partIndex => $part) {
                    if (!empty($part['inlineData']['data'])) {
                        $images[] = [
                            'b64_json' => $part['inlineData']['data'],
                            'mimeType' => $part['inlineData']['mimeType'] ?? 'image/png',
                        ];
                        Log::info("Found image in candidate {$candidateIndex}, part {$partIndex}", [
                            'mimeType' => $part['inlineData']['mimeType'] ?? 'image/png',
                            'dataLength' => strlen($part['inlineData']['data']),
                        ]);
                    } elseif (!empty($part['text'])) {
                        // Sometimes the model returns text instead of image
                        Log::debug("Candidate {$candidateIndex}, part {$partIndex} contains text", [
                            'textPreview' => substr($part['text'], 0, 100),
                        ]);
                    }
                }
            }

            if (empty($images)) {
                // Build detailed error message for debugging
                $errorDetails = [
                    'model' => $model,
                    'candidates' => [],
                    'promptFeedback' => $body['promptFeedback'] ?? null,
                ];

                foreach ($body['candidates'] ?? [] as $idx => $candidate) {
                    $errorDetails['candidates'][$idx] = [
                        'finishReason' => $candidate['finishReason'] ?? 'not_set',
                        'partsCount' => count($candidate['content']['parts'] ?? []),
                        'partTypes' => array_map(function($p) {
                            if (isset($p['inlineData'])) return 'inlineData';
                            if (isset($p['text'])) return 'text';
                            return 'unknown';
                        }, $candidate['content']['parts'] ?? []),
                        'safetyRatings' => $candidate['safetyRatings'] ?? [],
                    ];
                }

                Log::error("Gemini Image Generation: No image data returned", $errorDetails);

                // Build user-friendly error message
                $finishReason = $body['candidates'][0]['finishReason'] ?? null;
                $errorMessage = match($finishReason) {
                    'SAFETY' => 'Content was blocked due to safety guidelines. Try modifying your prompt.',
                    'RECITATION' => 'Content was blocked due to recitation policy.',
                    'MAX_TOKENS' => 'Generation exceeded maximum token limit.',
                    'STOP' => 'Generation completed but no image was produced. The model may have returned text instead.',
                    null => 'No response candidates returned. Check API key and model availability.',
                    default => "Generation ended with reason: {$finishReason}",
                };

                throw new \Exception("Image generation failed: {$errorMessage}");
            }

            Log::info("Gemini Image Generation Success", [
                'model' => $model,
                'imagesGenerated' => count($images),
            ]);

            return $this->successResponse($model, $images);

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, 'image');
        }
    }

    /**
     * Generate an image based on an existing image (image-to-image).
     * Used for upscaling, style transfer, and variations.
     */
    public function generateImageFromImage(string $base64Image, string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? $this->getModel('image');

        try {
            Log::info("Gemini Image-to-Image Request", [
                'model' => $model,
                'promptLength' => strlen($prompt),
                'imageDataLength' => strlen($base64Image),
            ]);

            // Build payload with both image and text
            $payload = [
                "contents" => [
                    [
                        "parts" => [
                            [
                                "inlineData" => [
                                    "mimeType" => $options['mimeType'] ?? "image/png",
                                    "data" => $base64Image
                                ]
                            ],
                            ["text" => $prompt]
                        ]
                    ]
                ]
            ];

            $generationConfig = [
                'responseModalities' => ['image', 'text'],
            ];

            $body = $this->sendGenerateContentRequest($model, $payload, $generationConfig);

            // Log response for debugging
            Log::info("Gemini Image-to-Image Response", [
                'model' => $model,
                'candidatesCount' => count($body['candidates'] ?? []),
                'promptFeedback' => $body['promptFeedback'] ?? null,
            ]);

            // Check for blocks
            if (isset($body['promptFeedback']['blockReason'])) {
                throw new \Exception("Image-to-image blocked: " . $body['promptFeedback']['blockReason']);
            }

            // Extract image from response
            foreach ($body['candidates'] ?? [] as $candidate) {
                foreach ($candidate['content']['parts'] ?? [] as $part) {
                    if (!empty($part['inlineData']['data'])) {
                        return [
                            'success' => true,
                            'imageData' => $part['inlineData']['data'],
                            'mimeType' => $part['inlineData']['mimeType'] ?? 'image/png',
                        ];
                    }
                }
            }

            // No image returned
            $finishReason = $body['candidates'][0]['finishReason'] ?? 'unknown';
            throw new \Exception("Image-to-image generation failed. Finish reason: {$finishReason}");

        } catch (\Throwable $e) {
            Log::error("Gemini Image-to-Image Error", [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Edit an image using a mask (inpainting).
     * The mask indicates areas to be edited (white = edit, black = keep).
     */
    public function editImageWithMask(string $base64Image, string $base64Mask, string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? $this->getModel('image');

        try {
            Log::info("Gemini Image Edit with Mask Request", [
                'model' => $model,
                'promptLength' => strlen($prompt),
                'imageDataLength' => strlen($base64Image),
                'maskDataLength' => strlen($base64Mask),
            ]);

            // Build comprehensive edit prompt with mask context
            $editPrompt = <<<EOT
You are editing an existing image. A mask is provided where WHITE areas should be edited/replaced and BLACK areas should remain unchanged.

Edit instructions: {$prompt}

Important:
- ONLY modify the white masked areas
- Keep the black/unmasked areas exactly as they are
- Maintain consistent lighting, style, and perspective with the original
- The edit should blend seamlessly with the surrounding unedited areas
EOT;

            // Build payload with image, mask, and instructions
            $payload = [
                "contents" => [
                    [
                        "parts" => [
                            [
                                "inlineData" => [
                                    "mimeType" => $options['imageMimeType'] ?? "image/png",
                                    "data" => $base64Image
                                ]
                            ],
                            [
                                "inlineData" => [
                                    "mimeType" => "image/png",
                                    "data" => $base64Mask
                                ]
                            ],
                            ["text" => $editPrompt]
                        ]
                    ]
                ]
            ];

            $generationConfig = [
                'responseModalities' => ['image', 'text'],
            ];

            $body = $this->sendGenerateContentRequest($model, $payload, $generationConfig);

            // Log response
            Log::info("Gemini Image Edit Response", [
                'model' => $model,
                'candidatesCount' => count($body['candidates'] ?? []),
                'promptFeedback' => $body['promptFeedback'] ?? null,
            ]);

            // Check for blocks
            if (isset($body['promptFeedback']['blockReason'])) {
                throw new \Exception("Image edit blocked: " . $body['promptFeedback']['blockReason']);
            }

            // Extract edited image from response
            foreach ($body['candidates'] ?? [] as $candidate) {
                foreach ($candidate['content']['parts'] ?? [] as $part) {
                    if (!empty($part['inlineData']['data'])) {
                        return [
                            'success' => true,
                            'imageData' => $part['inlineData']['data'],
                            'mimeType' => $part['inlineData']['mimeType'] ?? 'image/png',
                        ];
                    }
                }
            }

            // If no image returned, fall back to image-to-image without mask
            Log::warning("Mask editing not supported, falling back to image-to-image", [
                'model' => $model,
            ]);

            return $this->generateImageFromImage($base64Image, $prompt, $options);

        } catch (\Throwable $e) {
            Log::error("Gemini Image Edit Error", [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    // --- Video Generation ---

    /**
     * Generates content based on a video file (multimodal analysis).
     */
    public function generateVideo(string $prompt, array $options = [], string $category = 'video'): array
    {
        $model = $this->getModel($category);

        $parts = [["text" => $prompt]];
        if (isset($options['video_uri'])) {
            $parts[] = [
                "fileData" => [
                    "mimeType" => $options['mimeType'] ?? "video/mp4",
                    "fileUri"  => $options['video_uri'],
                ]
            ];
        }

        $payload = ["contents" => [["parts" => $parts]]];
        
        try {
            $body = $this->sendGenerateContentRequest($model, $payload);
            // Process response body for video analysis/generation
            return $this->successResponse($model, $body);

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, $category);
        }
    }

    // --- Vision Generation ---

    /**
     * Generates content based on text and an image (Vision).
     */
    public function generateVision(string|array $prompt, array $options = [], string $category = 'vision'): array
    {
        $model = $this->getModel($category);

        $parts = array_filter([
            is_array($prompt) ? $prompt : ["text" => $prompt],
            isset($options['image_base64']) ? [
                "inlineData" => [
                    "mimeType" => $options['mimeType'] ?? "image/png",
                    "data"     => $options['image_base64'] // Base64 data already
                ]
            ] : null,
        ]);

        $payload = ["contents" => [["parts" => $parts]]];
        
        try {
            $body = $this->sendGenerateContentRequest($model, $payload);
            // Process response body for vision result
            return $this->successResponse($model, $body);

        } catch (\Throwable $e) {
            return $this->errorResponse($model, $e, $category);
        }
    }

    // --- Placeholder/Unsupported Functions ---

    public function generateEmbedding(string $text, array $options = [], string $category = 'embedding'): array
    {
        return $this->errorResponse('gemini-embedding', new \Exception("Gemini embedding not supported yet"), $category);
    }

    public function textToSpeech(string $text, array $options = [], string $category = 'speech'): array
    {
        return $this->errorResponse('gemini-tts', new \Exception("Gemini TTS not supported yet"), $category);
    }

    public function speechToText(string $filePath, array $options = [], string $category = 'speech_to_text'): array
    {
        return $this->errorResponse('gemini-stt', new \Exception("Gemini STT not supported yet"), $category);
    }

    public function generateAudio(string $filePath, array $options = [], string $category = 'audio'): array
    {
        return $this->speechToText($filePath, $options, $category);
    }

    // --- Response Helpers ---

    /**
     * Standard error response format.
     */
    protected function errorResponse(string $model, \Throwable $e, string $category = ''): array
    {
        Log::error("Gemini {$category} error with model {$model}: " . $e->getMessage());

        return [
            'data'             => [],
            'promptTokens'     => 0,
            'completionTokens' => 0,
            'totalTokens'      => 0,
            'minutesUsed'      => 0,
            'model'            => $model,
            'error'            => $e->getMessage(),
        ];
    }

    /**
     * Standard success response format.
     */
    protected function successResponse(string $model, array $data, array $usage = [], float $minutesUsed = 0): array
    {
        return [
            'data'             => $data,
            'promptTokens'     => $usage['promptTokens'] ?? 0,
            'completionTokens' => $usage['completionTokens'] ?? 0,
            'totalTokens'      => $usage['totalTokens'] ?? 0,
            'minutesUsed'      => $minutesUsed,
            'model'            => $model,
            'error'            => null,
        ];
    }
}