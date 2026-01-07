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
            VwPromptSeeder::class,
            VwProductionTypeSeeder::class,
        ]);
    }
}
