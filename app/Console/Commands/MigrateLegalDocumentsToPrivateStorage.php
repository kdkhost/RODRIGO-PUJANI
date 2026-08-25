<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\LegalDocument;
use App\Services\LegalDocumentStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class MigrateLegalDocumentsToPrivateStorage extends Command
{
    protected $signature = 'legal-documents:migrate-private
        {--dry-run : Valida e relata sem copiar, remover ou atualizar o banco}
        {--limit=100 : Quantidade máxima de documentos}
        {--document= : ID específico do documento}';

    protected $description = 'Migra documentos jurídicos legados de public/uploads para o armazenamento privado';

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
                $sourceHash = hash_file('sha256', $source);

                if (! is_string($sourceHash)) {
                    throw new RuntimeException('Não foi possível calcular o hash do arquivo legado.');
                }

                if (filled($document->sha256) && ! hash_equals((string) $document->sha256, $sourceHash)) {
                    throw new RuntimeException('Hash divergente do valor registrado antes da migração.');
                }

                $storage->validateLegacy($source, $document);

                if ($dryRun) {
                    $this->line("[DRY-RUN] #{$document->id}: válido, SHA-256 {$sourceHash}");
                    $success++;
                    continue;
                }

                $stored = $storage->storeLegacy($source, $document);
                $destination = Storage::disk(LegalDocumentStorage::DISK)->path($stored['path']);
                $destinationHash = hash_file('sha256', $destination);

                if (! is_string($destinationHash) || ! hash_equals($sourceHash, $destinationHash)) {
                    Storage::disk(LegalDocumentStorage::DISK)->delete($stored['path']);
                    throw new RuntimeException('Hash divergente após a cópia privada.');
                }

                $sourcePath = (string) $document->path;
                DB::transaction(function () use ($document, $stored, $sourceHash, $sourcePath): void {
                    $document->forceFill($stored)->save();
                    ActivityLog::query()->create([
                        'module' => 'legal_documents',
                        'event' => 'legacy_migrated_private',
                        'description' => 'Documento legado migrado para armazenamento privado.',
                        'subject_type' => $document->getMorphClass(),
                        'subject_id' => $document->getKey(),
                        'properties' => [
                            'source_path' => $sourcePath,
                            'destination_path' => $stored['path'],
                            'sha256' => $sourceHash,
                            'legacy_format' => $stored['extension'],
                        ],
                    ]);
                });

                $document->refresh();
                if ($document->disk !== LegalDocumentStorage::DISK
                    || $document->storage_status !== LegalDocumentStorage::LEGACY_PRIVATE_STATUS
                    || ! hash_equals($sourceHash, (string) $document->sha256)
                    || ! $storage->isPrivateCopyReadable($document)) {
                    throw new RuntimeException('A cópia privada não ficou disponível para download seguro.');
                }

                if (! unlink($source)) {
                    throw new RuntimeException('A cópia privada foi confirmada, mas a cópia pública não pôde ser removida.');
                }

                $this->info("#{$document->id}: migrado, verificado e removido do diretório público.");
                $success++;
            } catch (Throwable $exception) {
                report($exception);
                $this->error("#{$document->id}: {$exception->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->line("Processados: {$documents->count()}; aprovados: {$success}; falhas: {$failed}; modo: ".($dryRun ? 'dry-run' : 'migração'));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function safeLegacyPath(LegalDocument $document): string
    {
        $rawPath = str_replace('\\', '/', ltrim((string) $document->path, '/'));
        $segments = array_values(array_filter(explode('/', $rawPath), fn (string $segment): bool => $segment !== ''));

        if (in_array('..', $segments, true) || in_array('.', $segments, true)) {
            throw new RuntimeException('Path traversal não é permitido em caminhos legados.');
        }

        $publicUploadsRoot = realpath(public_path('uploads'));
        $candidate = public_path($rawPath);
        $candidateCursor = public_path();
        foreach ($segments as $segment) {
            $candidateCursor .= DIRECTORY_SEPARATOR.$segment;
            if (is_link($candidateCursor)) {
                throw new RuntimeException('Links simbólicos não são permitidos na migração legada.');
            }
        }
        $source = realpath($candidate);

        if (! $publicUploadsRoot || ! $source || ! is_file($source)) {
            throw new RuntimeException('Arquivo legado inexistente.');
        }

        $rootPrefix = rtrim(strtolower($publicUploadsRoot), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (! str_starts_with(strtolower($source), $rootPrefix)) {
            throw new RuntimeException('Caminho legado fora de public/uploads.');
        }

        return $source;
    }
}
