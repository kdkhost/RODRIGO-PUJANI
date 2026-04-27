<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            \App\Http\Middleware\EnsureInstalled::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdminAccess::class,
            'check.maintenance' => \App\Http\Middleware\CheckMaintenanceAccess::class,
            'track.visit' => \App\Http\Middleware\TrackPageVisit::class,
            'system-files.confirmed' => \App\Http\Middleware\EnsureSystemFilesPageConfirmed::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
