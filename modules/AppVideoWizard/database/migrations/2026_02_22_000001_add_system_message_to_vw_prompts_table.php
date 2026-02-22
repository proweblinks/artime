<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vw_prompts', function (Blueprint $table) {
            $table->longText('system_message')->nullable()->after('prompt_template');
        });

        Schema::table('vw_prompt_history', function (Blueprint $table) {
            $table->longText('system_message')->nullable()->after('prompt_template');
        });
    }

    public function down(): void
    {
        Schema::table('vw_prompts', function (Blueprint $table) {
            $table->dropColumn('system_message');
        });

        Schema::table('vw_prompt_history', function (Blueprint $table) {
            $table->dropColumn('system_message');
        });
    }
};
