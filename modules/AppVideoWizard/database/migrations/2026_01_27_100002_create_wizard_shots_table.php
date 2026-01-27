<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * wizard_shots table stores multi-shot decomposition data for Hollywood-style
     * shot-based workflows. Each scene can have multiple shots with individual
     * prompts, camera movements, and generated assets.
     */
    public function up(): void
    {
        Schema::create('wizard_shots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_id')
                ->constrained('wizard_scenes')
                ->onDelete('cascade');

            // Order within scene
            $table->unsignedTinyInteger('order')->default(0);

            // Prompts
            $table->text('image_prompt')->nullable();
            $table->text('video_prompt')->nullable();

            // Technical specs
            $table->string('camera_movement', 50)->nullable();
            $table->unsignedTinyInteger('duration')->default(5);
            $table->string('duration_class', 20)->default('short');

            // Generated assets
            $table->string('image_url', 500)->nullable();
            $table->string('image_status', 20)->default('pending');
            $table->string('video_url', 500)->nullable();
            $table->string('video_status', 20)->default('pending');

            // Dialogue/speech for this shot
            $table->text('dialogue')->nullable();

            // Flexible metadata JSON for:
            // - speaking_characters array
            // - additional shot-specific data
            $table->json('shot_metadata')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index(['scene_id', 'order']);
            $table->index('image_status');
            $table->index('video_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wizard_shots');
    }
};
