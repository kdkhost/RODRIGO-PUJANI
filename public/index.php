<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$requestPath = (string) (parse_url($requestUri, PHP_URL_PATH) ?: '/');

if ($requestPath === '/public' || str_starts_with($requestPath, '/public/')) {
    $canonicalPath = substr($requestPath, strlen('/public')) ?: '/';
    $query = (string) (parse_url($requestUri, PHP_URL_QUERY) ?: '');
    $location = $canonicalPath === '/docs.php'
        ? '/admin/documentation#changelog'
        : $canonicalPath.($query !== '' ? '?'.$query : '');

    header('Location: '.$location, true, 301);
    exit;
}

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
