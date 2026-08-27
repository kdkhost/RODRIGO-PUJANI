<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->foreignId('client_id')->nullable()->after('id')->constrained('clients')->nullOnDelete();
            $table->foreignId('legal_case_id')->nullable()->after('client_id')->constrained('legal_cases')->nullOnDelete();
            $table->foreignId('legal_task_id')->nullable()->unique()->after('legal_case_id')->constrained('legal_tasks')->nullOnDelete();
            $table->string('event_type', 40)->default('appointment')->after('category')->index();
            $table->unsignedInteger('reminder_minutes')->nullable()->after('end_at');
            $table->boolean('shared_with_client')->default(false)->after('visibility')->index();
            $table->boolean('google_sync_enabled')->default(false)->after('shared_with_client')->index();
            $table->string('source', 40)->default('manual')->after('google_sync_enabled')->index();
        });

        Schema::create('legal_task_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_task_id')->nullable()->constrained('legal_tasks')->nullOnDelete();
            $table->unsignedBigInteger('task_id_snapshot')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40)->index();
            $table->json('changes')->nullable();
            $table->json('snapshot')->nullable();
            $table->string('source', 40)->default('system')->index();
            $table->timestamps();
        });

        Schema::create('legal_deadline_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('deadline_reminders_enabled')->default(true);
            $table->boolean('daily_summary_enabled')->default(true);
            $table->time('daily_summary_time')->default('07:00:00');
            $table->unsignedTinyInteger('daily_summary_days_ahead')->default(7);
            $table->string('timezone', 64)->default('America/Sao_Paulo');
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('legal_notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_task_id')->nullable()->constrained('legal_tasks')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40)->index();
            $table->string('channel', 30)->default('mail')->index();
            $table->string('deduplication_key', 64)->unique();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->dateTime('scheduled_for')->nullable()->index();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('sent_at')->nullable()->index();
            $table->dateTime('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_notification_deliveries');
        Schema::dropIfExists('legal_deadline_preferences');
        Schema::dropIfExists('legal_task_histories');

        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('legal_task_id');
            $table->dropUnique('calendar_events_legal_task_id_unique');
            $table->dropConstrainedForeignId('legal_case_id');
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn([
                'event_type',
                'reminder_minutes',
                'shared_with_client',
                'google_sync_enabled',
                'source',
            ]);
        });
    }
};
