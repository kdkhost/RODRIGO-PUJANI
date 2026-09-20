<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ElectronicSignatureSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_signature_settings_are_saved_in_database_and_applied_to_runtime_config(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)
            ->get(route('admin.system-settings.show', 'signatures'))
            ->assertOk()
            ->assertSee('Assinatura eletrônica')
            ->assertSee('Ativar módulo de assinatura');

        $this->actingAs($admin)
            ->putJson(route('admin.system-settings.update', 'signatures'), [
                'signature_enabled' => '1',
                'signature_provider' => 'internal',
                'signature_default_expiration_days' => 12,
                'signature_token_expiration_hours' => 96,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Configuracoes atualizadas com sucesso.');

        $this->assertDatabaseHas('settings', [
            'group' => 'signatures',
            'key' => 'signatures.enabled',
            'value' => '1',
            'is_public' => false,
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'signatures.default_expiration_days',
            'value' => '12',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'signatures.token_expiration_hours',
            'value' => '96',
        ]);

        Cache::flush();
        config()->set('signatures.enabled', false);
        config()->set('signatures.default_expiration_days', 7);
        config()->set('signatures.token_expiration_hours', 72);

        (new AppServiceProvider(app()))->boot();

        $this->assertTrue((bool) config('signatures.enabled'));
        $this->assertSame('internal', config('signatures.provider'));
        $this->assertSame(12, config('signatures.default_expiration_days'));
        $this->assertSame(96, config('signatures.token_expiration_hours'));
    }

    public function test_signature_settings_default_to_disabled_in_database_seed(): void
    {
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->assertSame('0', Setting::query()->where('key', 'signatures.enabled')->value('value'));
        Cache::flush();

        $this->assertFalse((bool) electronic_signature_config()['enabled']);
    }

    private function administrator(): User
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo(['admin.access', 'settings.manage']);

        return $admin;
    }
}
