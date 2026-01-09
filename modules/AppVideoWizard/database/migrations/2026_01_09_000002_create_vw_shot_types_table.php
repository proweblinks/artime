<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Professional shot types (50+) based on StudioBinder's Camera Shots Guide.
     */
    public function up(): void
    {
        Schema::create('vw_shot_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 255);
            $table->enum('category', [
                'framing',    // Shot size: wide, medium, close-up, etc.
                'angle',      // Camera angle: low, high, dutch, etc.
                'movement',   // Camera movement: pan, tilt, dolly, etc.
                'focus',      // Focus techniques: rack, deep, shallow
                'special'     // Special shots: two-shot, reaction, etc.
            ])->default('framing');
            $table->text('description')->nullable();

            // Camera specifications
            $table->string('camera_specs', 255)->nullable(); // "85mm telephoto, f/1.4, shallow DOF"
            $table->string('default_lens', 100)->nullable(); // "85mm"
            $table->string('default_aperture', 20)->nullable(); // "f/1.4"

            // Duration recommendations
            $table->unsignedTinyInteger('typical_duration_min')->default(3);
            $table->unsignedTinyInteger('typical_duration_max')->default(8);

            // Narrative mapping (JSON arrays)
            $table->json('emotional_beats')->nullable();
            /*
             * Which emotional beats this shot works best for
             * ["tension", "intimacy", "reveal", "climax"]
             */

            $table->json('best_for_genres')->nullable();
            /*
             * Which genres this shot is commonly used in
             * ["thriller", "drama", "horror", "documentary"]
             */

            // Prompt generation
            $table->text('prompt_template')->nullable();
            /*
             * Template with variables:
             * "{shot_type} shot of {subject}, {lens_spec}, {camera_movement}"
             */

            $table->text('motion_description')->nullable();
            /*
             * Default motion/movement description for AI
             * "Slow push in emphasizing details and expressions"
             */

            // 30-degree rule compatibility
            $table->json('compatible_transitions')->nullable();
            /*
             * Shot types that can follow this one (30-degree rule)
             * ["wide", "over-shoulder", "reaction"]
             */

            // Admin settings
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_shot_types');
    }
};
