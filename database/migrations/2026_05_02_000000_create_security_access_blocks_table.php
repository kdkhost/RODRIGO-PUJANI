<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_access_blocks', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 40)->index();
            $table->string('value', 255)->index();
            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('blocked_by_user_id')->nullable()->index();
            $table->unsignedBigInteger('released_by_user_id')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('last_hit_at')->nullable();
            $table->unsignedInteger('hits')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['type', 'value'], 'security_blocks_type_value_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_access_blocks');
    }
};

