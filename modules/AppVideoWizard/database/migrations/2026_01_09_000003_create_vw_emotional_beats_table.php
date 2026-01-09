<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Emotional beats for narrative-based shot selection (MasterClass Three-Act Structure).
     */
    public function up(): void
    {
        Schema::create('vw_emotional_beats', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();

            // Three-act structure positioning
            $table->enum('story_position', [
                'act1_setup',      // Setup - establishing world
                'act1_catalyst',   // Inciting incident
                'act2_rising',     // Rising action
                'act2_midpoint',   // Midpoint twist
                'act2_crisis',     // Crisis/All is lost
                'act3_climax',     // Climax
                'act3_resolution', // Resolution
                'standalone'       // Can appear anywhere
            ])->default('standalone');

            // Intensity and pacing
            $table->unsignedTinyInteger('intensity_level')->default(5); // 1-10 scale
            $table->enum('pacing_suggestion', ['slow', 'medium', 'fast'])->default('medium');
            $table->string('color_mood', 100)->nullable(); // "warm", "cold", "desaturated"

            // Recommended cinematography (JSON arrays)
            $table->json('recommended_shot_types')->nullable();
            /*
             * Shot types that work best for this beat
             * ["close-up", "medium", "reaction"]
             */

            $table->json('recommended_camera_movements')->nullable();
            /*
             * Camera movements for this beat
             * ["slow-push", "static", "handheld"]
             */

            $table->json('recommended_lenses')->nullable();
            /*
             * Lens choices for this beat
             * ["85mm", "50mm", "35mm"]
             */

            // Prompt enhancement
            $table->text('atmosphere_keywords')->nullable();
            /*
             * Keywords to add to prompts for this beat
             * "tense, suspenseful, shadows, anticipation"
             */

            // Admin settings
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('story_position');
            $table->index('intensity_level');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_emotional_beats');
    }
};
