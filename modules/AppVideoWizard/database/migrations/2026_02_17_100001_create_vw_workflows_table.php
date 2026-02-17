<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vw_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('category', 50)->default('system'); // system, user, shared
            $table->string('video_engine', 50)->default('seedance'); // seedance, infinitetalk, any
            $table->json('nodes');
            $table->json('edges');
            $table->json('defaults')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->index('category');
            $table->index('video_engine');
            $table->index(['user_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vw_workflows');
    }
};
