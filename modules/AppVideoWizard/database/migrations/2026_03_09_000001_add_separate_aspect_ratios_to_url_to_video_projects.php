<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('url_to_video_projects', function (Blueprint $table) {
            $table->string('image_aspect_ratio')->default('9:16')->after('aspect_ratio');
            $table->string('video_aspect_ratio')->default('9:16')->after('image_aspect_ratio');
        });

        // Copy existing aspect_ratio into both new columns for existing projects
        DB::table('url_to_video_projects')->update([
            'image_aspect_ratio' => DB::raw('aspect_ratio'),
            'video_aspect_ratio' => DB::raw('aspect_ratio'),
        ]);
    }

    public function down(): void
    {
        Schema::table('url_to_video_projects', function (Blueprint $table) {
            $table->dropColumn(['image_aspect_ratio', 'video_aspect_ratio']);
        });
    }
};
