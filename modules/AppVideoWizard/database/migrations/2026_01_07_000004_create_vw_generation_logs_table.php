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
        Schema::create('vw_generation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('wizard_projects')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->string('prompt_slug', 100);
            $table->unsignedInteger('prompt_version')->nullable();
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->enum('status', ['success', 'failed', 'partial'])->default('success');
            $table->text('error_message')->nullable();
            $table->decimal('estimated_cost', 10, 6)->nullable(); // Cost in USD
            $table->timestamps();

            // Indexes for analytics
            $table->index('prompt_slug');
            $table->index('status');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['team_id', 'created_at']);
            $table->index(['project_id', 'prompt_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_generation_logs');
    }
};
