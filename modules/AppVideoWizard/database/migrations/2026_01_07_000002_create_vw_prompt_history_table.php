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
        Schema::create('vw_prompt_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')->constrained('vw_prompts')->onDelete('cascade');
            $table->unsignedInteger('version');
            $table->longText('prompt_template');
            $table->json('variables')->nullable();
            $table->string('model', 100)->nullable();
            $table->decimal('temperature', 2, 1)->nullable();
            $table->unsignedInteger('max_tokens')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('change_notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['prompt_id', 'version']);
            $table->index('changed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_prompt_history');
    }
};
