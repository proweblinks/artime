<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_tool_history', function (Blueprint $table) {
            $table->id();
            $table->string('id_secure', 32)->unique();
            $table->unsignedBigInteger('team_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('tool', 50);
            $table->string('platform', 30)->default('youtube');
            $table->string('title', 500)->nullable();
            $table->json('input_data')->nullable();
            $table->json('result_data')->nullable();
            $table->tinyInteger('status')->default(2); // 0=failed, 1=completed, 2=processing
            $table->unsignedInteger('credits_used')->default(0);
            $table->unsignedInteger('created')->default(0);
            $table->unsignedInteger('changed')->default(0);

            $table->index(['team_id', 'tool', 'created']);
        });

        Schema::create('ai_tool_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('history_id');
            $table->string('type', 30);
            $table->string('file_path', 500);
            $table->json('metadata')->nullable();
            $table->unsignedInteger('created')->default(0);

            $table->foreign('history_id')->references('id')->on('ai_tool_history')->onDelete('cascade');
            $table->index('history_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tool_assets');
        Schema::dropIfExists('ai_tool_history');
    }
};
