<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'cinematic_intelligence' to the category ENUM
        // This is needed before running the VwSettingSeeder with cinematic_intelligence settings
        DB::statement("ALTER TABLE vw_settings MODIFY COLUMN category ENUM(
            'shot_intelligence',
            'animation',
            'duration',
            'scene',
            'export',
            'general',
            'api',
            'credits',
            'ai_providers',
            'production_intelligence',
            'cinematic_intelligence'
        ) NOT NULL DEFAULT 'general'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous ENUM without 'cinematic_intelligence'
        // First, update any rows with the category to 'general'
        DB::table('vw_settings')
            ->where('category', 'cinematic_intelligence')
            ->update(['category' => 'general']);

        DB::statement("ALTER TABLE vw_settings MODIFY COLUMN category ENUM(
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
        ) NOT NULL DEFAULT 'general'");
    }
};
