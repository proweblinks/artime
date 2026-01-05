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
        Schema::create('wizard_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');

            // Project metadata
            $table->string('name')->default('Untitled Video');
            $table->enum('status', ['draft', 'processing', 'completed', 'failed'])->default('draft');
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->unsignedTinyInteger('max_reached_step')->default(1);

            // Platform configuration
            $table->string('platform')->nullable(); // youtube-shorts, tiktok, etc.
            $table->string('aspect_ratio')->default('16:9');
            $table->unsignedInteger('target_duration')->default(60); // seconds

            // Production configuration
            $table->string('format')->nullable(); // widescreen, vertical, etc.
            $table->string('production_type')->nullable(); // social, movie
            $table->string('production_subtype')->nullable(); // viral, educational, etc.

            // Content configuration (JSON)
            $table->json('concept')->nullable(); // rawInput, keywords, refinedConcept, etc.
            $table->json('character_intelligence')->nullable(); // characters, narrator config
            $table->json('content_config')->nullable(); // niche, style, tone, etc.

            // Script data (JSON)
            $table->json('script')->nullable(); // title, hook, scenes, cta

            // Storyboard data (JSON)
            $table->json('storyboard')->nullable(); // scenes with images, prompts

            // Animation data (JSON)
            $table->json('animation')->nullable(); // voiceover settings, animated scenes

            // Assembly data (JSON)
            $table->json('assembly')->nullable(); // transitions, music, captions

            // Export settings (JSON)
            $table->json('export_config')->nullable(); // quality, format, etc.

            // Output
            $table->string('output_url')->nullable();
            $table->string('thumbnail_url')->nullable();

            $table->timestamps();

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
        Schema::dropIfExists('wizard_projects');
    }
};
