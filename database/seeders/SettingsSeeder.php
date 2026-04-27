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
            ['group' => 'site', 'key' => 'site.business_hours', 'label' => 'Horário de atendimento', 'type' => 'text', 'value' => 'Seg. a sex.: 08h às 18h', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.social_linkedin', 'label' => 'LinkedIn', 'type' => 'text', 'value' => 'https://www.linkedin.com/', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.social_instagram', 'label' => 'Instagram', 'type' => 'text', 'value' => 'https://www.instagram.com/', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.panel_eyebrow', 'label' => 'Tela de login - chamada curta', 'type' => 'text', 'value' => 'Admin Suite', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.panel_title', 'label' => 'Tela de login - título', 'type' => 'text', 'value' => 'Gestão jurídica com acesso seguro.', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.panel_description', 'label' => 'Tela de login - descrição', 'type' => 'text', 'value' => 'Painel administrativo para conteúdo, agenda, mídias, usuários e permissões do escritório.', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.metric_1_title', 'label' => 'Tela de login - métrica 1 título', 'type' => 'text', 'value' => 'Laravel 13', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.metric_1_subtitle', 'label' => 'Tela de login - métrica 1 subtítulo', 'type' => 'text', 'value' => 'Base atual', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.metric_2_title', 'label' => 'Tela de login - métrica 2 título', 'type' => 'text', 'value' => 'ACL', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.metric_2_subtitle', 'label' => 'Tela de login - métrica 2 subtítulo', 'type' => 'text', 'value' => 'Permissões', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.metric_3_title', 'label' => 'Tela de login - métrica 3 título', 'type' => 'text', 'value' => 'PWA', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.metric_3_subtitle', 'label' => 'Tela de login - métrica 3 subtítulo', 'type' => 'text', 'value' => 'Experiência em app', 'is_public' => true],
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
            ['group' => 'preloader', 'key' => 'preloader.enabled', 'label' => 'Ativar preloader', 'type' => 'boolean', 'value' => '0', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.scope', 'label' => 'Exibição', 'type' => 'text', 'value' => 'all', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.style', 'label' => 'Estilo', 'type' => 'text', 'value' => 'spinner', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.brand', 'label' => 'Marca', 'type' => 'text', 'value' => 'Pujani Advogados', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.message', 'label' => 'Mensagem', 'type' => 'text', 'value' => 'Carregando experiência segura...', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.background_color', 'label' => 'Cor de fundo', 'type' => 'text', 'value' => '#0f1318', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.accent_color', 'label' => 'Cor principal', 'type' => 'text', 'value' => '#c49a3c', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.text_color', 'label' => 'Cor do texto', 'type' => 'text', 'value' => '#f4ead7', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.logo_path', 'label' => 'Logo', 'type' => 'text', 'value' => '', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.min_duration', 'label' => 'Duração mínima', 'type' => 'text', 'value' => '650', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.custom_css', 'label' => 'CSS personalizado', 'type' => 'textarea', 'value' => '', 'is_public' => false],
        ];

        foreach ($settings as $index => $item) {
            $setting = Setting::query()->firstOrNew(['key' => $item['key']]);

            if (! $setting->exists) {
                $setting->fill($item + ['sort_order' => $index]);
            } else {
                $metadata = $item;
                unset($metadata['value']);
                $setting->fill($metadata + ['sort_order' => $index]);
            }

            $setting->save();
        }
    }
}
