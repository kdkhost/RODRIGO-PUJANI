<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_security_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('security_access_block_id')->nullable()->after('portal_client_id')->index();
            $table->string('device_id')->nullable()->after('device_fingerprint')->index();
            $table->string('device_type', 40)->nullable()->after('device_id');
            $table->string('device_platform', 120)->nullable()->after('device_type');
            $table->string('device_model', 120)->nullable()->after('device_platform');
            $table->string('browser_name', 80)->nullable()->after('device_model');
            $table->string('browser_version', 80)->nullable()->after('browser_name');
            $table->string('os_name', 80)->nullable()->after('browser_version');
            $table->string('os_version', 80)->nullable()->after('os_name');
            $table->string('network_type', 30)->nullable()->after('os_version');
            $table->string('mac_address', 64)->nullable()->after('network_type');
            $table->json('device_metadata')->nullable()->after('mac_address');
        });
    }

    public function down(): void
    {
        Schema::table('form_security_logs', function (Blueprint $table): void {
            $table->dropColumn([
                'security_access_block_id',
                'device_id',
                'device_type',
                'device_platform',
                'device_model',
                'browser_name',
                'browser_version',
                'os_name',
                'os_version',
                'network_type',
                'mac_address',
                'device_metadata',
            ]);
        });
    }
};

