<?php

namespace App\Services;

use App\Contracts\LegalDocumentScannerInterface;
use App\Models\LegalDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

class LegalDocumentStorage
{
    public const DISK = 'legal_documents';
    public const LEGACY_PRIVATE_STATUS = 'legacy_private';

    private const MIME_EXTENSIONS = [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'application/msword' => ['doc'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'application/zip' => ['docx', 'xlsx'],
    ];

    public function __construct(private readonly LegalDocumentScannerInterface $scanner)
    {
    }

    public function store(UploadedFile $file): array
    {
        $metadata = $this->validate($file);

        if (! $this->scanner->scan($file)) {
            throw ValidationException::withMessages(['file' => 'O arquivo não foi aprovado pela verificação de segurança.']);
        }

        $path = now()->format('Y/m').'/'.Str::uuid().'.'.$metadata['extension'];
        Storage::disk(self::DISK)->putFileAs(dirname($path), $file, basename($path));

        if (! Storage::disk(self::DISK)->exists($path)) {
            throw new RuntimeException('Não foi possível confirmar o armazenamento privado do documento.');
        }

        return $metadata + [
            'disk' => self::DISK,
            'path' => $path,
            'file_name' => basename($path),
            'storage_status' => 'private',
            'scanned_at' => now(),
        ];
    }

    public function validate(UploadedFile $file): array
    {
        $originalName = $this->safeDownloadName($file->getClientOriginalName());
        $originalExtension = Str::lower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = Str::lower((string) $file->getMimeType());
        $allowedExtensions = self::MIME_EXTENSIONS[$mime] ?? [];

        if ($originalName === '' || substr_count($originalName, '.') !== 1 || ! in_array($originalExtension, $allowedExtensions, true)) {
            throw ValidationException::withMessages(['file' => 'Nome, extensão ou tipo real do documento não permitido.']);
        }

        $path = $file->getRealPath();
        $header = File::get($path, true);
        $header = substr($header, 0, 16);

        if (! $this->matchesSignature($path, $originalExtension, $header)) {
            throw ValidationException::withMessages(['file' => 'A assinatura interna do arquivo não corresponde ao formato informado.']);
        }

        return [
            'original_name' => $originalName,
            'extension' => $originalExtension,
            'mime_type' => $mime,
            'size' => $file->getSize(),
            'sha256' => hash_file('sha256', $path),
        ];
    }

    public function validateLegacy(string $source, LegalDocument $document): array
    {
        $extension = Str::lower(pathinfo((string) ($document->original_name ?: $source), PATHINFO_EXTENSION));
        $mime = Str::lower((string) File::mimeType($source));

        if (! is_file($source) || is_link($source) || ! in_array($document->storage_status, [null, 'legacy'], true)) {
            throw ValidationException::withMessages(['file' => 'O arquivo não possui metadados válidos de documento legado.']);
        }

        $uploadedFile = new UploadedFile(
            $source,
            $document->original_name ?: basename($source),
            null,
            UPLOAD_ERR_OK,
            true
        );

        if (! $this->scanner->scan($uploadedFile)) {
            throw ValidationException::withMessages(['file' => 'O arquivo legado não foi aprovado pela verificação de segurança.']);
        }

        if ($extension !== 'txt') {
            return $this->validate($uploadedFile);
        }

        if ($mime !== 'text/plain') {
            throw ValidationException::withMessages(['file' => 'O arquivo legado TXT não pôde ser validado.']);
        }

        return [
            'original_name' => $this->safeDownloadName($document->original_name ?: basename($source)),
            'extension' => $extension,
            'mime_type' => $mime,
            'size' => filesize($source),
            'sha256' => hash_file('sha256', $source),
        ];
    }

    public function storeLegacy(string $source, LegalDocument $document): array
    {
        $metadata = $this->validateLegacy($source, $document);
        $path = 'legacy/'.now()->format('Y/m').'/'.Str::uuid().'.'.$metadata['extension'];
        $stream = fopen($source, 'rb');

        if (! is_resource($stream)) {
            throw new RuntimeException('Não foi possível abrir o arquivo legado para cópia.');
        }

        try {
            Storage::disk(self::DISK)->writeStream($path, $stream);
        } finally {
            fclose($stream);
        }

        if (! Storage::disk(self::DISK)->exists($path)) {
            throw new RuntimeException('Não foi possível confirmar o armazenamento privado do documento legado.');
        }

        return $metadata + [
            'disk' => self::DISK,
            'path' => $path,
            'file_name' => basename($path),
            'storage_status' => self::LEGACY_PRIVATE_STATUS,
            'scanned_at' => now(),
            'is_sensitive' => true,
        ];
    }

    public function isPrivateCopyReadable(LegalDocument $document): bool
    {
        if ($document->disk !== self::DISK || blank($document->path)) {
            return false;
        }

        $disk = Storage::disk(self::DISK);
        if (! $disk->exists($document->path)) {
            return false;
        }

        $stream = $disk->readStream($document->path);
        if (! is_resource($stream)) {
            return false;
        }

        fclose($stream);

        return true;
    }

    public function absolutePath(LegalDocument $document): ?string
    {
        if ($document->disk === self::DISK && filled($document->path)) {
            return Storage::disk(self::DISK)->exists($document->path)
                ? Storage::disk(self::DISK)->path($document->path)
                : null;
        }

        $legacy = public_path(ltrim((string) $document->path, '/'));

        return is_file($legacy) ? $legacy : null;
    }

    public function delete(LegalDocument $document): void
    {
        if ($document->disk === self::DISK && filled($document->path)) {
            Storage::disk(self::DISK)->delete($document->path);
        }
    }

    public function safeDownloadName(?string $name): string
    {
        $name = basename(str_replace('\\', '/', trim((string) $name)));
        $name = preg_replace('/[\x00-\x1F\x7F"<>:|?*]/u', '-', $name) ?? '';

        return Str::limit(trim($name, '. '), 180, '');
    }

    private function matchesSignature(string $path, string $extension, string $header): bool
    {
        return match ($extension) {
            'pdf' => str_starts_with($header, '%PDF-'),
            'jpg', 'jpeg' => str_starts_with($header, "\xFF\xD8\xFF"),
            'png' => str_starts_with($header, "\x89PNG\r\n\x1A\n"),
            'webp' => str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'WEBP',
            'doc', 'xls' => str_starts_with($header, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"),
            'docx', 'xlsx' => $this->matchesOfficeArchive($path, $extension),
            default => false,
        };
    }

    private function matchesOfficeArchive(string $path, string $extension): bool
    {
        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages(['file' => 'A extensão ZIP do PHP é necessária para validar documentos DOCX/XLSX.']);
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return false;
        }

        $requiredDirectory = $extension === 'docx' ? 'word/' : 'xl/';
        $valid = $zip->locateName('[Content_Types].xml') !== false
            && collect(range(0, max(0, $zip->numFiles - 1)))
                ->contains(fn (int $index): bool => str_starts_with((string) $zip->getNameIndex($index), $requiredDirectory));
        $hasMacros = collect(range(0, max(0, $zip->numFiles - 1)))
            ->contains(fn (int $index): bool => str_ends_with(strtolower((string) $zip->getNameIndex($index)), 'vbaproject.bin'));
        $zip->close();

        return $valid && ! $hasMacros;
    }
}
