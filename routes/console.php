<?php

use App\Services\InstallerService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\File;

Schedule::call(function (): void {
    $path = storage_path('app/system/scheduler-heartbeat.json');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, json_encode([
        'executed_at' => now()->toIso8601String(),
        'timezone' => config('app.timezone'),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
})->everyMinute()->name('system-scheduler-heartbeat')->withoutOverlapping(2);

Schedule::command('signatures:expire')->hourly()->withoutOverlapping();
Schedule::command('djen:dispatch-due')
    ->everyFifteenMinutes()
    ->withoutOverlapping(14)
    ->name('djen-dispatch-due');
Schedule::command('legal:process-deadline-notifications')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->name('legal-deadline-notifications');
Schedule::command('google-calendar:sync')
    ->everyFifteenMinutes()
    ->withoutOverlapping(14)
    ->name('google-calendar-sync');
Schedule::command('queue:work --stop-when-empty --queue=integrations,legal-productivity,default --tries=3 --timeout=900 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping(20)
    ->name('shared-hosting-queue-drain');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('system:install {--fresh : Recria o banco do zero antes de popular os dados}', function () {
    $adminPassword = env('APP_ADMIN_PASSWORD');

    if (blank($adminPassword)) {
        $this->error('Defina APP_ADMIN_PASSWORD com uma senha forte antes de executar a instalação.');

        return 1;
    }

    app(InstallerService::class)->install([
        'app_name' => env('APP_NAME', 'Pujani Advogados'),
        'app_url' => env('APP_URL', 'http://localhost'),
        'db_connection' => env('DB_CONNECTION', 'mariadb'),
        'db_host' => env('DB_HOST', '127.0.0.1'),
        'db_port' => env('DB_PORT', '3306'),
        'db_database' => env('DB_DATABASE', 'pujani_advogados'),
        'db_username' => env('DB_USERNAME', 'root'),
        'db_password' => env('DB_PASSWORD', ''),
        'admin_name' => env('APP_ADMIN_NAME', 'Administrador'),
        'admin_email' => env('APP_ADMIN_EMAIL', 'admin@pujani.adv.br'),
        'admin_password' => (string) $adminPassword,
    ], $this->option('fresh'));

    $this->info('Instalação concluída.');

    return 0;
})->purpose('Instala e prepara o sistema para uso em hospedagem compartilhada');
