<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vw_workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('vw_workflows')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('wizard_projects')->cascadeOnDelete();
            $table->string('status', 30)->default('pending'); // pending, running, paused, completed, failed
            $table->string('current_node_id', 100)->nullable();
            $table->json('data_bus')->nullable();
            $table->json('node_results')->nullable();
            $table->json('workflow_snapshot')->nullable(); // Copy of workflow nodes/edges at execution time (for edits)
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('project_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vw_workflow_executions');
    }
};
