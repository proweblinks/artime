<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add motion intelligence columns to shot types for camera movement integration.
     */
    public function up(): void
    {
        Schema::table('vw_shot_types', function (Blueprint $table) {
            // Primary recommended movement for this shot type
            $table->string('primary_movement', 100)->nullable()->after('motion_description');
            // FK reference to vw_camera_movements.slug

            // Movement intensity recommendation
            $table->enum('movement_intensity', ['static', 'subtle', 'moderate', 'dynamic', 'intense'])
                  ->default('moderate')->after('primary_movement');

            // Compatible secondary movements for stacking
            $table->json('stackable_movements')->nullable()->after('movement_intensity');
            /*
             * Movement slugs that work well as secondary movement for this shot type
             * ["pan-left", "crane-up", "tilt-down"]
             */

            // How this shot typically ends (for continuity with next shot)
            $table->string('typical_ending', 255)->nullable()->after('stackable_movements');
            // "subject centered, camera at eye level"

            // Structured video prompt template
            $table->text('video_prompt_template')->nullable()->after('typical_ending');
            /*
             * Template for video/animation prompt generation:
             * "{style} {shot_name}, {subject} {action}, {camera_movement}, {lighting}"
             */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vw_shot_types', function (Blueprint $table) {
            $table->dropColumn([
                'primary_movement',
                'movement_intensity',
                'stackable_movements',
                'typical_ending',
                'video_prompt_template',
            ]);
        });
    }
};
