<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $settings = [
            ['group' => 'pwa', 'key' => 'pwa.enabled', 'label' => 'Ativar PWA', 'type' => 'boolean', 'value' => '1', 'is_public' => true, 'sort_order' => 530],
            ['group' => 'pwa', 'key' => 'pwa.installation_enabled', 'label' => 'Permitir instalação do PWA', 'type' => 'boolean', 'value' => '1', 'is_public' => true, 'sort_order' => 531],
            ['group' => 'pwa', 'key' => 'pwa.install_prompt_enabled', 'label' => 'Exibir anúncio de instalação', 'type' => 'boolean', 'value' => '1', 'is_public' => true, 'sort_order' => 532],
            ['group' => 'pwa', 'key' => 'pwa.footer_install_enabled', 'label' => 'Exibir instalação no rodapé', 'type' => 'boolean', 'value' => '1', 'is_public' => true, 'sort_order' => 533],
            ['group' => 'pwa', 'key' => 'pwa.mobile_install_enabled', 'label' => 'Exibir instalação no menu móvel', 'type' => 'boolean', 'value' => '1', 'is_public' => true, 'sort_order' => 534],
            ['group' => 'pwa', 'key' => 'pwa.app_name', 'label' => 'Nome do aplicativo', 'type' => 'text', 'value' => 'Pujani Advogados', 'is_public' => true, 'sort_order' => 535],
            ['group' => 'pwa', 'key' => 'pwa.short_name', 'label' => 'Nome curto do aplicativo', 'type' => 'text', 'value' => 'Pujani', 'is_public' => true, 'sort_order' => 536],
            ['group' => 'pwa', 'key' => 'pwa.description', 'label' => 'Descrição do PWA', 'type' => 'textarea', 'value' => 'Portal institucional e administrativo da Pujani Advogados.', 'is_public' => true, 'sort_order' => 537],
            ['group' => 'pwa', 'key' => 'pwa.start_path', 'label' => 'URL inicial do PWA', 'type' => 'text', 'value' => '/', 'is_public' => true, 'sort_order' => 538],
            ['group' => 'pwa', 'key' => 'pwa.scope', 'label' => 'Escopo do PWA', 'type' => 'text', 'value' => '/', 'is_public' => true, 'sort_order' => 539],
            ['group' => 'pwa', 'key' => 'pwa.display', 'label' => 'Modo de exibição do PWA', 'type' => 'text', 'value' => 'standalone', 'is_public' => true, 'sort_order' => 540],
            ['group' => 'pwa', 'key' => 'pwa.orientation', 'label' => 'Orientação do PWA', 'type' => 'text', 'value' => 'portrait', 'is_public' => true, 'sort_order' => 541],
            ['group' => 'pwa', 'key' => 'pwa.theme_color', 'label' => 'Cor principal do PWA', 'type' => 'text', 'value' => '#0B0C10', 'is_public' => true, 'sort_order' => 542],
            ['group' => 'pwa', 'key' => 'pwa.background_color', 'label' => 'Cor de fundo do PWA', 'type' => 'text', 'value' => '#0B0C10', 'is_public' => true, 'sort_order' => 543],
            ['group' => 'pwa', 'key' => 'pwa.icon_192', 'label' => 'Ícone PWA 192', 'type' => 'text', 'value' => 'pwa/icon-192.png', 'is_public' => true, 'sort_order' => 544],
            ['group' => 'pwa', 'key' => 'pwa.icon_512', 'label' => 'Ícone PWA 512', 'type' => 'text', 'value' => 'pwa/icon-512.png', 'is_public' => true, 'sort_order' => 545],
            ['group' => 'pwa', 'key' => 'pwa.popup_badge', 'label' => 'Etiqueta do anúncio do PWA', 'type' => 'text', 'value' => 'Aplicativo disponível', 'is_public' => true, 'sort_order' => 546],
            ['group' => 'pwa', 'key' => 'pwa.popup_title', 'label' => 'Título do anúncio do PWA', 'type' => 'text', 'value' => 'Instale o app do escritório', 'is_public' => true, 'sort_order' => 547],
            ['group' => 'pwa', 'key' => 'pwa.popup_description', 'label' => 'Descrição do anúncio do PWA', 'type' => 'textarea', 'value' => 'Adicione o site à tela inicial para abrir mais rápido, com aparência de aplicativo e suporte offline.', 'is_public' => true, 'sort_order' => 548],
            ['group' => 'pwa', 'key' => 'pwa.popup_primary_label', 'label' => 'Botão principal do anúncio do PWA', 'type' => 'text', 'value' => 'Instalar agora', 'is_public' => true, 'sort_order' => 549],
            ['group' => 'pwa', 'key' => 'pwa.popup_secondary_label', 'label' => 'Botão secundário do anúncio do PWA', 'type' => 'text', 'value' => 'Agora não', 'is_public' => true, 'sort_order' => 550],
            ['group' => 'pwa', 'key' => 'pwa.footer_label', 'label' => 'Texto do botão de instalação no rodapé', 'type' => 'text', 'value' => 'Instalar aplicativo', 'is_public' => true, 'sort_order' => 551],
            ['group' => 'pwa', 'key' => 'pwa.mobile_menu_label', 'label' => 'Texto do botão de instalação no menu móvel', 'type' => 'text', 'value' => 'Instalar aplicativo', 'is_public' => true, 'sort_order' => 552],
            ['group' => 'pwa', 'key' => 'pwa.offline_title', 'label' => 'Título da tela offline', 'type' => 'text', 'value' => 'Você está offline.', 'is_public' => true, 'sort_order' => 553],
            ['group' => 'pwa', 'key' => 'pwa.offline_message', 'label' => 'Mensagem da tela offline', 'type' => 'textarea', 'value' => 'Não foi possível carregar o conteúdo agora. Quando a conexão voltar, a navegação será retomada normalmente.', 'is_public' => true, 'sort_order' => 554],
            ['group' => 'pwa', 'key' => 'pwa.offline_button_label', 'label' => 'Botão da tela offline', 'type' => 'text', 'value' => 'Tentar novamente', 'is_public' => true, 'sort_order' => 555],
        ];

        foreach ($settings as $setting) {
            $existing = DB::table('settings')->where('key', $setting['key'])->first();

            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'group' => $setting['group'],
                    'label' => $setting['label'],
                    'type' => $setting['type'],
                    'value' => $existing?->value ?? $setting['value'],
                    'json_value' => null,
                    'is_public' => $setting['is_public'],
                    'sort_order' => $setting['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $existing?->created_at ?? $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'pwa.enabled',
            'pwa.installation_enabled',
            'pwa.install_prompt_enabled',
            'pwa.footer_install_enabled',
            'pwa.mobile_install_enabled',
            'pwa.scope',
            'pwa.orientation',
            'pwa.popup_badge',
            'pwa.popup_title',
            'pwa.popup_description',
            'pwa.popup_primary_label',
            'pwa.popup_secondary_label',
            'pwa.footer_label',
            'pwa.mobile_menu_label',
            'pwa.offline_title',
            'pwa.offline_message',
            'pwa.offline_button_label',
        ])->delete();
    }
};
