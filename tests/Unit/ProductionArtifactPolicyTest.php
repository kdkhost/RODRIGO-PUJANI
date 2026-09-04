<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ProductionArtifactPolicyTest extends TestCase
{
    private string $repositoryRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryRoot = dirname(__DIR__, 2);
    }

    public function test_git_archive_excludes_development_and_sensitive_artifacts(): void
    {
        $excluded = [
            '.env',
            '.env.production',
            '.githooks',
            '.phpunit.result.cache',
            'database/database.sqlite',
            'docs',
            'error_log',
            'node_modules',
            'phpunit.xml',
            'public/storage',
            'public/uploads',
            'puppeteer_test.cjs',
            'runtime.log',
            'scripts',
            'snapshot.backup',
            'snapshot.sql',
            'snapshot.zip',
            'sync_server.ps1',
            'testc.php',
            'tests',
            'tools',
            'vendor',
        ];

        foreach ($excluded as $path) {
            $this->assertSame('set', $this->exportIgnoreAttribute($path), $path);
        }

        $included = [
            '.env.example',
            'app',
            'artisan',
            'composer.json',
            'composer.lock',
            'config',
            'database/migrations',
            'package-lock.json',
            'package.json',
            'public/.htaccess',
            'public/index.php',
            'resources',
            'routes',
            'storage/app/.gitignore',
            'vite.config.js',
        ];

        foreach ($included as $path) {
            $this->assertNotSame('set', $this->exportIgnoreAttribute($path), $path);
        }
    }

    #[DataProvider('blockedPaths')]
    public function test_sensitive_paths_are_blocked_before_the_front_controller(string $path): void
    {
        $this->assertTrue($this->isBlockedByRewriteRules('.htaccess', $path), $path);

        $publicPath = str_starts_with($path, 'public/') ? substr($path, 7) : $path;
        $this->assertTrue($this->isBlockedByRewriteRules('public/.htaccess', $publicPath), $publicPath);
    }

    public static function blockedPaths(): array
    {
        return array_map(static fn (string $path): array => [$path], [
            '.env',
            '.env.production',
            '.githooks/pre-commit',
            '.phpunit.result.cache',
            'bootstrap/cache/config.php',
            'database/database.sqlite',
            'database/migrations/example.php',
            'error_log',
            'error_log.1',
            'phpunit.xml',
            'public/storage/.gitignore',
            'public/storage/shell.phtml.png',
            'public/uploads/shell.php',
            'public/uploads/shell.php.jpg',
            'snapshot.backup.1',
            'snapshot.sql.1',
            'snapshot.zip.1',
            'storage/app/private.pdf',
            'storage/logs/laravel.log',
            'testc.php',
            'tests/Feature/ExampleTest.php',
            'tools/check.php',
        ]);
    }

    #[DataProvider('publicPaths')]
    public function test_legitimate_public_paths_are_not_blocked(string $path): void
    {
        $this->assertFalse($this->isBlockedByRewriteRules('.htaccess', $path), $path);

        $publicPath = str_starts_with($path, 'public/') ? substr($path, 7) : $path;
        $this->assertFalse($this->isBlockedByRewriteRules('public/.htaccess', $publicPath), $publicPath);
    }

    public static function publicPaths(): array
    {
        return array_map(static fn (string $path): array => [$path], [
            '.well-known/acme-challenge/token',
            'docs.php',
            'index.php',
            'login',
            'public/assets/logo.svg',
            'public/build/assets/app.css',
            'public/docs.php',
            'public/index.php',
            'public/storage/branding/logo.webp',
            'public/uploads/image.jpg',
            'storage/application/image.jpg',
            'storage/branding/logo.webp',
            'storage/logos/logo.webp',
        ]);
    }

    public function test_apache_authorization_fallback_and_production_builder_are_fail_closed(): void
    {
        foreach (['.htaccess', 'public/.htaccess'] as $relativePath) {
            $contents = $this->read($relativePath);
            $frontControllerPosition = $relativePath === '.htaccess'
                ? strpos($contents, '# Canonicaliza acessos antigos')
                : strpos($contents, 'RewriteCond %{REQUEST_FILENAME} !-d');

            $this->assertStringContainsString('<IfModule mod_authz_core.c>', $contents);
            $this->assertStringContainsString('<IfModule mod_authz_host.c>', $contents);
            $this->assertStringNotContainsString('<IfModule mod_access_compat.c>', $contents);
            $this->assertNotFalse($frontControllerPosition);
            $this->assertLessThan(
                $frontControllerPosition,
                strpos($contents, '# BEGIN CENTRAL-JURIDICA-SENSITIVE-PATHS')
            );
        }

        $builder = $this->read('tools/build-production-payload.sh');

        $this->assertStringContainsString('git -C "$repository_root" archive --format=tar HEAD -- "${allowlist[@]}"', $builder);
        $this->assertStringContainsString('status --porcelain)', $builder);
        $this->assertStringContainsString('if [[ -e "$output_path" ]]', $builder);
        $this->assertStringContainsString('realpath -m -- "$output_path"', $builder);
        $this->assertStringContainsString('public/build/manifest.json', $builder);
        $this->assertStringContainsString('forbidden_path=', $builder);
        $this->assertStringNotContainsString('--format=zip', strtolower($builder));
        $this->assertStringNotContainsString('zip -', strtolower($builder));
    }

    private function exportIgnoreAttribute(string $path): string
    {
        $process = new Process([
            'git',
            '-C',
            $this->repositoryRoot,
            'check-attr',
            'export-ignore',
            '--',
            $path,
        ]);
        $process->mustRun();

        $parts = explode(': ', trim($process->getOutput()));

        return end($parts);
    }

    private function isBlockedByRewriteRules(string $relativePath, string $path): bool
    {
        preg_match_all(
            '/^\s*RewriteRule\s+(\S+)\s+-\s+\[F,L,NC\]\s*$/m',
            $this->read($relativePath),
            $matches
        );

        foreach ($matches[1] as $pattern) {
            if (preg_match('~'.$pattern.'~i', $path) === 1) {
                return true;
            }
        }

        return false;
    }

    private function read(string $relativePath): string
    {
        $contents = file_get_contents($this->repositoryRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath));

        $this->assertNotFalse($contents, $relativePath);

        return $contents;
    }
}
