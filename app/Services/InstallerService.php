<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Throwable;

class InstallerService
{
    public function isInstalled(): bool
    {
        if (app()->runningUnitTests()) {
            return true;
        }

        if ($this->hasInstallationMarker()) {
            return true;
        }

        try {
            return Schema::hasTable('users') && User::query()->exists();
        } catch (Throwable) {
            return false;
        }
    }

    public function status(): array
    {
        $envPath = base_path('.env');

        return [
            'installed' => $this->isInstalled(),
            'env_exists' => File::exists($envPath),
            'env_writable' => File::exists($envPath)
                ? is_writable($envPath)
                : is_writable(base_path()),
            'storage_writable' => is_writable(storage_path()),
            'bootstrap_writable' => is_writable(base_path('bootstrap/cache')),
            'public_writable' => is_writable(public_path()),
        ];
    }

    public function install(array $data, bool $fresh = false): User
    {
        $this->ensureEnvironmentFile();

        $variables = [
            'APP_NAME' => $data['app_name'],
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $data['app_url'],
            'APP_TIMEZONE' => 'America/Sao_Paulo',
            'APP_LOCALE' => 'pt_BR',
            'APP_FALLBACK_LOCALE' => 'pt_BR',
            'APP_FAKER_LOCALE' => 'pt_BR',
            'DB_CONNECTION' => $data['db_connection'],
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => (string) $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'] ?? '',
            'APP_ADMIN_NAME' => $data['admin_name'],
            'APP_ADMIN_EMAIL' => $data['admin_email'],
            'APP_ADMIN_PASSWORD' => $data['admin_password'],
            'APP_INSTALLED' => 'false',
        ];

        $this->writeEnvironment($variables);
        $this->applyRuntimeConfiguration($variables);
        $this->refreshDatabaseConnection($data['db_connection']);

        if (! config('app.key')) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        if ($fresh) {
            $this->runArtisanCommand('migrate:fresh', ['--seed' => true, '--force' => true]);
        } else {
            $this->runArtisanCommand('migrate', ['--force' => true]);
            $this->runArtisanCommand('db:seed', ['--force' => true]);
        }

        $this->ensureStorageLink();

        $user = $this->syncAdminUser($data);

        $this->writeEnvironment(['APP_INSTALLED' => 'true']);
        $this->setEnvironmentValue('APP_INSTALLED', 'true');
        $this->writeInstallationMarker();

        $this->runArtisanCommand('optimize:clear');

        return $user;
    }

    protected function ensureEnvironmentFile(): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath) && File::exists(base_path('.env.example'))) {
            File::copy(base_path('.env.example'), $envPath);
        }
    }

    protected function writeEnvironment(array $variables): void
    {
        $envPath = base_path('.env');
        $content = File::exists($envPath) ? File::get($envPath) : '';
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        foreach ($variables as $key => $value) {
            $line = $key.'='.$this->formatEnvironmentValue($value);
            $pattern = "/^".preg_quote($key, '/')."=.*/m";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content) ?? $content;
            } else {
                $content = rtrim($content).PHP_EOL.$line.PHP_EOL;
            }

            $this->setEnvironmentValue($key, (string) $value);
        }

        File::put($envPath, $content);
    }

    protected function formatEnvironmentValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $string = trim((string) $value);

        if ($string === '') {
            return '';
        }

        if ($string === 'true' || $string === 'false' || is_numeric($string)) {
            return $string;
        }

        if (! preg_match('/\s|#|=|"|\'/', $string)) {
            return $string;
        }

        return '"'.str_replace(['\\', '"'], ['\\\\', '\"'], $string).'"';
    }

    protected function applyRuntimeConfiguration(array $variables): void
    {
        Config::set('app.name', $variables['APP_NAME']);
        Config::set('app.url', $variables['APP_URL']);
        Config::set('app.timezone', $variables['APP_TIMEZONE']);
        Config::set('app.locale', $variables['APP_LOCALE']);
        Config::set('app.fallback_locale', $variables['APP_FALLBACK_LOCALE']);
        Config::set('app.faker_locale', $variables['APP_FAKER_LOCALE']);
        Config::set('database.default', $variables['DB_CONNECTION']);

        foreach (['mysql', 'mariadb'] as $connection) {
            Config::set("database.connections.{$connection}.host", $variables['DB_HOST']);
            Config::set("database.connections.{$connection}.port", $variables['DB_PORT']);
            Config::set("database.connections.{$connection}.database", $variables['DB_DATABASE']);
            Config::set("database.connections.{$connection}.username", $variables['DB_USERNAME']);
            Config::set("database.connections.{$connection}.password", $variables['DB_PASSWORD']);
        }
    }

    protected function refreshDatabaseConnection(string $connection): void
    {
        DB::purge($connection);
        DB::disconnect($connection);
        DB::connection($connection)->getPdo();
    }

    protected function syncAdminUser(array $data): User
    {
        $admin = User::query()->updateOrCreate(
            ['email' => $data['admin_email']],
            [
                'name' => $data['admin_name'],
                'password' => Hash::make($data['admin_password']),
                'timezone' => 'America/Sao_Paulo',
                'is_active' => true,
            ]
        );

        $admin->syncRoles([Role::findOrCreate('Super Admin', 'web')->name]);

        return $admin;
    }

    protected function runArtisanCommand(string $command, array $parameters = []): void
    {
        $exitCode = Artisan::call($command, $parameters);

        if ($exitCode !== 0) {
            throw new \RuntimeException(trim(Artisan::output()) ?: "Falha ao executar {$command}.");
        }
    }

    protected function ensureStorageLink(): void
    {
        $exitCode = Artisan::call('storage:link');

        if ($exitCode === 0) {
            return;
        }

        $output = trim(Artisan::output());

        if (str_contains(strtolower($output), 'already exists')) {
            return;
        }

        throw new \RuntimeException($output ?: 'Falha ao criar o link simbolico do storage.');
    }

    protected function hasInstallationMarker(): bool
    {
        $flag = filter_var(env('APP_INSTALLED', false), FILTER_VALIDATE_BOOLEAN);

        return $flag || File::exists($this->installationMarkerPath());
    }

    protected function writeInstallationMarker(): void
    {
        $path = $this->installationMarkerPath();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'installed_at' => now()->toIso8601String(),
            'app_name' => config('app.name'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    protected function installationMarkerPath(): string
    {
        return storage_path('app/installer/installed.json');
    }

    protected function setEnvironmentValue(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
