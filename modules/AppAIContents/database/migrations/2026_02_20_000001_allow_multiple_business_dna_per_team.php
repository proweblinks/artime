<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_business_dna', function (Blueprint $table) {
            // Must drop FK first before dropping the unique index (MySQL requirement)
            $table->dropForeign(['team_id']);
            $table->dropUnique(['team_id']);
            $table->index('team_id');
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('content_business_dna', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropIndex(['team_id']);
            $table->unique('team_id');
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
        });
    }
};
