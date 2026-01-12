<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Extend vw_settings category ENUM with motion_intelligence category.
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
            'motion_intelligence'
        ) NOT NULL DEFAULT 'general'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Removing enum value could fail if data exists
        // Only revert if no motion_intelligence settings exist
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
