<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Support\SmtpSecret;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GoogleCalendarOAuthSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_oauth_settings_with_encrypted_secret(): void
    {
        $admin = $this->administrator();
        $secret = 'google-client-secret-only-for-test';

        $response = $this->actingAs($admin)->putJson(route('admin.google-calendar.oauth.update'), [
            'enabled' => '1',
            'client_id' => 'client-id.apps.googleusercontent.com',
            'client_secret' => $secret,
            'redirect_uri' => 'https://rodrigopujaniadvocacia.com.br/admin/google-calendar/callback',
            'timeout' => 30,
            'initial_sync_past_days' => 180,
        ]);

        $response->assertOk()->assertDontSee($secret);

        $stored = (string) Setting::query()->where('key', 'google_calendar.client_secret')->value('value');
        $this->assertNotSame($secret, $stored);
        $this->assertTrue(SmtpSecret::isEncrypted($stored));
        $this->assertSame($secret, SmtpSecret::decrypt($stored));
        $this->assertTrue(google_calendar_config()['configured']);
        $this->assertSame(30, google_calendar_config()['timeout']);
        $this->assertArrayNotHasKey('client_secret', google_calendar_config());

        $audit = ActivityLog::query()->where('module', 'google_calendar')->latest()->firstOrFail();
        $this->assertArrayNotHasKey('google_calendar.client_secret', $audit->properties ?? []);
        $this->assertStringNotContainsString($secret, $audit->toJson());

        $html = $this->actingAs($admin)->get(route('admin.google-calendar.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Segredo configurado; deixe vazio para preservar', $html);
        $this->assertStringNotContainsString($secret, $html);
        $this->assertStringNotContainsString($stored, $html);
    }

    public function test_empty_secret_preserves_existing_encrypted_secret(): void
    {
        $admin = $this->administrator();
        $encrypted = SmtpSecret::encrypt('preserve-google-secret');
        $this->setting('google_calendar.client_secret', $encrypted, 'password');
        Cache::flush();

        $this->actingAs($admin)->putJson(route('admin.google-calendar.oauth.update'), [
            'enabled' => '1',
            'client_id' => 'new-client-id.apps.googleusercontent.com',
            'client_secret' => '',
            'redirect_uri' => 'https://rodrigopujaniadvocacia.com.br/admin/google-calendar/callback',
            'timeout' => 20,
            'initial_sync_past_days' => 365,
        ])->assertOk();

        $this->assertSame($encrypted, Setting::query()->where('key', 'google_calendar.client_secret')->value('value'));
        $this->assertSame('new-client-id.apps.googleusercontent.com', google_calendar_config()['client_id']);
    }

    public function test_connect_uses_database_oauth_settings(): void
    {
        $admin = $this->administrator();
        $this->googleCalendarSettings();

        $response = $this->actingAs($admin)->get(route('admin.google-calendar.connect'));
        $response->assertStatus(302);

        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $location);
        $this->assertStringContainsString('client_id=db-client-id.apps.googleusercontent.com', urldecode($location));
        $this->assertStringContainsString('redirect_uri=https://rodrigopujaniadvocacia.com.br/admin/google-calendar/callback', urldecode($location));
        $this->assertStringNotContainsString('db-client-secret', $location);
    }

    public function test_missing_database_settings_keep_connect_blocked(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)
            ->get(route('admin.google-calendar.connect'))
            ->assertRedirect(route('admin.google-calendar.index'))
            ->assertSessionHas('error');
    }

    private function administrator(): User
    {
        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo(['admin.access', 'google-calendar.manage']);

        return $admin;
    }

    private function googleCalendarSettings(): void
    {
        foreach ([
            'google_calendar.enabled' => ['1', 'boolean'],
            'google_calendar.client_id' => ['db-client-id.apps.googleusercontent.com', 'text'],
            'google_calendar.client_secret' => [SmtpSecret::encrypt('db-client-secret'), 'password'],
            'google_calendar.redirect_uri' => ['https://rodrigopujaniadvocacia.com.br/admin/google-calendar/callback', 'text'],
            'google_calendar.timeout' => ['20', 'text'],
            'google_calendar.initial_sync_past_days' => ['365', 'text'],
        ] as $key => [$value, $type]) {
            $this->setting($key, $value, $type);
        }

        Cache::flush();
    }

    private function setting(string $key, string $value, string $type): void
    {
        Setting::query()->updateOrCreate(['key' => $key], [
            'group' => 'google_calendar',
            'label' => $key,
            'type' => $type,
            'value' => $value,
            'is_public' => false,
        ]);
    }
}
