<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_business_dna', function (Blueprint $table) {
            $table->dropUnique(['team_id']);
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::table('content_business_dna', function (Blueprint $table) {
            $table->dropIndex(['team_id']);
            $table->unique('team_id');
        });
    }
};
