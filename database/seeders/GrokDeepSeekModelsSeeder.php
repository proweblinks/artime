<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AIModel;

/**
 * Seeder for Grok (xAI) and DeepSeek AI models.
 *
 * Pricing Reference (Jan 2026):
 *
 * Grok (xAI):
 * - Grok 4.1 Fast: $0.20/1M input, $0.50/1M output (2M context) - BEST VALUE
 * - Grok 4: $3.00/1M input, $15.00/1M output (256K context)
 * - Grok 3 Mini: $0.30/1M input, $0.50/1M output
 * - Grok 2: $2.00/1M input, $10.00/1M output
 *
 * DeepSeek:
 * - DeepSeek V3.2-Exp: $0.28/1M input, $0.42/1M output (cache miss)
 * - DeepSeek V3.2-Exp: $0.028/1M input (cache hit) - 90% savings
 * - DeepSeek Reasoner: $0.55/1M input, $2.19/1M output
 */
class GrokDeepSeekModelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $models = [
            // ==========================================
            // GROK (xAI) MODELS
            // ==========================================

            // Grok 4 Series (Latest - Jan 2026)
            [
                'provider' => 'grok',
                'model_key' => 'grok-4-fast',
                'name' => 'Grok 4 Fast - Latest, 2M context ($0.20/$0.50)',
                'category' => 'text',
                'is_active' => true,
                'api_type' => 'chat',
            ],
            [
                'provider' => 'grok',
                'model_key' => 'grok-4',
                'name' => 'Grok 4 - Frontier intelligence ($3/$15)',
                'category' => 'text',
                'is_active' => true,
                'api_type' => 'chat',
            ],

            // Grok 3 Series
            [
                'provider' => 'grok',
                'model_key' => 'grok-3-fast',
                'name' => 'Grok 3 Fast - Best value, 2M context ($0.20/$0.50)',
                'category' => 'text',
                'is_active' => true,
                'api_type' => 'chat',
            ],
            [
                'provider' => 'grok',
                'model_key' => 'grok-3',
                'name' => 'Grok 3 - Frontier model ($3/$15)',
                'category' => 'text',
                'is_active' => true,
                'api_type' => 'chat',
            ],
            [
                'provider' => 'grok',
                'model_key' => 'grok-3-mini',
                'name' => 'Grok 3 Mini - Budget option ($0.30/$0.50)',
                'category' => 'text',
                'is_active' => true,
                'api_type' => 'chat',
            ],

            // Grok 2 Series
            [
                'provider' => 'grok',
                'model_key' => 'grok-2-1212',
                'name' => 'Grok 2 - Stable release ($2/$10)',
                'category' => 'text',
                'is_active' => true,
                'api_type' => 'chat',
            ],

            // Grok Vision Models
            [
                'provider' => 'grok',
                'model_key' => 'grok-2-vision-1212',
                'name' => 'Grok 2 Vision - Image understanding ($2/$10)',
                'category' => 'vision',
                'is_active' => true,
                'api_type' => 'chat',
            ],

            // ==========================================
            // DEEPSEEK MODELS
            // ==========================================

            // DeepSeek Text Models
            [
                'provider' => 'deepseek',
                'model_key' => 'deepseek-chat',
                'name' => 'DeepSeek Chat V3.2 - Best value ($0.28/$0.42)',
                'category' => 'text',
                'is_active' => true,
                'api_type' => 'chat',
            ],
            [
                'provider' => 'deepseek',
                'model_key' => 'deepseek-reasoner',
                'name' => 'DeepSeek Reasoner (R1) - Deep reasoning ($0.55/$2.19)',
                'category' => 'text',
                'is_active' => true,
                'api_type' => 'chat',
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

        $this->command->info('Grok (xAI) and DeepSeek AI models seeded successfully!');
    }
}
