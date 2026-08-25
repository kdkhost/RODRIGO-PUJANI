<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signature_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_uuid')->unique();
            $table->foreignId('legal_document_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('legal_case_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('provider', 40)->default('internal');
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->boolean('ordered')->default(false);
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('signature_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('signature_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('legal_document_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('disk', 60);
            $table->string('immutable_path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->string('completed_path')->nullable();
            $table->char('completed_sha256', 64)->nullable();
            $table->string('evidence_path')->nullable();
            $table->char('evidence_sha256', 64)->nullable();
            $table->timestamps();
        });
        Schema::create('signature_signers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_uuid')->unique();
            $table->foreignId('signature_request_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('document_normalized', 20)->nullable();
            $table->unsignedInteger('signing_order')->default(1);
            $table->string('status', 30)->default('pending')->index();
            $table->char('token_hash', 64)->nullable()->unique();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->text('decline_reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('terms_version', 40)->nullable();
            $table->char('terms_hash', 64)->nullable();
            $table->timestamps();
            $table->index(['signature_request_id', 'signing_order']);
        });
        Schema::create('signature_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('signature_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('signature_signer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 80)->index();
            $table->string('actor_type', 40)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->char('document_hash', 64)->nullable();
            $table->timestamps();
        });
        Schema::create('signature_provider_callbacks', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 40);
            $table->string('external_id')->nullable();
            $table->string('idempotency_key')->unique();
            $table->string('type', 80);
            $table->json('sanitized_payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->text('sanitized_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_provider_callbacks');
        Schema::dropIfExists('signature_events');
        Schema::dropIfExists('signature_signers');
        Schema::dropIfExists('signature_documents');
        Schema::dropIfExists('signature_requests');
    }
};
