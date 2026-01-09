<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Camera specifications - lenses, film stocks, looks (Sora 2 best practices).
     */
    public function up(): void
    {
        Schema::create('vw_camera_specs', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 255);

            $table->enum('category', [
                'lens',        // Physical lens specs
                'camera_body', // Camera body/sensor
                'film_stock',  // Film stock looks
                'format'       // Aspect ratio/format
            ])->default('lens');

            // Lens specifications (for lens category)
            $table->string('focal_length', 50)->nullable();  // "85mm", "24-70mm"
            $table->string('aperture', 20)->nullable();      // "f/1.4", "f/2.8"
            $table->text('characteristics')->nullable();      // "creamy bokeh, sharp center"

            // Film stock/look (for film_stock category)
            $table->text('look_description')->nullable();    // "Kodak Portra 400: soft pastels"

            // What to add to AI prompts
            $table->text('prompt_text')->nullable();
            /*
             * "shot on 85mm portrait lens at f/1.4, beautiful bokeh, intimate compression"
             */

            // Best for mappings (JSON)
            $table->json('best_for_shots')->nullable();   // ["portrait", "close-up", "medium"]
            $table->json('best_for_genres')->nullable();  // ["drama", "romance", "documentary"]

            // Admin settings
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_camera_specs');
    }
};
