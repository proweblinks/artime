<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * wizard_scenes table stores normalized scene data that was previously
     * stored in JSON columns (script, storyboard, animation) on wizard_projects.
     * This enables lazy loading, proper relationships, and reduced Livewire payloads.
     */
    public function up(): void
    {
        Schema::create('wizard_scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('wizard_projects')
                ->onDelete('cascade');

            // Order within project
            $table->unsignedInteger('order')->default(0);

            // Script data (from $script['scenes'][])
            $table->text('narration')->nullable();
            $table->text('visual_prompt')->nullable();
            $table->unsignedSmallInteger('duration')->default(8);
            $table->string('speech_type', 20)->default('voiceover');
            $table->string('transition', 20)->default('cut');

            // Storyboard data (from $storyboard['scenes'][])
            $table->string('image_url', 500)->nullable();
            $table->string('image_status', 20)->default('pending');
            $table->text('image_prompt')->nullable();
            $table->string('image_job_id', 100)->nullable();

            // Animation data (from $animation['scenes'][])
            $table->string('video_url', 500)->nullable();
            $table->string('video_status', 20)->default('pending');
            $table->string('voiceover_url', 500)->nullable();

            // Flexible metadata JSON for less-frequent fields:
            // - voiceover settings, character associations, etc.
            $table->json('scene_metadata')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index(['project_id', 'order']);
            $table->index('image_status');
            $table->index('video_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wizard_scenes');
    }
};
