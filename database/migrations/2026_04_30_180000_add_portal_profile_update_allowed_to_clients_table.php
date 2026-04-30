<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            if (! Schema::hasColumn('clients', 'portal_profile_update_allowed')) {
                $table->boolean('portal_profile_update_allowed')
                    ->default(false)
                    ->after('portal_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            if (Schema::hasColumn('clients', 'portal_profile_update_allowed')) {
                $table->dropColumn('portal_profile_update_allowed');
            }
        });
    }
};
