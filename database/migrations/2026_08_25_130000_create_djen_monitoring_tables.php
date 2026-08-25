<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('djen_monitors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_case_id')->nullable()->constrained('legal_cases')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->index();
            $table->string('label');
            $table->string('process_number_normalized', 20)->nullable()->index();
            $table->string('oab_number_normalized', 30)->nullable()->index();
            $table->char('oab_state', 2)->nullable()->index();
            $table->char('fingerprint', 64)->unique();
            $table->boolean('enabled')->default(true)->index();
            $table->unsignedSmallInteger('sync_interval_minutes')->default(60);
            $table->unsignedSmallInteger('lookback_days')->default(30);
            $table->unsignedSmallInteger('overlap_days')->default(2);
            $table->date('starts_at')->nullable();
            $table->dateTime('last_attempt_at')->nullable()->index();
            $table->dateTime('last_successful_sync_at')->nullable();
            $table->dateTime('rate_limited_until')->nullable()->index();
            $table->dateTime('next_sync_at')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('djen_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('monitor_id')->constrained('djen_monitors')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('trigger', 20)->default('scheduled')->index();
            $table->string('status', 20)->default('queued')->index();
            $table->json('query_payload')->nullable();
            $table->unsignedSmallInteger('pages_processed')->default(0);
            $table->unsignedInteger('items_fetched')->default(0);
            $table->unsignedInteger('items_created')->default(0);
            $table->unsignedInteger('items_updated')->default(0);
            $table->unsignedInteger('items_failed')->default(0);
            $table->unsignedInteger('rate_limit_limit')->nullable();
            $table->unsignedInteger('rate_limit_remaining')->nullable();
            $table->dateTime('retry_at')->nullable()->index();
            $table->text('error_summary')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();

            $table->index(['monitor_id', 'created_at']);
        });

        Schema::create('djen_publications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('last_sync_run_id')->nullable()->constrained('djen_sync_runs')->nullOnDelete();
            $table->foreignId('legal_case_id')->nullable()->constrained('legal_cases')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->char('external_key', 64)->unique();
            $table->string('communication_number', 100)->nullable()->index();
            $table->string('source_hash')->nullable()->index();
            $table->string('process_number_normalized', 20)->nullable()->index();
            $table->string('tribunal', 30)->nullable()->index();
            $table->string('communication_type')->nullable()->index();
            $table->string('court_body')->nullable();
            $table->string('document_type')->nullable();
            $table->date('availability_date')->nullable()->index();
            $table->text('source_link')->nullable();
            $table->longText('raw_text')->nullable();
            $table->json('raw_payload');
            $table->char('content_hash', 64);
            $table->string('review_status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('legal_case_update_id')->nullable()->constrained('legal_case_updates')->nullOnDelete();
            $table->dateTime('discovered_at');
            $table->dateTime('last_seen_at');
            $table->timestamps();

            $table->index(['legal_case_id', 'review_status']);
            $table->index(['client_id', 'review_status']);
        });

        Schema::create('djen_monitor_publication', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('monitor_id')->constrained('djen_monitors')->cascadeOnDelete();
            $table->foreignId('publication_id')->constrained('djen_publications')->cascadeOnDelete();
            $table->foreignId('sync_run_id')->nullable()->constrained('djen_sync_runs')->nullOnDelete();
            $table->dateTime('first_seen_at');
            $table->dateTime('last_seen_at');
            $table->timestamps();

            $table->unique(['monitor_id', 'publication_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('djen_monitor_publication');
        Schema::dropIfExists('djen_publications');
        Schema::dropIfExists('djen_sync_runs');
        Schema::dropIfExists('djen_monitors');
    }
};
