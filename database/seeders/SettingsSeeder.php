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
            ['group' => 'site', 'key' => 'site.company_secondary_email', 'label' => 'E-mail secundario', 'type' => 'text', 'value' => 'consultoria@pujani.adv.br', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.company_address', 'label' => 'Endereco', 'type' => 'text', 'value' => 'Av. Paulista, 1842 · Conj. 2101 · Bela Vista · Sao Paulo/SP', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.company_cep', 'label' => 'CEP', 'type' => 'text', 'value' => '01310-200', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.business_hours', 'label' => 'Horario de atendimento', 'type' => 'text', 'value' => 'Seg. a sex.: 08h as 18h', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.social_linkedin', 'label' => 'LinkedIn', 'type' => 'text', 'value' => 'https://www.linkedin.com/', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.social_instagram', 'label' => 'Instagram', 'type' => 'text', 'value' => 'https://www.instagram.com/', 'is_public' => true],

            ['group' => 'branding', 'key' => 'branding.brand_name', 'label' => 'Nome da marca', 'type' => 'text', 'value' => 'Pujani Advogados', 'is_public' => true],
            ['group' => 'branding', 'key' => 'branding.brand_short_name', 'label' => 'Sigla da marca', 'type' => 'text', 'value' => 'P', 'is_public' => true],
            ['group' => 'branding', 'key' => 'branding.admin_subtitle', 'label' => 'Subtitulo do painel', 'type' => 'text', 'value' => 'Painel administrativo', 'is_public' => true],
            ['group' => 'branding', 'key' => 'branding.logo_path', 'label' => 'Logo principal', 'type' => 'text', 'value' => '', 'is_public' => true],
            ['group' => 'branding', 'key' => 'branding.favicon_path', 'label' => 'Favicon', 'type' => 'text', 'value' => '', 'is_public' => true],
            ['group' => 'branding', 'key' => 'branding.admin_footer_text', 'label' => 'Rodape do painel', 'type' => 'text', 'value' => 'Painel administrativo premium para operacao juridica.', 'is_public' => false],
            ['group' => 'branding', 'key' => 'branding.admin_footer_meta', 'label' => 'Rodape complementar', 'type' => 'text', 'value' => 'Laravel 13 | PHP 8.4 | Multiusuario', 'is_public' => false],

            ['group' => 'auth', 'key' => 'auth.panel_eyebrow', 'label' => 'Tela de login - chamada curta', 'type' => 'text', 'value' => 'Admin Suite', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.panel_title', 'label' => 'Tela de login - titulo', 'type' => 'text', 'value' => 'Gestao juridica com acesso seguro.', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.panel_description', 'label' => 'Tela de login - descricao', 'type' => 'text', 'value' => 'Painel administrativo para conteudo, agenda, midias, usuarios e permissoes do escritorio.', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.metric_1_title', 'label' => 'Tela de login - metrica 1 titulo', 'type' => 'text', 'value' => 'Laravel 13', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.metric_1_subtitle', 'label' => 'Tela de login - metrica 1 subtitulo', 'type' => 'text', 'value' => 'Base atual', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.metric_2_title', 'label' => 'Tela de login - metrica 2 titulo', 'type' => 'text', 'value' => 'ACL', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.metric_2_subtitle', 'label' => 'Tela de login - metrica 2 subtitulo', 'type' => 'text', 'value' => 'Permissoes', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.metric_3_title', 'label' => 'Tela de login - metrica 3 titulo', 'type' => 'text', 'value' => 'PWA', 'is_public' => true],
            ['group' => 'auth', 'key' => 'auth.metric_3_subtitle', 'label' => 'Tela de login - metrica 3 subtitulo', 'type' => 'text', 'value' => 'Experiencia em app', 'is_public' => true],

            ['group' => 'pwa', 'key' => 'pwa.enabled', 'label' => 'Ativar PWA', 'type' => 'boolean', 'value' => '1', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.installation_enabled', 'label' => 'Permitir instalacao do PWA', 'type' => 'boolean', 'value' => '1', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.install_prompt_enabled', 'label' => 'Exibir anuncio de instalacao', 'type' => 'boolean', 'value' => '1', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.footer_install_enabled', 'label' => 'Exibir instalacao no rodape', 'type' => 'boolean', 'value' => '1', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.mobile_install_enabled', 'label' => 'Exibir instalacao no menu movel', 'type' => 'boolean', 'value' => '1', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.app_name', 'label' => 'Nome do app', 'type' => 'text', 'value' => 'Pujani Advogados', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.short_name', 'label' => 'Nome curto', 'type' => 'text', 'value' => 'Pujani', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.description', 'label' => 'Descricao do PWA', 'type' => 'text', 'value' => 'Portal institucional e administrativo da Pujani Advogados.', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.theme_color', 'label' => 'Cor principal', 'type' => 'text', 'value' => '#0B0C10', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.background_color', 'label' => 'Cor de fundo', 'type' => 'text', 'value' => '#0B0C10', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.start_path', 'label' => 'URL inicial', 'type' => 'text', 'value' => '/', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.scope', 'label' => 'Escopo', 'type' => 'text', 'value' => '/', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.display', 'label' => 'Modo de exibicao', 'type' => 'text', 'value' => 'standalone', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.orientation', 'label' => 'Orientacao', 'type' => 'text', 'value' => 'portrait', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.icon_192', 'label' => 'Icone 192', 'type' => 'text', 'value' => 'pwa/icon-192.png', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.icon_512', 'label' => 'Icone 512', 'type' => 'text', 'value' => 'pwa/icon-512.png', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.popup_badge', 'label' => 'Etiqueta do anuncio', 'type' => 'text', 'value' => 'Aplicativo disponível', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.popup_title', 'label' => 'Titulo do anuncio', 'type' => 'text', 'value' => 'Instale o app do escritório', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.popup_description', 'label' => 'Descricao do anuncio', 'type' => 'text', 'value' => 'Adicione o site à tela inicial para abrir mais rápido, com aparência de aplicativo e suporte offline.', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.popup_primary_label', 'label' => 'Botao principal do anuncio', 'type' => 'text', 'value' => 'Instalar agora', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.popup_secondary_label', 'label' => 'Botao secundario do anuncio', 'type' => 'text', 'value' => 'Agora não', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.footer_label', 'label' => 'Texto do botao no rodape', 'type' => 'text', 'value' => 'Instalar aplicativo', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.mobile_menu_label', 'label' => 'Texto do botao no menu movel', 'type' => 'text', 'value' => 'Instalar aplicativo', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.offline_title', 'label' => 'Titulo offline', 'type' => 'text', 'value' => 'Você está offline.', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.offline_message', 'label' => 'Mensagem offline', 'type' => 'text', 'value' => 'Não foi possível carregar o conteúdo agora. Quando a conexão voltar, a navegação será retomada normalmente.', 'is_public' => true],
            ['group' => 'pwa', 'key' => 'pwa.offline_button_label', 'label' => 'Botao offline', 'type' => 'text', 'value' => 'Tentar novamente', 'is_public' => true],

            ['group' => 'system', 'key' => 'system.maintenance_enabled', 'label' => 'Manutencao ativa', 'type' => 'boolean', 'value' => '0', 'is_public' => false],
            ['group' => 'system', 'key' => 'system.maintenance_release_at', 'label' => 'Liberacao automatica', 'type' => 'text', 'value' => '', 'is_public' => false],
            ['group' => 'system', 'key' => 'system.maintenance_allowed_ips', 'label' => 'IPs liberados', 'type' => 'text', 'value' => '127.0.0.1,::1', 'is_public' => false],
            ['group' => 'system', 'key' => 'system.maintenance_allowed_devices', 'label' => 'Dispositivos liberados', 'type' => 'text', 'value' => 'iphone,android,windows', 'is_public' => false],

            ['group' => 'security', 'key' => 'security.recaptcha_enabled', 'label' => 'Ativar reCAPTCHA v3', 'type' => 'boolean', 'value' => '0', 'is_public' => false],
            ['group' => 'security', 'key' => 'security.recaptcha_site_key', 'label' => 'Site key do reCAPTCHA', 'type' => 'text', 'value' => '', 'is_public' => false],
            ['group' => 'security', 'key' => 'security.recaptcha_secret_key', 'label' => 'Secret key do reCAPTCHA', 'type' => 'text', 'value' => '', 'is_public' => false],
            ['group' => 'security', 'key' => 'security.recaptcha_min_score', 'label' => 'Score minimo do reCAPTCHA', 'type' => 'text', 'value' => '0.5', 'is_public' => false],

            ['group' => 'preloader', 'key' => 'preloader.enabled', 'label' => 'Ativar preloader', 'type' => 'boolean', 'value' => '0', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.scope', 'label' => 'Exibicao', 'type' => 'text', 'value' => 'all', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.style', 'label' => 'Estilo', 'type' => 'text', 'value' => 'spinner', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.brand', 'label' => 'Marca', 'type' => 'text', 'value' => 'Pujani Advogados', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.message', 'label' => 'Mensagem', 'type' => 'text', 'value' => 'Carregando experiencia segura...', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.background_color', 'label' => 'Cor de fundo', 'type' => 'text', 'value' => '#0f1318', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.accent_color', 'label' => 'Cor principal', 'type' => 'text', 'value' => '#c49a3c', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.text_color', 'label' => 'Cor do texto', 'type' => 'text', 'value' => '#f4ead7', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.logo_path', 'label' => 'Logo', 'type' => 'text', 'value' => '', 'is_public' => true],
            ['group' => 'preloader', 'key' => 'preloader.min_duration', 'label' => 'Duracao minima', 'type' => 'text', 'value' => '650', 'is_public' => true],
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
