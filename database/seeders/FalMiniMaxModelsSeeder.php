<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AIModel;

class FalMiniMaxModelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $models = [
            // FAL AI - Image Models
            [
                'provider' => 'fal',
                'model_key' => 'fal-ai/flux-pro/v1.1',
                'name' => 'Flux Pro v1.1 - Professional image generation',
                'category' => 'image',
                'is_active' => true,
                'api_type' => 'image',
            ],
            [
                'provider' => 'fal',
                'model_key' => 'fal-ai/flux/dev',
                'name' => 'Flux Dev - Fast development image generation',
                'category' => 'image',
                'is_active' => true,
                'api_type' => 'image',
            ],
            [
                'provider' => 'fal',
                'model_key' => 'fal-ai/flux/schnell',
                'name' => 'Flux Schnell - Ultra-fast image generation',
                'category' => 'image',
                'is_active' => true,
                'api_type' => 'image',
            ],
            [
                'provider' => 'fal',
                'model_key' => 'fal-ai/flux-realism',
                'name' => 'Flux Realism - Photorealistic images',
                'category' => 'image',
                'is_active' => true,
                'api_type' => 'image',
            ],

            // FAL AI - Video Models
            [
                'provider' => 'fal',
                'model_key' => 'fal-ai/kling-video/v1/standard/text-to-video',
                'name' => 'Kling Video - Text to video generation',
                'category' => 'video',
                'is_active' => true,
                'api_type' => 'video',
            ],
            [
                'provider' => 'fal',
                'model_key' => 'fal-ai/kling-video/v1/standard/image-to-video',
                'name' => 'Kling Video - Image to video generation',
                'category' => 'video',
                'is_active' => true,
                'api_type' => 'video',
            ],
            [
                'provider' => 'fal',
                'model_key' => 'fal-ai/minimax/video-01',
                'name' => 'MiniMax Video-01 via FAL - Text/Image to video',
                'category' => 'video',
                'is_active' => true,
                'api_type' => 'video',
            ],
            [
                'provider' => 'fal',
                'model_key' => 'fal-ai/luma-dream-machine',
                'name' => 'Luma Dream Machine - Creative video generation',
                'category' => 'video',
                'is_active' => true,
                'api_type' => 'video',
            ],

            // MiniMax AI - Text Models
            [
                'provider' => 'minimax',
                'model_key' => 'abab6.5s-chat',
                'name' => 'Abab 6.5s Chat - General text generation',
                'category' => 'text',
                'is_active' => true,
                'api_type' => 'chat',
            ],
            [
                'provider' => 'minimax',
                'model_key' => 'abab6.5g-chat',
                'name' => 'Abab 6.5g Chat - Advanced text generation',
                'category' => 'text',
                'is_active' => true,
                'api_type' => 'chat',
            ],

            // MiniMax AI - Video Models
            [
                'provider' => 'minimax',
                'model_key' => 'video-01',
                'name' => 'Video-01 - Text/Image to video generation',
                'category' => 'video',
                'is_active' => true,
                'api_type' => 'video',
            ],

            // MiniMax AI - Speech Models
            [
                'provider' => 'minimax',
                'model_key' => 'speech-01-turbo',
                'name' => 'Speech-01 Turbo - Fast text-to-speech',
                'category' => 'speech',
                'type' => 'tts',
                'is_active' => true,
                'api_type' => 'tts',
            ],
            [
                'provider' => 'minimax',
                'model_key' => 'speech-01-hd',
                'name' => 'Speech-01 HD - High-quality text-to-speech',
                'category' => 'speech',
                'type' => 'tts',
                'is_active' => true,
                'api_type' => 'tts',
            ],
        ];

        foreach ($models as $model) {
            AIModel::updateOrCreate(
                [
                    'provider' => $model['provider'],
                    'model_key' => $model['model_key'],
                    'category' => $model['category'],
                ],
                [
                    'name' => $model['name'],
                    'type' => $model['type'] ?? null,
                    'is_active' => $model['is_active'],
                    'api_type' => $model['api_type'],
                    'id_secure' => $model['id_secure'] ?? rand_string(32),
                ]
            );
        }

        $this->command->info('FAL and MiniMax AI models seeded successfully!');
    }
}
