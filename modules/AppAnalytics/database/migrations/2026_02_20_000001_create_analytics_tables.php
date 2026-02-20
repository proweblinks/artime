<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('team_id')->index();
            $table->integer('account_id')->index();
            $table->string('social_network', 30)->index();
            $table->date('snapshot_date')->index();
            $table->json('metrics');
            $table->json('top_posts')->nullable();
            $table->integer('created')->nullable();
            $table->unique(['account_id', 'snapshot_date']);
        });

        Schema::create('analytics_ai_insights', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('team_id')->index();
            $table->integer('account_id')->nullable();
            $table->string('social_network', 30)->nullable();
            $table->string('insight_type', 50);
            $table->text('content');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('created')->nullable();
            $table->index(['team_id', 'insight_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_ai_insights');
        Schema::dropIfExists('analytics_snapshots');
    }
};
