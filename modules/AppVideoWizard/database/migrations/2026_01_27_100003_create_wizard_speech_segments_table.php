<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * wizard_speech_segments table stores speech/dialogue segments for each scene.
     * These were previously nested arrays within script['scenes'][]['speechSegments'].
     * Supports narrator, character dialogue, and voice-over segments with timing data.
     */
    public function up(): void
    {
        Schema::create('wizard_speech_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_id')
                ->constrained('wizard_scenes')
                ->onDelete('cascade');

            // Order within scene
            $table->unsignedTinyInteger('order')->default(0);

            // Segment data (from SpeechSegment service class)
            $table->string('type', 20)->default('narrator');
            $table->text('text');
            $table->string('speaker', 100)->nullable();
            $table->string('character_id', 50)->nullable();
            $table->string('voice_id', 50)->nullable();

            // Timing (set after audio generation)
            $table->float('start_time')->nullable();
            $table->float('duration')->nullable();

            // Generated audio
            $table->string('audio_url', 500)->nullable();

            // Additional attributes
            $table->string('emotion', 50)->nullable();
            $table->boolean('needs_lip_sync')->default(false);

            $table->timestamps();

            // Indexes for common queries
            $table->index(['scene_id', 'order']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wizard_speech_segments');
    }
};
