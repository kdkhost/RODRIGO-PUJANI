<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_calendar_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('google_account_email')->nullable();
            $table->longText('access_token');
            $table->longText('refresh_token')->nullable();
            $table->dateTime('token_expires_at')->nullable()->index();
            $table->json('scopes')->nullable();
            $table->string('calendar_id')->default('primary');
            $table->string('calendar_name')->nullable();
            $table->boolean('sync_enabled')->default(true)->index();
            $table->longText('sync_token')->nullable();
            $table->dateTime('last_synced_at')->nullable()->index();
            $table->dateTime('last_success_at')->nullable();
            $table->dateTime('last_failure_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('google_calendar_event_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('google_calendar_connection_id')->constrained('google_calendar_connections')->cascadeOnDelete();
            $table->foreignId('calendar_event_id')->nullable()->constrained('calendar_events')->nullOnDelete();
            $table->string('google_event_id');
            $table->string('google_ical_uid')->nullable()->index();
            $table->string('etag')->nullable();
            $table->string('sync_hash', 64)->nullable()->index();
            $table->dateTime('google_updated_at')->nullable();
            $table->dateTime('last_pushed_at')->nullable();
            $table->dateTime('last_pulled_at')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['google_calendar_connection_id', 'google_event_id'], 'gcal_connection_event_unique');
            $table->unique(['google_calendar_connection_id', 'calendar_event_id'], 'gcal_connection_local_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_calendar_event_mappings');
        Schema::dropIfExists('google_calendar_connections');
    }
};
