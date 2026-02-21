<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_creatives', function (Blueprint $table) {
            $table->string('source_type', 20)->default('ai')->after('type');
            $table->string('source_image_path')->nullable()->after('source_type');
            $table->string('style_preset', 50)->nullable()->after('source_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('content_creatives', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_image_path', 'style_preset']);
        });
    }
};
