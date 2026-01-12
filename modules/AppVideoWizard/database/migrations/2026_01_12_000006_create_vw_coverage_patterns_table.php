<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates vw_coverage_patterns table for Phase 4: Scene Type Detection.
     */
    public function up(): void
    {
        Schema::create('vw_coverage_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->string('scene_type', 50)->index(); // dialogue, action, montage, emotional, establishing, etc.
            $table->text('description')->nullable();

            // Pattern definition - ordered shot sequence
            $table->json('shot_sequence'); // ['master', 'two-shot', 'over-shoulder', 'close-up', 'reaction']

            // Detection keywords for auto-classification
            $table->json('detection_keywords')->nullable(); // ['dialogue', 'conversation', 'speaks']
            $table->json('negative_keywords')->nullable(); // Keywords that exclude this pattern

            // Visual cues for detection
            $table->json('visual_cues')->nullable(); // ['indoor', 'two_people', 'seated']

            // Recommended settings
            $table->string('recommended_pacing', 30)->default('balanced'); // slow, balanced, fast, dynamic
            $table->integer('min_shots')->default(2);
            $table->integer('max_shots')->default(10);
            $table->integer('typical_shot_duration')->default(6); // seconds

            // Transition rules
            $table->json('transition_rules')->nullable(); // Rules for transitioning between shots

            // Movement preferences
            $table->string('default_movement_intensity', 20)->default('moderate'); // subtle, moderate, dynamic, intense
            $table->json('preferred_movements')->nullable(); // ['push-in', 'dolly', 'static']

            // Usage tracking
            $table->unsignedInteger('usage_count')->default(0);
            $table->decimal('success_rate', 5, 2)->nullable(); // 0.00 - 100.00

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // System patterns can't be deleted
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('priority')->default(50); // Higher priority = checked first during detection

            $table->timestamps();

            // Indexes for common queries
            $table->index(['scene_type', 'is_active']);
            $table->index(['is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_coverage_patterns');
    }
};
