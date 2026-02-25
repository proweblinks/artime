<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Story Mode configuration options with their defaults.
     */
    protected array $options = [
        'story_mode_enabled' => '1',
        'story_mode_ai_engine' => 'gemini',
        'story_mode_ai_model' => 'gemini-2.5-flash',
        'story_mode_image_model' => 'nanobanana-pro',
        'story_mode_video_model' => 'seedance-1.5-pro',
        'story_mode_tts_provider' => 'auto',
        'story_mode_default_voice' => 'nova',
        'story_mode_default_aspect' => '9:16',
        'story_mode_max_duration' => '60',
        'story_mode_default_duration' => '35',
        'story_mode_max_words' => '450',
        'story_mode_captions_enabled' => '1',
        'story_mode_music_enabled' => '1',
        'story_mode_music_volume' => '0.15',
        'story_mode_export_quality' => 'balanced',
        'story_mode_export_resolution' => '1080p',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->options as $name => $value) {
            // Only insert if option doesn't already exist
            $exists = DB::table('options')->where('name', $name)->exists();
            if (!$exists) {
                DB::table('options')->insert([
                    'name' => $name,
                    'value' => $value,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('options')->whereIn('name', array_keys($this->options))->delete();
    }
};
