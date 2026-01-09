<?php

namespace Modules\AppVideoWizard\Database\Seeders;

use Illuminate\Database\Seeder;

class VideoWizardDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            // Core seeders
            VwPromptSeeder::class,
            VwProductionTypeSeeder::class,

            // Professional Cinematography System seeders
            VwGenrePresetSeeder::class,      // 15+ genre presets (thriller, drama, documentary, etc.)
            VwShotTypeSeeder::class,          // 50+ professional shot types (StudioBinder guide)
            VwEmotionalBeatSeeder::class,     // Emotional beats (Three-Act structure)
            VwStoryStructureSeeder::class,    // Story structures (Hero's Journey, Save the Cat, etc.)
            VwCameraSpecSeeder::class,        // Camera/lens specs (Sora 2 best practices)
        ]);
    }
}
