<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_credentials', function (Blueprint $table): void {
            $table->id();
            $table->string('service', 60)->unique();
            $table->boolean('enabled')->default(false)->index();
            $table->json('configuration')->nullable();
            $table->longText('secret')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('legal_update_summaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_case_update_id')->constrained('legal_case_updates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->char('source_sha256', 64);
            $table->longText('summary_text');
            $table->string('status', 30)->default('draft')->index();
            $table->string('provider', 60)->nullable();
            $table->string('model', 120)->nullable();
            $table->json('generation_metadata')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['legal_case_update_id', 'version'], 'legal_update_summary_version_unique');
            $table->index(['legal_case_update_id', 'status']);
        });

        Schema::create('hearing_transcriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('legal_case_id')->nullable()->constrained('legal_cases')->nullOnDelete();
            $table->foreignId('calendar_event_id')->nullable()->constrained('calendar_events')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('original_name');
            $table->string('disk', 40)->default('hearing_audio');
            $table->string('path');
            $table->string('mime_type', 120);
            $table->string('extension', 12);
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status', 40)->default('uploaded')->index();
            $table->string('provider', 60)->nullable();
            $table->string('provider_reference')->nullable();
            $table->longText('transcript_original')->nullable();
            $table->longText('transcript_edited')->nullable();
            $table->longText('minutes_draft')->nullable();
            $table->string('review_status', 30)->default('not_reviewed')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('processing_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['legal_case_id', 'status']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hearing_transcriptions');
        Schema::dropIfExists('legal_update_summaries');
        Schema::dropIfExists('integration_credentials');
    }
};
