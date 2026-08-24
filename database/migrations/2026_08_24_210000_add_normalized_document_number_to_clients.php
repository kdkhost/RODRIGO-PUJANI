<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->string('document_number_normalized', 20)->nullable()->after('document_number')->index();
        });

        DB::table('clients')->select(['id', 'document_number'])->orderBy('id')
            ->chunkById(500, function ($clients): void {
                foreach ($clients as $client) {
                    $normalized = preg_replace('/\D+/', '', (string) $client->document_number);
                    DB::table('clients')->where('id', $client->id)->update([
                        'document_number_normalized' => $normalized !== '' ? $normalized : null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn('document_number_normalized');
        });
    }
};
