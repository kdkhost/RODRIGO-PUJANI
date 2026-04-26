<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('location')->nullable();
            $table->string('url')->nullable();
            $table->string('category')->default('Atendimento')->index();
            $table->string('status')->default('scheduled')->index();
            $table->string('visibility')->default('team')->index();
            $table->string('color', 20)->nullable();
            $table->string('text_color', 20)->nullable();
            $table->dateTime('start_at')->index();
            $table->dateTime('end_at')->nullable()->index();
            $table->boolean('all_day')->default(false);
            $table->boolean('editable')->default(true);
            $table->boolean('overlap')->default(true);
            $table->string('display')->default('auto');
            $table->json('extended_props')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
