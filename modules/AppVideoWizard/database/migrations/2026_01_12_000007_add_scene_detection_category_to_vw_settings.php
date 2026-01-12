<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Extend vw_settings category ENUM with scene_detection category for Phase 4.
     */
    public function up(): void
    {
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
            'cinematic_intelligence',
            'motion_intelligence',
            'shot_continuity',
            'scene_detection'
        ) NOT NULL DEFAULT 'general'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
            'cinematic_intelligence',
            'motion_intelligence',
            'shot_continuity'
        ) NOT NULL DEFAULT 'general'");
    }
};
