<?php

use App\Services\InstallerService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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
        'admin_password' => env('APP_ADMIN_PASSWORD', 'Admin@12345'),
    ], $this->option('fresh'));

    $this->info('Instalação concluída.');
})->purpose('Instala e prepara o sistema para uso em hospedagem compartilhada');
