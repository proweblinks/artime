<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_business_dna', function (Blueprint $table) {
            $table->tinyInteger('progress_step')->default(0)->after('status');
            $table->string('progress_message', 255)->nullable()->after('progress_step');
        });
    }

    public function down(): void
    {
        Schema::table('content_business_dna', function (Blueprint $table) {
            $table->dropColumn(['progress_step', 'progress_message']);
        });
    }
};
