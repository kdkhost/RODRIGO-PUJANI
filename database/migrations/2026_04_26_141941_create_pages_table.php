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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('menu_title')->nullable();
            $table->string('template')->default('default');
            $table->string('theme_variant')->default('public');
            $table->string('status')->default('draft')->index();
            $table->boolean('is_home')->default(false);
            $table->boolean('show_in_menu')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('hero_title')->nullable();
            $table->text('hero_subtitle')->nullable();
            $table->string('hero_cta_label')->nullable();
            $table->string('hero_cta_url')->nullable();
            $table->string('cover_path')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->json('content')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
