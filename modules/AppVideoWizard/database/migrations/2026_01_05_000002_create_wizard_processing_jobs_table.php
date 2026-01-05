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
        Schema::create('wizard_processing_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('wizard_projects')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Job type
            $table->enum('type', [
                'script_generation',
                'concept_improvement',
                'image_generation',
                'voiceover_generation',
                'video_animation',
                'video_export'
            ]);

            // Job status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->unsignedTinyInteger('progress')->default(0); // 0-100
            $table->string('current_stage')->nullable();

            // Job data
            $table->json('input_data')->nullable(); // Input parameters
            $table->json('result_data')->nullable(); // Output data
            $table->text('error_message')->nullable();

            // External job tracking
            $table->string('external_job_id')->nullable(); // For tracking external API jobs
            $table->string('external_provider')->nullable(); // openai, runpod, etc.

            // Credits
            $table->unsignedInteger('credits_used')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['project_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index('external_job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wizard_processing_jobs');
    }
};
