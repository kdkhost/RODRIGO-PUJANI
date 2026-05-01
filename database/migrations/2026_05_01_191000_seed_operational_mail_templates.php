<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $shared = $this->sharedTheme();

        $templates = [
            [
                'name' => 'Boas-vindas ao portal',
                'slug' => 'boas-vindas-portal-cliente',
                'description' => 'Envio inicial de acesso do portal do cliente.',
                'subject' => 'Acesso liberado ao portal do cliente',
                'body_html' => '<p>Olá, @{{name}}.</p><p>Seu acesso ao portal do cliente foi liberado. Use o botão abaixo para entrar e acompanhar seus processos em tempo real.</p>',
            ],
            [
                'name' => 'Novo contato pelo site',
                'slug' => 'alerta-novo-contato-site',
                'description' => 'Alerta interno quando o formulário de contato recebe um novo lead.',
                'subject' => 'Novo contato recebido: @{{name}}',
                'body_html' => '<p>Um novo contato foi recebido pelo site.</p><p><strong>Nome:</strong> @{{name}}<br><strong>E-mail:</strong> @{{email}}</p><p>Consulte a área administrativa para tratar o atendimento.</p>',
            ],
            [
                'name' => 'Nova mensagem interna do cliente',
                'slug' => 'alerta-mensagem-interna-cliente',
                'description' => 'Alerta para equipe quando o cliente envia mensagem interna no portal.',
                'subject' => 'Nova mensagem do cliente no portal',
                'body_html' => '<p>Olá, equipe.</p><p>Uma nova mensagem interna foi enviada por @{{name}} no portal do cliente.</p><p>Acesse o painel para responder.</p>',
            ],
            [
                'name' => 'Atualização de processo para cliente',
                'slug' => 'atualizacao-processo-cliente',
                'description' => 'Comunicado ao cliente sobre novo andamento processual.',
                'subject' => 'Atualização no seu processo',
                'body_html' => '<p>Olá, @{{name}}.</p><p>Houve uma nova atualização no seu processo. Entre no portal para visualizar os detalhes e próximos passos.</p>',
            ],
            [
                'name' => 'Novo documento compartilhado',
                'slug' => 'documento-compartilhado-cliente',
                'description' => 'Aviso ao cliente quando novo documento é disponibilizado no portal.',
                'subject' => 'Novo documento disponível no portal',
                'body_html' => '<p>Olá, @{{name}}.</p><p>Um novo documento foi compartilhado no seu portal. Acesse para visualizar e baixar o arquivo.</p>',
            ],
            [
                'name' => 'Lembrete de prazo/tarefa',
                'slug' => 'lembrete-prazo-tarefa',
                'description' => 'Lembrete operacional para prazos e tarefas jurídicas.',
                'subject' => 'Lembrete de prazo: ação necessária',
                'body_html' => '<p>Olá, @{{name}}.</p><p>Há um prazo/tarefa pendente que requer atenção. Verifique sua agenda administrativa para evitar vencimentos.</p>',
            ],
        ];

        foreach ($templates as $template) {
            DB::table('mail_templates')->updateOrInsert(
                ['slug' => $template['slug']],
                array_merge($shared, $template, [
                    'system_key' => null,
                    'is_default' => false,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]),
            );
        }
    }

    public function down(): void
    {
        DB::table('mail_templates')->whereIn('slug', [
            'boas-vindas-portal-cliente',
            'alerta-novo-contato-site',
            'alerta-mensagem-interna-cliente',
            'atualizacao-processo-cliente',
            'documento-compartilhado-cliente',
            'lembrete-prazo-tarefa',
        ])->delete();
    }

    private function sharedTheme(): array
    {
        $settings = DB::table('settings')
            ->whereIn('key', [
                'mail.template_header',
                'mail.template_footer',
                'mail.template_show_logo',
                'mail.template_layout',
                'mail.template_font_family',
                'mail.template_background_color',
                'mail.template_body_background_color',
                'mail.template_card_background_color',
                'mail.template_border_color',
                'mail.template_heading_color',
                'mail.template_text_color',
                'mail.template_muted_color',
                'mail.template_button_background_color',
                'mail.template_button_text_color',
                'mail.template_custom_css',
            ])
            ->pluck('value', 'key');

        return [
            'header_html' => $settings['mail.template_header'] ?? 'Olá, @{{name}}.',
            'footer_html' => $settings['mail.template_footer'] ?? 'Equipe @{{app_name}}',
            'layout' => $settings['mail.template_layout'] ?? 'premium',
            'font_family' => $settings['mail.template_font_family'] ?? 'Segoe UI, Arial, sans-serif',
            'show_logo' => ($settings['mail.template_show_logo'] ?? '1') === '1',
            'background_color' => $settings['mail.template_background_color'] ?? '#0F172A',
            'body_background_color' => $settings['mail.template_body_background_color'] ?? '#F4F6FB',
            'card_background_color' => $settings['mail.template_card_background_color'] ?? '#FFFFFF',
            'border_color' => $settings['mail.template_border_color'] ?? '#E5E7EF',
            'heading_color' => $settings['mail.template_heading_color'] ?? '#0F172A',
            'text_color' => $settings['mail.template_text_color'] ?? '#334155',
            'muted_color' => $settings['mail.template_muted_color'] ?? '#64748B',
            'button_background_color' => $settings['mail.template_button_background_color'] ?? '#C49A3C',
            'button_text_color' => $settings['mail.template_button_text_color'] ?? '#10131A',
            'custom_css' => $settings['mail.template_custom_css'] ?? '',
        ];
    }
};
