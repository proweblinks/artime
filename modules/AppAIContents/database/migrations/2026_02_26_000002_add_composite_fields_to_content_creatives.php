<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_creatives', function (Blueprint $table) {
            $table->unsignedBigInteger('layout_template_id')->nullable()->after('style_preset');
            $table->string('composite_image_path', 500)->nullable()->after('video_url');
            $table->string('composite_image_url', 500)->nullable()->after('composite_image_path');
            $table->string('composite_status', 20)->default('pending')->after('composite_image_url');

            $table->foreign('layout_template_id')
                ->references('id')
                ->on('creative_layout_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('content_creatives', function (Blueprint $table) {
            $table->dropForeign(['layout_template_id']);
            $table->dropColumn(['layout_template_id', 'composite_image_path', 'composite_image_url', 'composite_status']);
        });
    }
};
