<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_business_dna', function (Blueprint $table) {
            $table->string('language', 100)->nullable()->after('business_overview');
            $table->string('language_code', 10)->nullable()->after('language');
        });
    }

    public function down(): void
    {
        Schema::table('content_business_dna', function (Blueprint $table) {
            $table->dropColumn(['language', 'language_code']);
        });
    }
};
