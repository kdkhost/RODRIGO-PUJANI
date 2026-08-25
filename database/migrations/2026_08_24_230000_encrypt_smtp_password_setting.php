<?php

use App\Models\Setting;
use App\Support\SmtpSecret;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        $setting = Setting::query()->where('key', 'mail.password')->first();

        if ($setting && filled($setting->value) && ! SmtpSecret::isEncrypted($setting->value)) {
            $setting->forceFill([
                'value' => SmtpSecret::encrypt($setting->value),
                'type' => 'password',
                'is_public' => false,
            ])->save();
        }

        Cache::forget('site_settings.map.v2');
        Cache::forget('mail.config.v1');
        Cache::forget('mail.config.v2');
    }

    public function down(): void
    {
        // A reversão não restaura segredo em texto puro.
    }
};
