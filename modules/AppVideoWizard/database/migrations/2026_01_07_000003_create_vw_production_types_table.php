<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vw_production_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('vw_production_types')->onDelete('cascade');
            $table->string('slug', 100);
            $table->string('name', 255);
            $table->string('icon', 50)->nullable();
            $table->text('description')->nullable();
            $table->json('characteristics')->nullable();
            $table->string('default_narration', 50)->nullable();
            $table->unsignedInteger('suggested_duration_min')->nullable();
            $table->unsignedInteger('suggested_duration_max')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('parent_id');
            $table->index('slug');
            $table->index('is_active');
            $table->index('sort_order');

            // Unique slug within same parent
            $table->unique(['parent_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_production_types');
    }
};
