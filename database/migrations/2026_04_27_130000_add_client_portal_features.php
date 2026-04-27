<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->boolean('portal_enabled')->default(false)->after('is_active')->index();
            $table->string('portal_access_code')->nullable()->after('portal_enabled');
            $table->timestamp('portal_access_code_updated_at')->nullable()->after('portal_access_code');
            $table->timestamp('portal_last_login_at')->nullable()->after('portal_access_code_updated_at');
            $table->string('portal_last_login_ip', 45)->nullable()->after('portal_last_login_at');
        });

        Schema::table('legal_cases', function (Blueprint $table): void {
            $table->boolean('portal_visible')->default(true)->after('is_active')->index();
            $table->longText('portal_summary')->nullable()->after('portal_visible');
            $table->string('tribunal_alias', 40)->nullable()->after('portal_summary')->index();
            $table->boolean('datajud_sync_enabled')->default(false)->after('tribunal_alias')->index();
            $table->dateTime('datajud_last_synced_at')->nullable()->after('datajud_sync_enabled')->index();
            $table->dateTime('latest_court_update_at')->nullable()->after('datajud_last_synced_at')->index();
        });

        Schema::create('legal_case_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_case_id')->constrained('legal_cases')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_id')->nullable()->index();
            $table->string('source', 30)->default('manual')->index();
            $table->string('update_type', 40)->default('procedural')->index();
            $table->string('title')->index();
            $table->longText('body')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->boolean('is_visible_to_client')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['legal_case_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_case_updates');

        Schema::table('legal_cases', function (Blueprint $table): void {
            $table->dropColumn([
                'portal_visible',
                'portal_summary',
                'tribunal_alias',
                'datajud_sync_enabled',
                'datajud_last_synced_at',
                'latest_court_update_at',
            ]);
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn([
                'portal_enabled',
                'portal_access_code',
                'portal_access_code_updated_at',
                'portal_last_login_at',
                'portal_last_login_ip',
            ]);
        });
    }
};
