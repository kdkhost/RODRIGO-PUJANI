<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SecurityAccessBlock;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

$users = User::query()
    ->whereIn('id', [1, 4])
    ->get(['id', 'name', 'email', 'is_active', 'password']);

foreach ($users as $user) {
    $ok = Hash::check('Pujani@2026!', (string) $user->password);
    echo "user={$user->id};email={$user->email};active=".((int) $user->is_active).";temp_password_ok=".($ok ? '1' : '0').PHP_EOL;
}

$recaptcha = recaptcha_config();
echo 'recaptcha_enabled='.(($recaptcha['enabled'] ?? false) ? '1' : '0').';site_key='.(((string) ($recaptcha['site_key'] ?? '')) !== '' ? '1' : '0').';secret_key='.(((string) ($recaptcha['secret_key'] ?? '')) !== '' ? '1' : '0').PHP_EOL;

$loginEmail = (string) optional($users->firstWhere('id', 4))->email;
if ($loginEmail !== '') {
    $key = Str::transliterate(Str::lower($loginEmail).'|127.0.0.1');
    RateLimiter::clear($key);
    echo 'auth_id4_temp='.(Auth::attempt(['email' => $loginEmail, 'password' => 'Pujani@2026!']) ? '1' : '0').PHP_EOL;
    Auth::logout();
}

if (class_exists(SecurityAccessBlock::class)) {
    $blocks = SecurityAccessBlock::query()
        ->where('is_active', true)
        ->latest('id')
        ->limit(5)
        ->get(['id', 'block_type', 'block_value']);

    echo 'active_blocks='.$blocks->count().PHP_EOL;
    foreach ($blocks as $block) {
        echo "block={$block->id};type={$block->block_type};value={$block->block_value}".PHP_EOL;
    }
}
