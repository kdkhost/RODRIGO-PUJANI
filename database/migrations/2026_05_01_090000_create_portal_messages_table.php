<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('legal_case_id')->nullable()->constrained('legal_cases')->nullOnDelete();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_type', 20)->default('client');
            $table->string('subject', 160)->nullable();
            $table->text('message');
            $table->timestamp('read_by_client_at')->nullable();
            $table->timestamp('read_by_staff_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
            $table->index(['client_id', 'read_by_client_at']);
            $table->index(['client_id', 'read_by_staff_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_messages');
    }
};
