<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_document_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->index();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('context_scope', 24)->default('client_case')->index();
            $table->string('default_output_format', 10)->default('docx');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('legal_document_template_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_document_template_id');
            $table->foreign('legal_document_template_id', 'legal_doc_version_template_foreign')
                ->references('id')
                ->on('legal_document_templates')
                ->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('title_template');
            $table->json('definition');
            $table->json('allowed_tokens');
            $table->string('content_sha256', 64)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['legal_document_template_id', 'version'], 'legal_doc_template_version_unique');
        });

        Schema::create('legal_document_generations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_document_template_id');
            $table->foreign('legal_document_template_id', 'legal_doc_generation_template_foreign')
                ->references('id')
                ->on('legal_document_templates')
                ->restrictOnDelete();
            $table->foreignId('legal_document_template_version_id');
            $table->foreign('legal_document_template_version_id', 'legal_doc_generation_version_foreign')
                ->references('id')
                ->on('legal_document_template_versions')
                ->restrictOnDelete();
            $table->foreignId('legal_document_id')->nullable()->constrained('legal_documents')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('legal_case_id')->nullable()->constrained('legal_cases')->nullOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('context_scope', 24)->index();
            $table->string('output_format', 10)->index();
            $table->longText('context_snapshot');
            $table->string('context_sha256', 64)->index();
            $table->string('template_sha256', 64)->index();
            $table->string('rendered_sha256', 64)->index();
            $table->timestamp('generated_at')->useCurrent()->index();

            $table->unique('legal_document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_document_generations');
        Schema::dropIfExists('legal_document_template_versions');
        Schema::dropIfExists('legal_document_templates');
    }
};
