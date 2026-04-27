<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        foreach ($this->definitions() as $key => $definition) {
            $setting = DB::table('settings')->where('key', $key)->first();

            if (! $setting) {
                continue;
            }

            $payload = [
                'label' => $definition['label'],
                'updated_at' => now(),
            ];

            if (array_key_exists('value', $definition) && in_array($setting->value, $definition['old_values'], true)) {
                $payload['value'] = $definition['value'];
            }

            DB::table('settings')
                ->where('key', $key)
                ->update($payload);
        }
    }

    public function down(): void
    {
    }

    private function definitions(): array
    {
        return [
            'auth.panel_eyebrow' => [
                'label' => 'Tela de login - chamada curta',
                'value' => 'Painel administrativo',
                'old_values' => ['Admin Suite', 'Painel administrativo'],
            ],
            'auth.panel_title' => [
                'label' => 'Tela de login - título',
                'value' => 'Gestão jurídica com acesso seguro.',
                'old_values' => ['Gestao juridica com acesso seguro.', 'Gestão jurídica com acesso seguro.'],
            ],
            'auth.panel_description' => [
                'label' => 'Tela de login - descrição',
                'value' => 'Painel administrativo para conteúdo, agenda, mídias, usuários e permissões do escritório.',
                'old_values' => [
                    'Painel administrativo para conteudo, agenda, midias, usuarios e permissoes do escritorio.',
                    'Painel administrativo para conteúdo, agenda, mídias, usuários e permissões do escritório.',
                ],
            ],
            'auth.metric_1_title' => [
                'label' => 'Tela de login - métrica 1 título',
                'value' => 'Laravel 13',
                'old_values' => ['Laravel 13'],
            ],
            'auth.metric_1_subtitle' => [
                'label' => 'Tela de login - métrica 1 subtítulo',
                'value' => 'Base atual',
                'old_values' => ['Base atual'],
            ],
            'auth.metric_2_title' => [
                'label' => 'Tela de login - métrica 2 título',
                'value' => 'ACL',
                'old_values' => ['ACL'],
            ],
            'auth.metric_2_subtitle' => [
                'label' => 'Tela de login - métrica 2 subtítulo',
                'value' => 'Permissões',
                'old_values' => ['Permissoes', 'Permissões'],
            ],
            'auth.metric_3_title' => [
                'label' => 'Tela de login - métrica 3 título',
                'value' => 'PWA',
                'old_values' => ['PWA'],
            ],
            'auth.metric_3_subtitle' => [
                'label' => 'Tela de login - métrica 3 subtítulo',
                'value' => 'Experiência em app',
                'old_values' => ['Experiencia app', 'Experiência em app'],
            ],
            'preloader.scope' => [
                'label' => 'Exibição',
                'old_values' => [],
            ],
            'preloader.message' => [
                'label' => 'Mensagem',
                'value' => 'Carregando experiência segura...',
                'old_values' => ['Carregando experiencia segura...', 'Carregando experiência segura...'],
            ],
            'preloader.min_duration' => [
                'label' => 'Duração mínima',
                'old_values' => [],
            ],
            'site.company_secondary_email' => [
                'label' => 'E-mail secundário',
                'old_values' => [],
            ],
            'site.company_address' => [
                'label' => 'Endereço',
                'old_values' => [],
            ],
            'site.business_hours' => [
                'label' => 'Horário de atendimento',
                'old_values' => [],
            ],
            'pwa.description' => [
                'label' => 'Descrição do PWA',
                'old_values' => [],
            ],
            'pwa.display' => [
                'label' => 'Modo de exibição',
                'old_values' => [],
            ],
            'pwa.icon_192' => [
                'label' => 'Ícone 192',
                'old_values' => [],
            ],
            'pwa.icon_512' => [
                'label' => 'Ícone 512',
                'old_values' => [],
            ],
            'system.maintenance_enabled' => [
                'label' => 'Manutenção ativa',
                'old_values' => [],
            ],
            'system.maintenance_release_at' => [
                'label' => 'Liberação automática',
                'old_values' => [],
            ],
        ];
    }
};
