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
        Schema::create('wizard_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('wizard_projects')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Asset type
            $table->enum('type', ['image', 'audio', 'video', 'voiceover', 'music']);

            // Asset metadata
            $table->string('name')->nullable();
            $table->string('path'); // Storage path
            $table->string('url')->nullable(); // Public URL
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0); // bytes

            // Scene association
            $table->unsignedInteger('scene_index')->nullable();
            $table->string('scene_id')->nullable();

            // Additional metadata
            $table->json('metadata')->nullable(); // duration, dimensions, etc.

            $table->timestamps();

            // Indexes
            $table->index(['project_id', 'type']);
            $table->index(['project_id', 'scene_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wizard_assets');
    }
};
