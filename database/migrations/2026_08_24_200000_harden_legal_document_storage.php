<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_documents', function (Blueprint $table): void {
            $table->string('disk', 40)->default('legacy_public')->after('path')->index();
            $table->string('sha256', 64)->nullable()->after('size')->index();
            $table->string('storage_status', 30)->default('legacy')->after('sha256')->index();
            $table->timestamp('scanned_at')->nullable()->after('storage_status');
        });
    }

    public function down(): void
    {
        Schema::table('legal_documents', function (Blueprint $table): void {
            $table->dropIndex(['disk']);
            $table->dropIndex(['sha256']);
            $table->dropIndex(['storage_status']);
            $table->dropColumn(['disk', 'sha256', 'storage_status', 'scanned_at']);
        });
    }
};
