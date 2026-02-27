<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wizard_stock_media', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('path');
            $table->string('disk_path');
            $table->string('checksum', 64)->unique();
            $table->enum('type', ['image', 'video']);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('width')->default(0);
            $table->unsignedInteger('height')->default(0);
            $table->decimal('duration', 8, 2)->nullable();
            $table->decimal('fps', 6, 2)->nullable();
            $table->string('category', 100);
            $table->string('title');
            $table->text('tags')->nullable();
            $table->text('description')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->enum('orientation', ['landscape', 'portrait', 'square'])->default('landscape');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('type');
            $table->index('orientation');
            $table->index('is_active');
        });

        // Add FULLTEXT index for MySQL (skip for SQLite)
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement(
                'ALTER TABLE wizard_stock_media ADD FULLTEXT idx_stock_fulltext (title, tags, description)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wizard_stock_media');
    }
};
