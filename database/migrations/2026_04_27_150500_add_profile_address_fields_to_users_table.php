<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('document_number', 32)->nullable()->after('phone');
            $table->string('whatsapp', 30)->nullable()->after('document_number');
            $table->string('alternate_phone', 30)->nullable()->after('whatsapp');
            $table->date('birth_date')->nullable()->after('alternate_phone');
            $table->string('address_zip', 12)->nullable()->after('birth_date');
            $table->string('address_street')->nullable()->after('address_zip');
            $table->string('address_number', 20)->nullable()->after('address_street');
            $table->string('address_complement')->nullable()->after('address_number');
            $table->string('address_district')->nullable()->after('address_complement');
            $table->string('address_city')->nullable()->after('address_district');
            $table->string('address_state', 8)->nullable()->after('address_city');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'document_number',
                'whatsapp',
                'alternate_phone',
                'birth_date',
                'address_zip',
                'address_street',
                'address_number',
                'address_complement',
                'address_district',
                'address_city',
                'address_state',
            ]);
        });
    }
};
