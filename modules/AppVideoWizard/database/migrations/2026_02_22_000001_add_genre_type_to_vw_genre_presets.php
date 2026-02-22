<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add genre_type, icon, and blending_traits columns to vw_genre_presets.
     * Supports multi-genre selection: primary genres + modifier sub-genres.
     */
    public function up(): void
    {
        Schema::table('vw_genre_presets', function (Blueprint $table) {
            $table->enum('genre_type', ['primary', 'modifier'])->default('primary')->after('category');
            $table->string('icon', 10)->nullable()->after('name');
            $table->json('blending_traits')->nullable()->after('lens_preferences');

            $table->index('genre_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vw_genre_presets', function (Blueprint $table) {
            $table->dropIndex(['genre_type']);
            $table->dropColumn(['genre_type', 'icon', 'blending_traits']);
        });
    }
};
