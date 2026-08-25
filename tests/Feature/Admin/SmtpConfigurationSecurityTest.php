<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Support\SmtpSecret;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SmtpConfigurationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_secret_is_encrypted_at_rest_and_masked_in_admin_panel(): void
    {
        $admin = $this->administrator();
        $secret = 'SMTP-secret-only-for-test';
        $encrypted = SmtpSecret::encrypt($secret);
        $this->setting('mail.password', $encrypted, 'password');
        Cache::flush();

        $response = $this->actingAs($admin)->get(route('admin.system-settings.show', 'mail'));

        $response->assertOk()
            ->assertDontSee($secret)
            ->assertDontSee($encrypted)
            ->assertSee('Senha configurada; deixe vazio para preservar');
        $this->assertStringContainsString('value=""', $response->getContent());
        $this->assertTrue(SmtpSecret::isEncrypted(Setting::query()->where('key', 'mail.password')->value('value')));
    }

    public function test_empty_password_preserves_existing_encrypted_secret(): void
    {
        $admin = $this->administrator();
        $encrypted = SmtpSecret::encrypt('preserve-this-secret');
        $this->mailSettings($encrypted);

        $this->actingAs($admin)->putJson(route('admin.system-settings.update', 'mail'), $this->mailPayload([
            'mail_password' => '',
        ]))->assertOk();

        $this->assertSame($encrypted, Setting::query()->where('key', 'mail.password')->value('value'));
    }

    public function test_new_password_is_encrypted_and_never_written_to_response_or_audit_log(): void
    {
        $admin = $this->administrator();
        $secret = 'new-secret-that-must-not-leak';
        $this->mailSettings(SmtpSecret::encrypt('old-secret'));

        $response = $this->actingAs($admin)->putJson(route('admin.system-settings.update', 'mail'), $this->mailPayload([
            'mail_password' => $secret,
        ]));

        $response->assertOk()->assertDontSee($secret);
        $stored = Setting::query()->where('key', 'mail.password')->value('value');
        $this->assertNotSame($secret, $stored);
        $this->assertSame($secret, SmtpSecret::decrypt($stored));
        $audit = ActivityLog::query()->where('module', 'system-settings')->latest()->firstOrFail();
        $this->assertArrayNotHasKey('mail.password', $audit->properties ?? []);
        $this->assertStringNotContainsString($secret, $audit->toJson());
    }

    public function test_database_smtp_configuration_is_applied_with_decrypted_secret(): void
    {
        $secret = 'runtime-secret';
        $this->mailSettings(SmtpSecret::encrypt($secret));
        Cache::flush();

        (new AppServiceProvider(app()))->boot();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.test.internal', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('ssl', config('mail.mailers.smtp.encryption'));
        $this->assertSame('no-reply@smtp.test.internal', config('mail.from.address'));
        $this->assertSame($secret, config('mail.mailers.smtp.password'));
        $this->assertArrayNotHasKey('password', smtp_config());
    }

    public function test_smtp_test_uses_stored_secret_when_field_is_empty_without_exposing_it(): void
    {
        Mail::fake();
        $admin = $this->administrator();
        $secret = 'stored-test-secret';
        $this->mailSettings(SmtpSecret::encrypt($secret));
        Cache::flush();

        $response = $this->actingAs($admin)->postJson(route('admin.system-settings.smtp-test'), [
            'test_email' => 'controlled@example.test',
            'mailer' => 'smtp',
            'host' => 'smtp.test.internal',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => 'no-reply@smtp.test.internal',
            'password' => '',
            'from_address' => 'no-reply@smtp.test.internal',
            'from_name' => 'Teste controlado',
        ]);

        $response->assertOk()->assertDontSee($secret);
        $this->assertStringNotContainsString($secret, $response->getContent());
    }

    private function administrator(): User
    {
        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo(['admin.access', 'settings.manage']);

        return $admin;
    }

    private function mailSettings(string $encryptedPassword): void
    {
        foreach ([
            'mail.enabled' => '1',
            'mail.mailer' => 'smtp',
            'mail.host' => 'smtp.test.internal',
            'mail.port' => '465',
            'mail.encryption' => 'ssl',
            'mail.username' => 'no-reply@smtp.test.internal',
            'mail.password' => $encryptedPassword,
            'mail.from_address' => 'no-reply@smtp.test.internal',
            'mail.from_name' => 'Pujani Teste',
        ] as $key => $value) {
            $this->setting($key, $value, $key === 'mail.password' ? 'password' : 'text');
        }

        Cache::flush();
    }

    private function setting(string $key, string $value, string $type): void
    {
        Setting::query()->updateOrCreate(['key' => $key], [
            'group' => 'mail',
            'label' => $key,
            'type' => $type,
            'value' => $value,
            'is_public' => false,
        ]);
    }

    private function mailPayload(array $overrides = []): array
    {
        return array_merge([
            'mail_enabled' => '1',
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.test.internal',
            'mail_port' => 465,
            'mail_encryption' => 'ssl',
            'mail_username' => 'no-reply@smtp.test.internal',
            'mail_from_address' => 'no-reply@smtp.test.internal',
            'mail_from_name' => 'Pujani Teste',
        ], $overrides);
    }
}
