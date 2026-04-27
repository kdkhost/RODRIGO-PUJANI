<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('person_type', 20)->default('individual')->index();
            $table->string('name')->index();
            $table->string('trade_name')->nullable();
            $table->string('document_number', 32)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('alternate_phone', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('profession')->nullable();
            $table->string('address_zip', 12)->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_number', 20)->nullable();
            $table->string('address_complement')->nullable();
            $table->string('address_district')->nullable();
            $table->string('address_city')->nullable()->index();
            $table->string('address_state', 8)->nullable()->index();
            $table->longText('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('assigned_lawyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('legal_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('primary_lawyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supervising_lawyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->index();
            $table->string('process_number', 40)->nullable()->index();
            $table->string('internal_code', 40)->nullable()->index();
            $table->string('practice_area')->nullable()->index();
            $table->string('counterparty')->nullable();
            $table->string('court_name')->nullable();
            $table->string('court_division')->nullable();
            $table->string('court_city')->nullable()->index();
            $table->string('court_state', 8)->nullable()->index();
            $table->string('status', 30)->default('active')->index();
            $table->string('phase', 30)->default('initial')->index();
            $table->string('priority', 20)->default('medium')->index();
            $table->date('filing_date')->nullable();
            $table->dateTime('next_hearing_at')->nullable()->index();
            $table->dateTime('next_deadline_at')->nullable()->index();
            $table->decimal('claim_amount', 14, 2)->nullable();
            $table->decimal('contract_value', 14, 2)->nullable();
            $table->decimal('success_fee_percent', 5, 2)->nullable();
            $table->longText('summary')->nullable();
            $table->longText('strategy_notes')->nullable();
            $table->boolean('is_confidential')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('legal_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_case_id')->nullable()->constrained('legal_cases')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->index();
            $table->string('task_type', 30)->default('follow_up')->index();
            $table->string('priority', 20)->default('medium')->index();
            $table->string('status', 20)->default('pending')->index();
            $table->dateTime('start_at')->nullable();
            $table->dateTime('due_at')->nullable()->index();
            $table->dateTime('completed_at')->nullable()->index();
            $table->string('location')->nullable();
            $table->unsignedInteger('reminder_minutes')->nullable();
            $table->unsignedInteger('billable_minutes')->nullable();
            $table->longText('description')->nullable();
            $table->longText('result_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('legal_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_case_id')->nullable()->constrained('legal_cases')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->index();
            $table->string('category')->nullable()->index();
            $table->string('original_name')->nullable();
            $table->string('file_name')->nullable();
            $table->string('path')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->longText('notes')->nullable();
            $table->boolean('is_sensitive')->default(true)->index();
            $table->boolean('shared_with_client')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
        Schema::dropIfExists('legal_tasks');
        Schema::dropIfExists('legal_cases');
        Schema::dropIfExists('clients');
    }
};
