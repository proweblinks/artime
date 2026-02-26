<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Story Mode transition and continuity options.
     */
    protected array $options = [
        'story_mode_transition_type' => 'fade',
        'story_mode_crossfade_duration' => '0.5',
        'story_mode_fadeout_duration' => '1.5',
        'story_mode_frame_chaining' => '0',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->options as $name => $value) {
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
