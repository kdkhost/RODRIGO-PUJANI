<?php

namespace Tests\Feature\Security;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_requires_recaptcha_when_enabled(): void
    {
        $this->enableRecaptcha();

        $user = User::factory()->create([
            'email' => 'admin@demo.test',
            'password' => 'Secret@123',
            'is_active' => true,
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Secret@123',
        ])->assertSessionHasErrors('recaptcha_token');

        $this->assertGuest();
    }

    public function test_contact_form_accepts_valid_recaptcha_token(): void
    {
        $this->enableRecaptcha();

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
                'action' => 'contact_message',
            ], 200),
        ]);

        $this->postJson(route('site.contact.submit'), [
            'name' => 'Lead Demo',
            'email' => 'lead@demo.test',
            'phone' => '(11) 99999-0000',
            'area_interest' => 'civil',
            'subject' => 'Consulta inicial',
            'message' => 'Preciso entender o fluxo do atendimento inicial.',
            'consent' => '1',
            'recaptcha_token' => 'demo-token',
        ])
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'lead@demo.test',
            'name' => 'Lead Demo',
            'area_interest' => 'Direito Civil',
            'subject' => 'Direito Civil',
        ]);
    }

    private function enableRecaptcha(): void
    {
        foreach ([
            'site_settings.map.v2',
            'site_settings.all.v2',
            'recaptcha.config.v1',
        ] as $cacheKey) {
            Cache::forget($cacheKey);
        }

        Setting::query()->updateOrCreate(
            ['key' => 'security.recaptcha_enabled'],
            ['group' => 'security', 'label' => 'Ativar reCAPTCHA', 'type' => 'boolean', 'value' => '1', 'is_public' => false, 'sort_order' => 1],
        );

        Setting::query()->updateOrCreate(
            ['key' => 'security.recaptcha_site_key'],
            ['group' => 'security', 'label' => 'Site key', 'type' => 'text', 'value' => 'site-key-demo', 'is_public' => false, 'sort_order' => 2],
        );

        Setting::query()->updateOrCreate(
            ['key' => 'security.recaptcha_secret_key'],
            ['group' => 'security', 'label' => 'Secret key', 'type' => 'text', 'value' => 'secret-key-demo', 'is_public' => false, 'sort_order' => 3],
        );

        Setting::query()->updateOrCreate(
            ['key' => 'security.recaptcha_min_score'],
            ['group' => 'security', 'label' => 'Score mínimo', 'type' => 'text', 'value' => '0.5', 'is_public' => false, 'sort_order' => 4],
        );

        foreach ([
            'site_settings.map.v2',
            'site_settings.all.v2',
            'recaptcha.config.v1',
        ] as $cacheKey) {
            Cache::forget($cacheKey);
        }
    }
}
