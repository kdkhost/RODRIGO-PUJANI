<?php
/**
 * Script de limpeza emergencial de cache - RODRIGO PUJANI
 * Acesse: https://pujani.adv.br/cache-flush.php?token=pujani-flush-2026
 * APAGUE este arquivo após usar!
 */
declare(strict_types=1);

$token = $_GET['token'] ?? '';
if ($token !== 'pujani-flush-2026') {
    http_response_code(403);
    die('Acesso negado.');
}

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

$results = [];

// Limpa caches corrompidos específicos
$keys = ['site_whatsapp.team.v1', 'site_whatsapp.team.v2', 'site_pages.menu.v2', 'site_settings.all.v2'];
foreach ($keys as $key) {
    Cache::forget($key);
    $results[] = "✓ Cache '{$key}' removido";
}

// Limpa views compiladas
try {
    Artisan::call('view:clear');
    $results[] = "✓ Views compiladas limpas";
} catch (Throwable $e) {
    $results[] = "✗ view:clear falhou: " . $e->getMessage();
}

// Limpa cache de configuração
try {
    Artisan::call('config:clear');
    $results[] = "✓ Config cache limpo";
} catch (Throwable $e) {
    $results[] = "✗ config:clear falhou: " . $e->getMessage();
}

// Limpa sessões antigas (opcional)
try {
    Artisan::call('cache:clear');
    $results[] = "✓ Cache geral limpo (artisan cache:clear)";
} catch (Throwable $e) {
    $results[] = "✗ cache:clear falhou: " . $e->getMessage();
}

header('Content-Type: text/plain; charset=utf-8');
echo "=== CACHE FLUSH - Pujani Advogados ===\n";
echo date('d/m/Y H:i:s') . "\n\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\n✅ Pronto! Delete este arquivo agora: public/cache-flush.php\n";
