<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_complete_office_and_responsible_setup(): void
    {
        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Administrador');

        $this->actingAs($admin)
            ->get(route('admin.office-setup.edit'))
            ->assertOk()
            ->assertSee('Configuração inicial')
            ->assertSee('data-cep-autofill', false)
            ->assertSee('Número da OAB');

        $response = $this->actingAs($admin)->putJson(route('admin.office-setup.update'), [
            'company_legal_name' => 'Escritório Jurídico Exemplo Ltda.',
            'company_trade_name' => 'Exemplo Advocacia',
            'company_document' => '12.345.678/0001-90',
            'company_oab_registration' => 'Sociedade 1234',
            'company_phone' => '(11) 3000-0000',
            'company_whatsapp' => '(11) 99999-0000',
            'company_email' => 'contato@exemplo.adv.br',
            'company_secondary_email' => 'financeiro@exemplo.adv.br',
            'business_hours' => 'Seg. a sex.: 08h às 18h',
            'address_zip' => '01310-200',
            'address_street' => 'Avenida Paulista',
            'address_number' => '1000',
            'address_complement' => 'Conjunto 10',
            'address_district' => 'Bela Vista',
            'address_city' => 'São Paulo',
            'address_state' => 'sp',
            'responsible_name' => 'Advogada Responsável',
            'responsible_email' => 'responsavel@exemplo.adv.br',
            'responsible_phone' => '(11) 3000-0001',
            'responsible_whatsapp' => '(11) 99999-0001',
            'responsible_document' => '123.456.789-09',
            'professional_title' => 'Sócia-administradora',
            'oab_number' => '123456',
            'oab_state' => 'sp',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $response->assertOk()->assertJsonPath('redirect', route('admin.office-setup.edit'));

        $this->assertSame('Exemplo Advocacia', Setting::query()->where('key', 'branding.brand_name')->value('value'));
        $this->assertSame('12.345.678/0001-90', Setting::query()->where('key', 'site.company_document')->value('value'));
        $this->assertStringContainsString('Avenida Paulista, 1000', (string) Setting::query()->where('key', 'site.company_address')->value('value'));

        $admin->refresh();
        $this->assertSame('Advogada Responsável', $admin->name);
        $this->assertSame('123456', $admin->oab_number);
        $this->assertSame('SP', $admin->oab_state);
    }

    public function test_user_without_settings_permission_cannot_open_setup(): void
    {
        $this->seed(PermissionsSeeder::class);
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('admin.office-setup.edit'))
            ->assertForbidden();
    }
}
