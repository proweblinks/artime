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
        Schema::create('url_to_video_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');

            // Project metadata
            $table->string('title')->default('Untitled Video');
            $table->text('prompt')->nullable(); // optional user instruction/angle

            // URL source
            $table->string('source_url', 1000);
            $table->enum('source_type', [
                'article', 'youtube_video', 'news', 'linkedin', 'twitter', 'newsletter', 'prompt',
            ])->default('article');
            $table->json('extracted_content')->nullable(); // raw scraped data
            $table->json('content_brief')->nullable(); // AI-analyzed content structure

            // Video configuration
            $table->enum('aspect_ratio', ['9:16', '16:9', '1:1'])->default('9:16');

            // Voice configuration
            $table->string('voice_id', 100)->nullable();
            $table->string('voice_provider', 50)->nullable();

            // Generated content
            $table->text('transcript')->nullable();
            $table->unsignedInteger('transcript_word_count')->nullable();
            $table->json('visual_script')->nullable();
            $table->json('scenes')->nullable();

            // Pipeline status
            $table->enum('status', [
                'draft',
                'extracting_content',
                'analyzing_content',
                'generating_script',
                'script_ready',
                'generating_voiceover',
                'generating_visual_script',
                'generating_images',
                'generating_video',
                'assembling',
                'ready',
                'failed',
                'cancelled',
            ])->default('draft');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('current_stage', 50)->nullable();

            // Output
            $table->string('video_path', 500)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->unsignedInteger('video_duration')->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();

            // Error handling
            $table->text('error_message')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

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
        Schema::dropIfExists('url_to_video_projects');
    }
};
