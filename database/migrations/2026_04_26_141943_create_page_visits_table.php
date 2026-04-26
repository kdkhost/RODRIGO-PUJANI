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
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('path')->index();
            $table->string('route_name')->nullable()->index();
            $table->string('page_title')->nullable();
            $table->string('page_slug')->nullable()->index();
            $table->text('referrer')->nullable();
            $table->string('session_id')->nullable()->index();
            $table->string('ip_hash')->nullable()->index();
            $table->string('device_type')->nullable()->index();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('country')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('visited_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
