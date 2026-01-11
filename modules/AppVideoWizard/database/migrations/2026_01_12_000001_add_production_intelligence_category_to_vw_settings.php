<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add production_intelligence category to vw_settings ENUM
     */
    public function up(): void
    {
        // Alter the ENUM to include production_intelligence category
        DB::statement("ALTER TABLE `vw_settings` MODIFY COLUMN `category` ENUM(
            'shot_intelligence',
            'animation',
            'duration',
            'scene',
            'export',
            'general',
            'api',
            'credits',
            'ai_providers',
            'production_intelligence'
        ) DEFAULT 'general'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This will fail if there are rows with production_intelligence category
        // You may need to delete those rows first
        DB::statement("ALTER TABLE `vw_settings` MODIFY COLUMN `category` ENUM(
            'shot_intelligence',
            'animation',
            'duration',
            'scene',
            'export',
            'general',
            'api',
            'credits',
            'ai_providers'
        ) DEFAULT 'general'");
    }
};
