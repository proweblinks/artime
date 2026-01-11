<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Dynamic settings for Video Wizard - Shot Intelligence, Animation, Durations, etc.
     */
    public function up(): void
    {
        Schema::create('vw_settings', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 255);
            $table->enum('category', [
                'shot_intelligence',  // AI shot decomposition settings
                'animation',          // Animation model settings (MiniMax, Multitalk)
                'duration',           // Duration configurations
                'scene',              // Scene processing settings
                'export',             // Export and rendering settings
                'general',            // General wizard settings
            ])->default('general');

            $table->text('description')->nullable();

            // Value storage - supports multiple types
            $table->string('value_type', 20)->default('string'); // string, integer, float, boolean, json, array
            $table->text('value')->nullable();
            $table->text('default_value')->nullable();

            // Validation constraints
            $table->integer('min_value')->nullable();
            $table->integer('max_value')->nullable();
            $table->json('allowed_values')->nullable(); // For enum-like settings

            // UI hints
            $table->string('input_type', 50)->default('text'); // text, number, select, checkbox, textarea, json_editor
            $table->string('input_placeholder', 255)->nullable();
            $table->string('input_help', 500)->nullable();
            $table->string('icon', 50)->nullable();

            // Admin settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // System settings can't be deleted
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('is_active');
            $table->index(['category', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_settings');
    }
};
