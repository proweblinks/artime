<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add new categories to vw_settings: api, credits, ai_providers
     */
    public function up(): void
    {
        // Alter the ENUM to include new categories
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This will fail if there are rows with the new categories
        // You may need to delete/update those rows first
        DB::statement("ALTER TABLE `vw_settings` MODIFY COLUMN `category` ENUM(
            'shot_intelligence',
            'animation',
            'duration',
            'scene',
            'export',
            'general'
        ) DEFAULT 'general'");
    }
};
