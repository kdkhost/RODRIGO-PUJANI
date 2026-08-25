<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('legal_case_id')->nullable()->constrained('legal_cases')->nullOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('installment_group')->nullable()->index();
            $table->unsignedSmallInteger('installment_number')->default(1);
            $table->unsignedSmallInteger('installment_count')->default(1);
            $table->string('entry_type', 20)->index();
            $table->string('category', 60)->index();
            $table->string('description');
            $table->string('reference', 120)->nullable()->index();
            $table->decimal('amount', 15, 2);
            $table->date('due_date')->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->string('status', 20)->default('pending')->index();
            $table->string('payment_method', 40)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status', 'due_date']);
            $table->index(['legal_case_id', 'status', 'due_date']);
            $table->unique(['installment_group', 'installment_number'], 'financial_installment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_entries');
    }
};
