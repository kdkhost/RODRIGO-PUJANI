<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'pref_receive_internal_messages')) {
                $table->boolean('pref_receive_internal_messages')
                    ->default(true)
                    ->after('is_active');
            }

            if (! Schema::hasColumn('users', 'pref_receive_whatsapp_messages')) {
                $table->boolean('pref_receive_whatsapp_messages')
                    ->default(true)
                    ->after('pref_receive_internal_messages');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'pref_receive_whatsapp_messages')) {
                $table->dropColumn('pref_receive_whatsapp_messages');
            }

            if (Schema::hasColumn('users', 'pref_receive_internal_messages')) {
                $table->dropColumn('pref_receive_internal_messages');
            }
        });
    }
};

