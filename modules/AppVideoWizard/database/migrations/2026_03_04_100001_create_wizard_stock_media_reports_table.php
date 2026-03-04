<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wizard_stock_media_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_media_id')->constrained('wizard_stock_media')->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['stock_media_id', 'ip_address']);
        });

        Schema::table('wizard_stock_media', function (Blueprint $table) {
            $table->unsignedInteger('report_count')->default(0)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('wizard_stock_media', function (Blueprint $table) {
            $table->dropColumn('report_count');
        });

        Schema::dropIfExists('wizard_stock_media_reports');
    }
};
