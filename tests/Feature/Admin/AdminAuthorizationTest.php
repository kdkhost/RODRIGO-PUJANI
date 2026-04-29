<?php

namespace Tests\Feature\Admin;

use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\MediaAsset;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_module_permission_cannot_open_settings(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->givePermissionTo(['admin.access', 'pages.manage']);

        $response = $this->actingAs($user)->get(route('admin.settings.index'));

        $response->assertForbidden();
    }

    public function test_user_with_settings_permission_can_open_settings(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->givePermissionTo(['admin.access', 'settings.manage']);

        $response = $this->actingAs($user)->get(route('admin.settings.index'));

        $response->assertOk();
    }

    public function test_user_with_settings_permission_can_open_system_settings(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->givePermissionTo(['admin.access', 'settings.manage']);

        $response = $this->actingAs($user)->get(route('admin.system-settings.index'));

        $response->assertOk()->assertSee('Configurações do sistema');
    }

    public function test_settings_permission_can_update_auth_appearance_texts(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create([
            'is_active' => true,
        ]);
        $admin->givePermissionTo(['admin.access', 'settings.manage']);

        $this->actingAs($admin)
            ->putJson(route('admin.auth-appearance.update'), [
                'panel_eyebrow' => 'Portal Seguro',
                'panel_title' => 'Acesso estrategico',
                'panel_description' => 'Conteudo personalizado pelo administrativo.',
                'metric_1_title' => 'Clientes',
                'metric_1_subtitle' => 'Atendimento',
                'metric_2_title' => 'Agenda',
                'metric_2_subtitle' => 'Integrada',
                'metric_3_title' => 'Portal',
                'metric_3_subtitle' => 'Instalavel',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Tela de login atualizada com sucesso.');

        $this->assertDatabaseHas('settings', [
            'key' => 'auth.metric_1_title',
            'value' => 'Clientes',
        ]);

        auth()->logout();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Portal Seguro')
            ->assertSee('Clientes')
            ->assertSee('Instalavel');
    }

    public function test_settings_permission_can_update_system_settings_and_seed_demo_data(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create([
            'is_active' => true,
        ]);
        $admin->givePermissionTo(['admin.access', 'settings.manage']);

        $logo = $this->fakePngUpload('logo.png');
        $favicon = $this->fakePngUpload('favicon.png');
        $pwaIcon192 = $this->fakePngUpload('pwa-192.png');
        $pwaIcon512 = $this->fakePngUpload('pwa-512.png');

        $response = $this->actingAs($admin)->post(route('admin.system-settings.update'), [
            '_method' => 'PUT',
            'brand_name' => 'Pujani Premium',
            'brand_short_name' => 'PP',
            'admin_subtitle' => 'Operação jurídica',
            'admin_footer_text' => 'Rodapé premium',
            'admin_footer_meta' => 'Laravel 13 | Painel premium',
            'logo' => $logo,
            'favicon' => $favicon,
            'pwa_enabled' => '1',
            'pwa_installation_enabled' => '1',
            'pwa_install_prompt_enabled' => '1',
            'pwa_footer_install_enabled' => '1',
            'pwa_mobile_install_enabled' => '1',
            'pwa_app_name' => 'Pujani App',
            'pwa_short_name' => 'Pujani',
            'pwa_description' => 'Aplicativo premium do escritório.',
            'pwa_start_path' => '/portal-cliente',
            'pwa_scope' => '/',
            'pwa_display' => 'standalone',
            'pwa_orientation' => 'portrait',
            'pwa_theme_color' => '#123456',
            'pwa_background_color' => '#111111',
            'pwa_icon_192' => $pwaIcon192,
            'pwa_icon_512' => $pwaIcon512,
            'pwa_popup_badge' => 'Aplicativo disponível',
            'pwa_popup_title' => 'Instale o app',
            'pwa_popup_description' => 'Abra o escritório com aparência de aplicativo.',
            'pwa_popup_primary_label' => 'Instalar',
            'pwa_popup_secondary_label' => 'Depois',
            'pwa_footer_label' => 'Instalar app',
            'pwa_mobile_menu_label' => 'App no menu',
            'pwa_offline_title' => 'Modo offline',
            'pwa_offline_message' => 'Sem conexão no momento.',
            'pwa_offline_button_label' => 'Tentar novamente',
            'recaptcha_enabled' => '1',
            'recaptcha_site_key' => 'site-key-demo',
            'recaptcha_secret_key' => 'secret-key-demo',
            'recaptcha_min_score' => '0.7',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Configurações do sistema atualizadas com sucesso.');

        $this->assertDatabaseHas('settings', [
            'key' => 'branding.brand_name',
            'value' => 'Pujani Premium',
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'security.recaptcha_min_score',
            'value' => '0.7',
        ]);

        $logoSetting = Setting::query()->where('key', 'branding.logo_path')->value('value');
        $faviconSetting = Setting::query()->where('key', 'branding.favicon_path')->value('value');
        $pwaIcon192Setting = Setting::query()->where('key', 'pwa.icon_192')->value('value');
        $pwaIcon512Setting = Setting::query()->where('key', 'pwa.icon_512')->value('value');

        $this->assertNotEmpty($logoSetting);
        $this->assertNotEmpty($faviconSetting);
        $this->assertNotEmpty($pwaIcon192Setting);
        $this->assertNotEmpty($pwaIcon512Setting);
        $this->assertFileExists(public_path($logoSetting));
        $this->assertFileExists(public_path($faviconSetting));
        $this->assertFileExists(public_path($pwaIcon192Setting));
        $this->assertFileExists(public_path($pwaIcon512Setting));

        $this->assertDatabaseHas('settings', [
            'key' => 'pwa.popup_title',
            'value' => 'Instale o app',
        ]);

        $this->getJson(route('site.manifest'))
            ->assertOk()
            ->assertJsonPath('name', 'Pujani App')
            ->assertJsonPath('start_url', '/portal-cliente')
            ->assertJsonPath('theme_color', '#123456');

        $this->get(route('site.offline'))
            ->assertOk()
            ->assertSee('Modo offline')
            ->assertSee('Sem conexão no momento.');

        $this->actingAs($admin)
            ->postJson(route('admin.system-settings.seed-demo-data'))
            ->assertOk()
            ->assertJsonPath('message', 'Dados de exemplo populados com sucesso.');

        $this->assertDatabaseHas('users', [
            'email' => 'gestor.demo@pujani.adv.br',
        ]);

        $this->assertDatabaseHas('clients', [
            'email' => 'helena.martins@cliente.demo',
        ]);

        $this->assertGreaterThanOrEqual(1, Client::query()->count());

        File::delete(public_path($logoSetting));
        File::delete(public_path($faviconSetting));
        File::delete(public_path($pwaIcon192Setting));
        File::delete(public_path($pwaIcon512Setting));
    }

    public function test_disabled_pwa_returns_cleanup_service_worker_script(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'pwa.enabled'],
            ['group' => 'pwa', 'label' => 'Ativar PWA', 'type' => 'boolean', 'value' => '0', 'is_public' => true, 'sort_order' => 530],
        );

        $response = $this->get(route('site.service-worker'));

        $response->assertOk();
        $this->assertStringContainsString('unregister', $response->getContent());
        $this->assertStringContainsString('caches.delete', $response->getContent());
    }

    public function test_administrator_cannot_open_system_files_even_with_permission(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->assignRole('Administrador');
        $user->givePermissionTo('system-files.manage');

        $this->actingAs($user)
            ->get(route('admin.system-files.index'))
            ->assertForbidden();
    }

    public function test_super_admin_must_confirm_password_before_opening_system_files(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->assignRole('Super Admin');

        $this->actingAs($user)
            ->get(route('admin.system-files.index'))
            ->assertRedirect(route('admin.system-files.confirm'));
    }

    public function test_super_admin_can_confirm_password_and_open_system_files(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
            'password' => 'password',
        ]);
        $user->assignRole('Super Admin');

        $this->actingAs($user)
            ->post(route('admin.system-files.confirm.store'), [
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.system-files.index'));

        $this->actingAs($user)
            ->get(route('admin.system-files.index'))
            ->assertOk()
            ->assertSee('Arquivo .env')
            ->assertSee('Cofre técnico');
    }

    public function test_admin_user_can_update_user_without_validation_type_error(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo(['admin.access', 'users.manage']);
        $managedUser = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->putJson(route('admin.users.update', $managedUser), [
                'name' => 'Usuario Atualizado',
                'email' => $managedUser->email,
                'phone' => '(11) 99999-9999',
                'document_number' => '123.456.789-09',
                'address_zip' => '01310-100',
                'address_street' => 'Avenida Paulista',
                'address_number' => '1000',
                'address_district' => 'Bela Vista',
                'address_city' => 'São Paulo',
                'address_state' => 'SP',
                'timezone' => 'America/Sao_Paulo',
                'is_active' => '1',
            ])
            ->assertOk()
            ->assertJsonStructure(['message', 'tableTarget']);

        $this->assertSame('Usuario Atualizado', $managedUser->refresh()->name);
        $this->assertSame('São Paulo', $managedUser->address_city);
        $this->assertSame('SP', $managedUser->address_state);
    }

    public function test_media_asset_upload_creates_only_one_record(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo(['admin.access', 'media-assets.manage']);

        $this->actingAs($admin)
            ->postJson(route('admin.media-assets.store'), [
                'directory' => 'testing',
                'file' => UploadedFile::fake()->create('documento.pdf', 20, 'application/pdf'),
                'is_public' => '1',
            ])
            ->assertOk();

        $asset = MediaAsset::query()->firstOrFail();

        $this->assertSame(1, MediaAsset::query()->count());
        $this->assertStringStartsWith('uploads/testing/', $asset->path);
        $this->assertFileExists(public_path($asset->path));

        File::delete(public_path($asset->path));
        File::deleteDirectory(public_path('uploads/testing'));
    }

    public function test_admin_dashboard_renders_premium_overview_without_errors(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo('admin.access');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Centro de comando')
            ->assertSee('visão única', false);
    }

    public function test_admin_analytics_page_renders_premium_panels_without_errors(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo(['admin.access', 'analytics.view']);

        $this->actingAs($admin)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertSee('Inteligência do site')
            ->assertSee('Visão premium do tráfego');
    }

    public function test_admin_calendar_page_renders_premium_layout_and_upcoming_event(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo(['admin.access', 'calendar.manage']);

        CalendarEvent::query()->create([
            'title' => 'Reunião estratégica',
            'category' => 'Reunião',
            'status' => 'scheduled',
            'visibility' => 'team',
            'start_at' => now()->addDay()->setHour(9)->setMinute(0),
            'end_at' => now()->addDay()->setHour(10)->setMinute(0),
            'editable' => true,
            'overlap' => true,
            'display' => 'auto',
            'owner_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.calendar.index'))
            ->assertOk()
            ->assertSee('Agenda operacional')
            ->assertSee('Reunião estratégica')
            ->assertSee('data-calendar', false)
            ->assertSee('data-calendar-version="6"', false)
            ->assertDontSee('fullcalendar@6', false);
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        $path = storage_path('framework/testing/'.Str::uuid().'-'.$name);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+XWZ0AAAAASUVORK5CYII='));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }
}
