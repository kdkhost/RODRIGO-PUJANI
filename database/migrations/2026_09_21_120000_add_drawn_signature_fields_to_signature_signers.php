<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signature_signers', function (Blueprint $table): void {
            $table->string('signature_image_path')->nullable()->after('terms_hash');
            $table->char('signature_image_sha256', 64)->nullable()->after('signature_image_path');
            $table->json('signature_metrics')->nullable()->after('signature_image_sha256');
        });
    }

    public function down(): void
    {
        Schema::table('signature_signers', function (Blueprint $table): void {
            $table->dropColumn([
                'signature_image_path',
                'signature_image_sha256',
                'signature_metrics',
            ]);
        });
    }
};
