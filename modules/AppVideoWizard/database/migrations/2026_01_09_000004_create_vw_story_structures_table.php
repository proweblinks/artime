<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Story structures for scene distribution (Hollywood three-act, Hero's Journey, etc.)
     */
    public function up(): void
    {
        Schema::create('vw_story_structures', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();

            $table->enum('structure_type', [
                'three_act',     // Classic Hollywood
                'five_act',      // Shakespearean
                'hero_journey',  // Joseph Campbell
                'save_the_cat',  // Blake Snyder
                'shorts',        // Short-form content
                'documentary',   // Documentary arc
                'custom'         // User-defined
            ])->default('three_act');

            // Act distribution (JSON)
            $table->json('act_distribution');
            /*
             * {
             *   "act1": {"percentage": 25, "label": "Setup", "beats": ["hook", "introduction"]},
             *   "act2": {"percentage": 50, "label": "Confrontation", "beats": ["conflict", "midpoint"]},
             *   "act3": {"percentage": 25, "label": "Resolution", "beats": ["climax", "resolution"]}
             * }
             */

            // Pacing curve for visual editor (JSON)
            $table->json('pacing_curve')->nullable();
            /*
             * Array of [percentage, intensity] points
             * [[0, 3], [25, 6], [50, 8], [75, 10], [100, 5]]
             */

            // Best for content types (JSON)
            $table->json('best_for')->nullable(); // ["drama", "thriller", "documentary"]

            // Scene count recommendations
            $table->unsignedTinyInteger('min_scenes')->default(3);
            $table->unsignedTinyInteger('max_scenes')->default(20);

            // Admin settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('structure_type');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_story_structures');
    }
};
