<?php

namespace App\Console\Commands;

use App\Models\LegalDocument;
use App\Services\LegalDocumentStorage;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateLegalDocumentsToPrivateStorage extends Command
{
    protected $signature = 'legal-documents:migrate-private
        {--dry-run : Valida e relata sem copiar ou atualizar o banco}
        {--limit=100 : Quantidade máxima de documentos}
        {--document= : ID específico do documento}';

    protected $description = 'Copia documentos jurídicos legados para o armazenamento privado sem apagar os originais';

    public function handle(LegalDocumentStorage $storage): int
    {
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $query = LegalDocument::query()->where(function ($builder): void {
            $builder->whereNull('disk')->orWhere('disk', '!=', LegalDocumentStorage::DISK);
        });

        if ($documentId = $this->option('document')) {
            $query->whereKey((int) $documentId);
        }

        $documents = $query->orderBy('id')->limit($limit)->get();
        $dryRun = (bool) $this->option('dry-run');
        $success = 0;
        $failed = 0;

        foreach ($documents as $document) {
            try {
                $source = $this->safeLegacyPath($document);
                $file = new UploadedFile($source, $document->original_name ?: basename($source), null, UPLOAD_ERR_OK, true);

                if ($dryRun) {
                    $metadata = $storage->validate($file);
                    $this->line("[DRY-RUN] #{$document->id}: válido, SHA-256 {$metadata['sha256']}");
                    $success++;
                    continue;
                }

                $metadata = $storage->store($file);
                DB::transaction(fn () => $document->forceFill($metadata)->save());
                $this->info("#{$document->id}: copiado para armazenamento privado; original preservado.");
                $success++;
            } catch (Throwable $exception) {
                report($exception);
                $this->error("#{$document->id}: {$exception->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->line("Processados: {$documents->count()}; aprovados: {$success}; falhas: {$failed}; modo: ".($dryRun ? 'dry-run' : 'cópia'));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function safeLegacyPath(LegalDocument $document): string
    {
        $publicRoot = realpath(public_path('uploads/legal-documents'));
        $source = realpath(public_path(ltrim((string) $document->path, '/')));

        if (! $publicRoot || ! $source || ! str_starts_with(strtolower($source), strtolower($publicRoot.DIRECTORY_SEPARATOR))) {
            throw new \RuntimeException('Caminho legado ausente ou fora do diretório permitido.');
        }

        return $source;
    }
}
