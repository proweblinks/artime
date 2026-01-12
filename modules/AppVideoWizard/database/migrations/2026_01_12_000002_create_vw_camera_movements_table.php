<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Camera movements based on Higgsfield's 50+ cinematic motion presets.
     */
    public function up(): void
    {
        Schema::create('vw_camera_movements', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 255);
            $table->enum('category', [
                'zoom',       // Zoom movements: zoom in/out, crash zoom, dolly zoom
                'dolly',      // Dolly movements: in/out, tracking left/right
                'crane',      // Crane movements: up/down, over head
                'pan_tilt',   // Pan and tilt: left/right, up/down, whip pan
                'arc',        // Arc movements: left/right, orbit
                'specialty',  // Special: static, handheld, steadicam, FPV
            ])->default('specialty');
            $table->text('description')->nullable();

            // Prompt syntax for AI video generation
            $table->string('prompt_syntax', 255);
            // Example: "camera slowly dollies in", "rapid crash zoom"

            // Movement characteristics
            $table->enum('intensity', ['subtle', 'moderate', 'dynamic', 'intense'])->default('moderate');
            $table->unsignedTinyInteger('typical_duration_min')->default(3);
            $table->unsignedTinyInteger('typical_duration_max')->default(10);

            // Stacking compatibility (which movements can combine)
            $table->json('stackable_with')->nullable();
            /*
             * Movement slugs that can be stacked with this one
             * ["pan-left", "pan-right", "tilt-up", "crane-up"]
             */

            // Shot type compatibility
            $table->json('best_for_shot_types')->nullable();
            /*
             * Shot types this movement works best with
             * ["close-up", "medium", "wide"]
             */

            // Emotional/purpose mapping
            $table->json('best_for_emotions')->nullable();
            /*
             * Emotional purposes this movement serves
             * ["tension", "reveal", "intimacy", "drama"]
             */

            // Continuity support
            $table->string('natural_continuation', 100)->nullable();
            // What movement naturally follows this one

            $table->string('ending_state', 255)->nullable();
            // How the camera typically ends after this movement
            // "closer to subject", "further from subject", "same position rotated"

            // Admin settings
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('intensity');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_camera_movements');
    }
};
