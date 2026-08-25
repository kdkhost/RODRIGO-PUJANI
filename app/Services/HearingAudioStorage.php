<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class HearingAudioStorage
{
    private const MIME_EXTENSIONS = [
        'audio/mpeg' => ['mp3'],
        'audio/wav' => ['wav'],
        'audio/x-wav' => ['wav'],
        'audio/wave' => ['wav'],
        'audio/ogg' => ['ogg'],
        'application/ogg' => ['ogg'],
        'audio/webm' => ['webm'],
        'video/webm' => ['webm'],
        'audio/mp4' => ['m4a', 'mp4'],
        'video/mp4' => ['m4a', 'mp4'],
        'audio/x-m4a' => ['m4a'],
    ];

    /** @return array<string, mixed> */
    public function store(UploadedFile $file, ?int $reportedDuration = null): array
    {
        $size = (int) $file->getSize();
        $maximumSize = (int) config('legal_productivity.hearing_audio.max_size_kb', 262144) * 1024;
        if ($size < 1 || $size > $maximumSize) {
            throw ValidationException::withMessages(['audio' => 'O áudio excede o limite permitido.']);
        }

        $sourcePath = (string) $file->getRealPath();
        $sourceHash = (string) hash_file('sha256', $sourcePath);
        $originalName = $this->safeName($file->getClientOriginalName());
        $extension = Str::lower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = Str::lower((string) $file->getMimeType());
        if (! in_array($extension, self::MIME_EXTENSIONS[$mime] ?? [], true)) {
            throw ValidationException::withMessages(['audio' => 'A extensão não corresponde ao tipo real do áudio.']);
        }

        if (! $this->matchesSignature($sourcePath, $extension)) {
            throw ValidationException::withMessages(['audio' => 'A assinatura interna do arquivo de áudio é inválida.']);
        }

        [$duration, $durationSource] = $this->duration($sourcePath, $reportedDuration);
        $maximumDuration = (int) config('legal_productivity.hearing_audio.max_duration_seconds', 14400);
        if ($duration !== null && ($duration < 1 || $duration > $maximumDuration)) {
            throw ValidationException::withMessages(['duration_seconds' => 'A duração do áudio excede o limite permitido.']);
        }

        $disk = (string) config('legal_productivity.hearing_audio.disk', 'hearing_audio');
        $path = now()->format('Y/m').'/'.Str::uuid().'.'.$extension;
        Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));
        if (! Storage::disk($disk)->exists($path)) {
            throw new RuntimeException('Não foi possível confirmar o armazenamento privado do áudio.');
        }
        $storedStream = Storage::disk($disk)->readStream($path);
        if (! is_resource($storedStream)) {
            Storage::disk($disk)->delete($path);
            throw new RuntimeException('Não foi possível validar a integridade do áudio privado.');
        }
        $hashContext = hash_init('sha256');
        hash_update_stream($hashContext, $storedStream);
        fclose($storedStream);
        $storedHash = hash_final($hashContext);
        if (! hash_equals($sourceHash, $storedHash)) {
            Storage::disk($disk)->delete($path);
            throw new RuntimeException('A integridade do áudio armazenado não pôde ser confirmada.');
        }

        return [
            'original_name' => $originalName,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $mime,
            'extension' => $extension,
            'size' => $size,
            'sha256' => $sourceHash,
            'duration_seconds' => $duration,
            'metadata' => ['duration_source' => $durationSource],
        ];
    }

    public function absolutePath(string $disk, string $path): string
    {
        if ($disk !== config('legal_productivity.hearing_audio.disk', 'hearing_audio') || ! Storage::disk($disk)->exists($path)) {
            throw new RuntimeException('O arquivo privado de áudio não foi encontrado.');
        }

        return Storage::disk($disk)->path($path);
    }

    public function delete(string $disk, string $path): void
    {
        if ($disk === config('legal_productivity.hearing_audio.disk', 'hearing_audio')) {
            Storage::disk($disk)->delete($path);
        }
    }

    private function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', trim($name)));
        $name = preg_replace('/[\x00-\x1F\x7F"<>:|?*]/u', '-', $name) ?? '';

        $safe = Str::limit(trim($name, '. '), 180, '');

        if ($safe === '' || pathinfo($safe, PATHINFO_EXTENSION) === '') {
            throw ValidationException::withMessages(['audio' => 'O arquivo precisa possuir um nome e uma extensão válidos.']);
        }

        return $safe;
    }

    private function matchesSignature(string $path, string $extension): bool
    {
        $header = (string) file_get_contents($path, false, null, 0, 16);

        return match ($extension) {
            'mp3' => str_starts_with($header, 'ID3') || (strlen($header) >= 2 && ord($header[0]) === 0xFF && (ord($header[1]) & 0xE0) === 0xE0),
            'wav' => str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'WAVE',
            'ogg' => str_starts_with($header, 'OggS'),
            'webm' => str_starts_with($header, "\x1A\x45\xDF\xA3"),
            'm4a', 'mp4' => substr($header, 4, 4) === 'ftyp',
            default => false,
        };
    }

    /** @return array{0:?int,1:string} */
    private function duration(string $path, ?int $reportedDuration): array
    {
        if (! class_exists(\getID3::class)) {
            throw new RuntimeException('O analisador seguro de duração de áudio não está instalado.');
        }

        $analysis = (new \getID3())->analyze($path);
        $seconds = isset($analysis['playtime_seconds']) ? (int) ceil((float) $analysis['playtime_seconds']) : null;
        if ($seconds === null || $seconds < 1) {
            throw ValidationException::withMessages(['audio' => 'Não foi possível determinar a duração real do áudio no servidor.']);
        }

        return [$seconds, 'server'];
    }
}
