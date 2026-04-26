<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class SystemFileManagerService
{
    public function all(): array
    {
        return collect(array_keys($this->definitions()))
            ->map(fn (string $key): array => $this->describe($key))
            ->all();
    }

    public function describe(string $key): array
    {
        $definition = $this->definition($key);
        $path = $definition['path'];
        $content = $this->currentContent($definition);

        return [
            'key' => $key,
            'label' => $definition['label'],
            'description' => $definition['description'],
            'path' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $path),
            'exists' => File::exists($path),
            'writable' => File::exists($path) ? is_writable($path) : is_writable(dirname($path)),
            'updated_at' => File::exists($path) ? date('d/m/Y H:i:s', File::lastModified($path)) : null,
            'size' => File::exists($path) ? File::size($path) : strlen($content),
            'rows' => $definition['rows'],
            'content' => $content,
            'backups' => $this->backups($key),
        ];
    }

    public function update(string $key, string $content, ?int $userId = null): array
    {
        $definition = $this->definition($key);
        $path = $definition['path'];
        $sanitized = $this->sanitizeContent($content);

        $this->validateContent($key, $sanitized);

        if (File::exists($path)) {
            $this->writeBackup($key, File::get($path), $userId);
        }

        File::put($path, $sanitized);
        $this->afterWrite($key);

        return $this->describe($key);
    }

    public function restore(string $key, string $backupName, ?int $userId = null): array
    {
        $definition = $this->definition($key);
        $backupPath = $this->backupDirectory($key).DIRECTORY_SEPARATOR.basename($backupName);

        if (! File::exists($backupPath)) {
            throw ValidationException::withMessages([
                'backup_name' => 'Backup nao encontrado para restauracao.',
            ]);
        }

        if (File::exists($definition['path'])) {
            $this->writeBackup($key, File::get($definition['path']), $userId);
        }

        File::put($definition['path'], $this->sanitizeContent(File::get($backupPath)));
        $this->afterWrite($key);

        return $this->describe($key);
    }

    protected function definitions(): array
    {
        return [
            'env' => [
                'label' => 'Arquivo .env',
                'description' => 'Variaveis de ambiente, credenciais e parametros do sistema.',
                'path' => base_path('.env'),
                'fallback_path' => base_path('.env.example'),
                'rows' => 18,
            ],
            'htaccess' => [
                'label' => 'Arquivo .htaccess',
                'description' => 'Regras da raiz para reescrita de URL, seguranca e publicacao sem /public.',
                'path' => base_path('.htaccess'),
                'fallback_path' => null,
                'rows' => 16,
            ],
        ];
    }

    protected function definition(string $key): array
    {
        $definition = $this->definitions()[$key] ?? null;

        if (! $definition) {
            throw ValidationException::withMessages([
                'file' => 'Arquivo do sistema nao permitido.',
            ]);
        }

        return $definition;
    }

    protected function currentContent(array $definition): string
    {
        if (File::exists($definition['path'])) {
            return $this->sanitizeContent(File::get($definition['path']), false);
        }

        if ($definition['fallback_path'] && File::exists($definition['fallback_path'])) {
            return $this->sanitizeContent(File::get($definition['fallback_path']), false);
        }

        return '';
    }

    protected function validateContent(string $key, string $content): void
    {
        if (str_contains($content, "\0")) {
            throw ValidationException::withMessages([
                'content' => 'O arquivo contem caracteres invalidos.',
            ]);
        }

        if (trim($content) === '') {
            throw ValidationException::withMessages([
                'content' => 'O conteudo nao pode ficar vazio.',
            ]);
        }

        if ($key === 'env') {
            $invalidLine = collect(preg_split("/\r\n|\n|\r/", $content) ?: [])
                ->map(fn (string $line, int $index): array => ['line' => $line, 'number' => $index + 1])
                ->first(function (array $row): bool {
                    $line = trim($row['line']);

                    return $line !== ''
                        && ! str_starts_with($line, '#')
                        && ! str_contains($line, '=');
                });

            if ($invalidLine) {
                throw ValidationException::withMessages([
                    'content' => 'Linha '.$invalidLine['number'].' do .env esta fora do formato CHAVE=valor.',
                ]);
            }
        }

        if ($key === 'htaccess' && ! str_contains(strtolower($content), 'rewriteengine')) {
            throw ValidationException::withMessages([
                'content' => 'O .htaccess precisa manter ao menos uma diretiva RewriteEngine.',
            ]);
        }
    }

    protected function sanitizeContent(string $content, bool $appendNewLine = true): string
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        if ($appendNewLine && ! str_ends_with($content, "\n")) {
            $content .= "\n";
        }

        return $content;
    }

    protected function backupDirectory(string $key): string
    {
        return storage_path('app/system-file-backups'.DIRECTORY_SEPARATOR.$key);
    }

    protected function backups(string $key): array
    {
        $directory = $this->backupDirectory($key);

        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->sortByDesc(fn (\SplFileInfo $file): int => $file->getMTime())
            ->take(8)
            ->map(fn (\SplFileInfo $file): array => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'updated_at' => date('d/m/Y H:i:s', $file->getMTime()),
            ])
            ->values()
            ->all();
    }

    protected function writeBackup(string $key, string $content, ?int $userId = null): void
    {
        $directory = $this->backupDirectory($key);
        File::ensureDirectoryExists($directory);

        $suffix = $userId ? '-u'.$userId : '';
        $name = now()->format('Ymd-His').$suffix.'.bak';

        File::put($directory.DIRECTORY_SEPARATOR.$name, $this->sanitizeContent($content));
    }

    protected function afterWrite(string $key): void
    {
        if ($key !== 'env') {
            return;
        }

        Artisan::call('optimize:clear');
    }
}
