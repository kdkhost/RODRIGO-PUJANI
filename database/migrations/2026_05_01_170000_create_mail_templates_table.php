<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('system_key')->nullable()->unique();
            $table->string('description', 500)->nullable();
            $table->string('subject')->nullable();
            $table->longText('header_html')->nullable();
            $table->longText('body_html');
            $table->longText('footer_html')->nullable();
            $table->string('layout', 40)->default('premium');
            $table->string('font_family')->default('Segoe UI, Arial, sans-serif');
            $table->boolean('show_logo')->default(true);
            $table->string('background_color', 20)->default('#0F172A');
            $table->string('body_background_color', 20)->default('#F4F6FB');
            $table->string('card_background_color', 20)->default('#FFFFFF');
            $table->string('border_color', 20)->default('#E5E7EF');
            $table->string('heading_color', 20)->default('#0F172A');
            $table->string('text_color', 20)->default('#334155');
            $table->string('muted_color', 20)->default('#64748B');
            $table->string('button_background_color', 20)->default('#C49A3C');
            $table->string('button_text_color', 20)->default('#10131A');
            $table->longText('custom_css')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $settings = DB::table('settings')
            ->whereIn('key', [
                'mail.template_header',
                'mail.template_footer',
                'mail.template_reset_subject',
                'mail.template_reset_body',
                'mail.template_generic_subject',
                'mail.template_generic_body',
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

        $shared = [
            'header_html' => $settings['mail.template_header'] ?? '',
            'footer_html' => $settings['mail.template_footer'] ?? '',
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
            'is_default' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('mail_templates')->insert([
            array_merge($shared, [
                'name' => 'Redefinição de senha',
                'slug' => 'redefinicao-de-senha',
                'system_key' => 'password-reset',
                'description' => 'Template padrão utilizado no fluxo de recuperação de acesso.',
                'subject' => $settings['mail.template_reset_subject'] ?? 'Redefinição de senha',
                'body_html' => $settings['mail.template_reset_body'] ?? '<p>Olá, @{{name}}.</p><p>Use o botão abaixo para redefinir sua senha.</p>',
            ]),
            array_merge($shared, [
                'name' => 'Notificação genérica',
                'slug' => 'notificacao-generica',
                'system_key' => 'generic-notification',
                'description' => 'Template padrão para comunicações gerais do sistema.',
                'subject' => $settings['mail.template_generic_subject'] ?? 'Notificação do sistema',
                'body_html' => $settings['mail.template_generic_body'] ?? '<p>Olá, @{{name}}.</p><p>Esta é uma mensagem enviada pelo sistema.</p>',
            ]),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_templates');
    }
};
