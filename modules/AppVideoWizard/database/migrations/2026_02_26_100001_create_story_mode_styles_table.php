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
        Schema::create('story_mode_styles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->enum('category', ['illustration', 'animation', 'artistic', 'realistic', 'custom'])->default('illustration');
            $table->string('description')->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->text('style_instruction'); // prompt modifier for image generation
            $table->string('style_reference_image', 500)->nullable(); // reference image path
            $table->json('config')->nullable(); // additional style params: color_palette, line_weight, texture, etc.
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_mode_styles');
    }
};
