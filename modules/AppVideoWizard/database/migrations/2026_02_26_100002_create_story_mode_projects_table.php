<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('story_mode_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');

            // Project metadata
            $table->string('title')->default('Untitled Story');
            $table->text('prompt'); // original user input

            // Style configuration
            $table->unsignedBigInteger('style_id')->nullable();
            $table->foreign('style_id')->references('id')->on('story_mode_styles')->onDelete('set null');
            $table->text('custom_style_instruction')->nullable();
            $table->string('custom_style_image', 500)->nullable();

            // Video configuration
            $table->enum('aspect_ratio', ['9:16', '16:9', '1:1'])->default('9:16');

            // Voice configuration
            $table->string('voice_id', 100)->nullable(); // e.g. 'nova', 'kokoro_bella'
            $table->string('voice_provider', 50)->nullable();

            // Generated content
            $table->text('transcript')->nullable(); // the generated/edited script
            $table->unsignedInteger('transcript_word_count')->nullable();
            $table->json('visual_script')->nullable(); // per-segment image prompts
            $table->json('scenes')->nullable(); // generated scene data with images/audio/video URLs

            // Pipeline status
            $table->enum('status', [
                'draft',
                'generating_script',
                'script_ready',
                'generating_voiceover',
                'generating_visual_script',
                'generating_images',
                'generating_video',
                'assembling',
                'ready',
                'failed',
            ])->default('draft');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('current_stage', 50)->nullable();

            // Output
            $table->string('video_path', 500)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->unsignedInteger('video_duration')->nullable(); // seconds
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();

            // Error handling
            $table->text('error_message')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // AI engine used, generation times, etc.

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['team_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_mode_projects');
    }
};
