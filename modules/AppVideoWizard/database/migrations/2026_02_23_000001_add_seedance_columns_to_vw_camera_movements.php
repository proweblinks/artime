<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vw_camera_movements', function (Blueprint $table) {
            $table->boolean('seedance_compatible')->default(true)->after('ending_state');
            $table->string('seedance_prompt_syntax', 512)->nullable()->after('seedance_compatible');
            $table->string('seedance_shot_size', 20)->nullable()->after('seedance_prompt_syntax');
        });
    }

    public function down(): void
    {
        Schema::table('vw_camera_movements', function (Blueprint $table) {
            $table->dropColumn(['seedance_compatible', 'seedance_prompt_syntax', 'seedance_shot_size']);
        });
    }
};
