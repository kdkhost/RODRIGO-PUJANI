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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('phone');
            $table->string('timezone')->default('America/Sao_Paulo')->after('avatar_path');
            $table->boolean('is_active')->default(true)->after('timezone');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'phone',
                'avatar_path',
                'timezone',
                'is_active',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
