<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix column sizes in vw_settings table.
     * Addresses "Data too long for column" errors.
     */
    public function up(): void
    {
        // Use raw SQL to modify columns - more reliable across MySQL versions
        DB::statement('ALTER TABLE vw_settings MODIFY value TEXT NULL');
        DB::statement('ALTER TABLE vw_settings MODIFY default_value TEXT NULL');
        DB::statement('ALTER TABLE vw_settings MODIFY category VARCHAR(50) NOT NULL DEFAULT "general"');
        DB::statement('ALTER TABLE vw_settings MODIFY input_help VARCHAR(1000) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - larger columns don't hurt
    }
};
