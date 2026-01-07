<?php

namespace Modules\AppVideoWizard\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppVideoWizard\Models\VwProductionType;

class VwProductionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Load production types from config
        $productionTypes = config('appvideowizard.production_types', []);

        $sortOrder = 0;
        foreach ($productionTypes as $slug => $type) {
            $sortOrder++;

            $parent = VwProductionType::updateOrCreate(
                ['slug' => $slug, 'parent_id' => null],
                [
                    'name' => $type['name'],
                    'icon' => $type['icon'] ?? null,
                    'description' => $type['description'] ?? null,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ]
            );

            if (!empty($type['subTypes'])) {
                $subSortOrder = 0;
                foreach ($type['subTypes'] as $subSlug => $subType) {
                    $subSortOrder++;

                    VwProductionType::updateOrCreate(
                        ['slug' => $subSlug, 'parent_id' => $parent->id],
                        [
                            'name' => $subType['name'],
                            'icon' => $subType['icon'] ?? null,
                            'description' => $subType['description'] ?? null,
                            'characteristics' => $subType['characteristics'] ?? null,
                            'default_narration' => $subType['defaultNarration'] ?? null,
                            'suggested_duration_min' => $subType['suggestedDuration']['min'] ?? null,
                            'suggested_duration_max' => $subType['suggestedDuration']['max'] ?? null,
                            'sort_order' => $subSortOrder,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        $this->command->info('Video Wizard production types seeded successfully.');
    }
}
