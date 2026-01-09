<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Genre presets for professional cinematography - admin manageable.
     */
    public function up(): void
    {
        Schema::create('vw_genre_presets', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 255);
            $table->enum('category', [
                'documentary',
                'cinematic',
                'horror',
                'comedy',
                'social',
                'commercial',
                'experimental',
                'educational'
            ])->default('cinematic');
            $table->text('description')->nullable();

            // Cinematography specifications
            $table->text('camera_language'); // "slow dolly, low angles, stabilized gimbal"
            $table->text('color_grade');     // "desaturated teal shadows, amber highlights"
            $table->text('lighting');        // "harsh single-source, dramatic rim lights"
            $table->text('atmosphere')->nullable(); // "smoke, rain reflections, wet surfaces"
            $table->text('style');           // "ultra-cinematic photoreal, noir thriller"

            // Lens preferences per shot type (JSON)
            $table->json('lens_preferences')->nullable();
            /*
             * Example:
             * {
             *   "establishing": "wide-angle 24mm lens",
             *   "medium": "standard 50mm lens",
             *   "close-up": "telephoto 85mm lens, shallow depth of field",
             *   "detail": "macro lens, extreme detail"
             * }
             */

            // Prompt template overrides (optional)
            $table->text('prompt_prefix')->nullable();  // Added before main prompt
            $table->text('prompt_suffix')->nullable();  // Added after main prompt

            // Admin settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('is_active');
            $table->index('is_default');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_genre_presets');
    }
};
