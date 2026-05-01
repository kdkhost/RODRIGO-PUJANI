<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_security_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('portal_client_id')->nullable()->index();
            $table->string('route_name')->nullable()->index();
            $table->string('method', 10);
            $table->string('path');
            $table->string('ip_address', 64)->nullable()->index();
            $table->text('forwarded_for')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referer')->nullable();
            $table->text('origin')->nullable();
            $table->string('host')->nullable();
            $table->string('session_id')->nullable();
            $table->string('device_fingerprint', 64)->nullable()->index();
            $table->string('reverse_dns')->nullable();
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone')->nullable();
            $table->string('isp')->nullable();
            $table->string('organization')->nullable();
            $table->string('asn')->nullable();
            $table->json('payload_preview')->nullable();
            $table->unsignedInteger('payload_field_count')->default(0);
            $table->boolean('blocked')->default(false)->index();
            $table->string('block_reason')->nullable();
            $table->json('threats')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_security_logs');
    }
};

