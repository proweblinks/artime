<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_business_dna', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->string('website_url', 500);
            $table->string('brand_name', 255)->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->json('colors')->nullable();
            $table->json('fonts')->nullable();
            $table->text('tagline')->nullable();
            $table->json('brand_values')->nullable();
            $table->json('brand_aesthetic')->nullable();
            $table->json('brand_tone')->nullable();
            $table->text('business_overview')->nullable();
            $table->json('images')->nullable();
            $table->json('raw_scrape_data')->nullable();
            $table->enum('status', ['pending', 'analyzing', 'ready', 'failed'])->default('pending');
            $table->timestamps();

            $table->unique('team_id');
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
        });

        Schema::create('content_campaign_ideas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('dna_id');
            $table->string('title', 500);
            $table->text('description');
            $table->text('prompt')->nullable();
            $table->boolean('is_dna_suggestion')->default(false);
            $table->enum('status', ['pending', 'used', 'dismissed'])->default('pending');
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->foreign('dna_id')->references('id')->on('content_business_dna')->cascadeOnDelete();
        });

        Schema::create('content_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('dna_id');
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->text('prompt')->nullable();
            $table->string('aspect_ratio', 10)->default('9:16');
            $table->enum('status', ['generating', 'ready', 'failed'])->default('generating');
            $table->boolean('is_suggestion')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'created_at']);
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->foreign('dna_id')->references('id')->on('content_business_dna')->cascadeOnDelete();
        });

        Schema::create('content_creatives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('team_id');
            $table->enum('type', ['image', 'video'])->default('image');
            $table->string('image_path', 500)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('video_path', 500)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->text('header_text')->nullable();
            $table->string('header_font', 100)->default('Roboto');
            $table->string('header_color', 20)->default('#ffffff');
            $table->integer('header_size')->default(40);
            $table->integer('header_height')->default(42);
            $table->boolean('header_visible')->default(true);
            $table->text('description_text')->nullable();
            $table->string('desc_font', 100)->default('Roboto');
            $table->string('desc_color', 20)->default('#ffffff');
            $table->integer('desc_size')->default(16);
            $table->integer('desc_height')->default(19);
            $table->boolean('desc_visible')->default(true);
            $table->string('cta_text', 255)->nullable();
            $table->string('cta_font', 100)->default('Roboto');
            $table->string('cta_color', 20)->default('#ffffff');
            $table->integer('cta_size')->default(14);
            $table->boolean('cta_visible')->default(false);
            $table->integer('current_version')->default(1);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('campaign_id');
            $table->index('team_id');
            $table->foreign('campaign_id')->references('id')->on('content_campaigns')->cascadeOnDelete();
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
        });

        Schema::create('content_creative_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creative_id');
            $table->integer('version_number');
            $table->string('image_path', 500)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->text('header_text')->nullable();
            $table->text('description_text')->nullable();
            $table->string('cta_text', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['creative_id', 'version_number']);
            $table->foreign('creative_id')->references('id')->on('content_creatives')->cascadeOnDelete();
        });

        Schema::create('content_photoshoots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('dna_id')->nullable();
            $table->enum('type', ['template', 'freeform'])->default('freeform');
            $table->text('prompt')->nullable();
            $table->string('product_image_path', 500)->nullable();
            $table->string('template_id', 100)->nullable();
            $table->string('aspect_ratio', 10)->default('9:16');
            $table->enum('status', ['generating', 'ready', 'failed'])->default('generating');
            $table->json('results')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'created_at']);
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->foreign('dna_id')->references('id')->on('content_business_dna')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_photoshoots');
        Schema::dropIfExists('content_creative_versions');
        Schema::dropIfExists('content_creatives');
        Schema::dropIfExists('content_campaigns');
        Schema::dropIfExists('content_campaign_ideas');
        Schema::dropIfExists('content_business_dna');
    }
};
