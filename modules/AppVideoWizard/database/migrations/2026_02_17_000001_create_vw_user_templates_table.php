<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vw_user_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->string('icon', 50)->default('fa-solid fa-bookmark');
            $table->boolean('is_shared')->default(false);
            $table->text('video_prompt');
            $table->json('concept')->nullable();
            $table->json('seedance_settings')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['team_id', 'is_shared']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vw_user_templates');
    }
};
