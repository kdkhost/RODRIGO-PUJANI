<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['group' => 'site', 'key' => 'site.company_phone', 'label' => 'Telefone', 'type' => 'text', 'value' => '(11) 3456-7890', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.company_whatsapp', 'label' => 'WhatsApp', 'type' => 'text', 'value' => '(11) 99876-5432', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.company_email', 'label' => 'E-mail principal', 'type' => 'text', 'value' => 'contato@pujani.adv.br', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.company_secondary_email', 'label' => 'E-mail secundário', 'type' => 'text', 'value' => 'consultoria@pujani.adv.br', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.company_address', 'label' => 'Endereço', 'type' => 'text', 'value' => 'Av. Paulista, 1842 · Conj. 2101 · Bela Vista · São Paulo/SP', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.company_cep', 'label' => 'CEP', 'type' => 'text', 'value' => '01310-200', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.business_hours', 'label' => 'Horário de atendimento', 'type' => 'text', 'value' => 'Seg a Sex: 08h às 18h', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.social_linkedin', 'label' => 'LinkedIn', 'type' => 'text', 'value' => 'https://www.linkedin.com/', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.social_instagram', 'label' => 'Instagram', 'type' => 'text', 'value' => 'https://www.instagram.com/', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.app_name', 'label' => 'Nome do app', 'type' => 'text', 'value' => 'Pujani Advogados', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.short_name', 'label' => 'Nome curto', 'type' => 'text', 'value' => 'Pujani', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.description', 'label' => 'Descrição do PWA', 'type' => 'text', 'value' => 'Portal institucional e administrativo da Pujani Advogados.', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.theme_color', 'label' => 'Cor principal', 'type' => 'text', 'value' => '#0B0C10', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.background_color', 'label' => 'Cor de fundo', 'type' => 'text', 'value' => '#0B0C10', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.start_path', 'label' => 'URL inicial', 'type' => 'text', 'value' => '/', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.display', 'label' => 'Modo de exibição', 'type' => 'text', 'value' => 'standalone', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.icon_192', 'label' => 'Ícone 192', 'type' => 'text', 'value' => 'pwa/icon-192.png', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.icon_512', 'label' => 'Ícone 512', 'type' => 'text', 'value' => 'pwa/icon-512.png', 'is_public' => true],
            ['group' => 'system', 'key' => 'system.maintenance_enabled', 'label' => 'Manutenção ativa', 'type' => 'boolean', 'value' => '0', 'is_public' => false],
            ['group' => 'system', 'key' => 'system.maintenance_release_at', 'label' => 'Liberação automática', 'type' => 'text', 'value' => '', 'is_public' => false],
            ['group' => 'system', 'key' => 'system.maintenance_allowed_ips', 'label' => 'IPs liberados', 'type' => 'text', 'value' => '127.0.0.1,::1', 'is_public' => false],
            ['group' => 'system', 'key' => 'system.maintenance_allowed_devices', 'label' => 'Dispositivos liberados', 'type' => 'text', 'value' => 'iphone,android,windows', 'is_public' => false],
        ];

        foreach ($settings as $index => $item) {
            Setting::query()->updateOrCreate(
                ['key' => $item['key']],
                $item + ['sort_order' => $index]
            );
        }
    }
}
