<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update Story Mode defaults for longer 60s videos.
     */
    public function up(): void
    {
        DB::table('options')
            ->where('name', 'story_mode_default_duration')
            ->update(['value' => '60']);

        DB::table('options')
            ->where('name', 'story_mode_max_words')
            ->update(['value' => '700']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('options')
            ->where('name', 'story_mode_default_duration')
            ->update(['value' => '35']);

        DB::table('options')
            ->where('name', 'story_mode_max_words')
            ->update(['value' => '450']);
    }
};
