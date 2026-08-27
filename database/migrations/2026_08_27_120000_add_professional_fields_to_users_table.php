<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('professional_title', 120)->nullable()->after('document_number');
            $table->string('oab_number', 30)->nullable()->after('professional_title');
            $table->char('oab_state', 2)->nullable()->after('oab_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['professional_title', 'oab_number', 'oab_state']);
        });
    }
};
