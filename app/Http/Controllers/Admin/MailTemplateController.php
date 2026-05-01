<?php

namespace App\Http\Controllers\Admin;

use App\Models\MailTemplate;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MailTemplateController extends AdminCrudController
{
    protected string $modelClass = MailTemplate::class;
    protected string $viewPath = 'mail-templates';
    protected string $module = 'mail-templates';
    protected string $singularLabel = 'Template de e-mail';
    protected string $pluralLabel = 'Templates de e-mail';
    protected string $routeBase = 'admin.mail-templates';
    protected array $searchable = ['name', 'slug', 'description', 'subject', 'system_key'];
    protected string $defaultSort = 'name';
    protected string $defaultDirection = 'asc';

    protected function indexQuery(Request $request): Builder
    {
        return MailTemplate::query();
    }

    protected function formData(?Model $record = null): array
    {
        return [
            'layoutOptions' => [
                'premium' => 'Premium',
                'classic' => 'Clássico',
                'minimal' => 'Minimalista',
            ],
            'fontOptions' => [
                'Segoe UI, Arial, sans-serif' => 'Segoe UI',
                'Arial, Helvetica, sans-serif' => 'Arial',
                'Roboto, Arial, sans-serif' => 'Roboto',
                'Georgia, Times New Roman, serif' => 'Georgia',
                'Tahoma, Geneva, sans-serif' => 'Tahoma',
            ],
            'systemKeyOptions' => MailTemplate::systemKeyOptions(),
            'tokenOptions' => [
                '@{{name}}' => 'Nome',
                '@{{email}}' => 'E-mail',
                '@{{app_name}}' => 'Aplicativo',
                '@{{from_name}}' => 'Remetente',
                '@{{reset_url}}' => 'URL de redefinição',
                '@{{year}}' => 'Ano',
            ],
        ];
    }

    protected function rules(Request $request, ?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', $this->uniqueRule('mail_templates', 'slug', $record)],
            'system_key' => [
                'nullable',
                Rule::in(array_keys(MailTemplate::systemKeyOptions())),
                Rule::unique('mail_templates', 'system_key')->ignore($record?->getKey()),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'subject' => ['nullable', 'string', 'max:255'],
            'header_html' => ['nullable', 'string'],
            'body_html' => ['required', 'string'],
            'footer_html' => ['nullable', 'string'],
            'layout' => ['required', Rule::in(['premium', 'classic', 'minimal'])],
            'font_family' => ['required', 'string', 'max:255'],
            'background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'body_background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'card_background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'border_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'heading_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'muted_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'button_background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'button_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'custom_css' => ['nullable', 'string'],
        ];
    }

    protected function mutateData(array $validated, Request $request, ?Model $record = null): array
    {
        $validated['slug'] = Str::slug((string) $validated['slug']);
        $validated += $this->booleanData($request, ['show_logo', 'is_active']);
        $validated['is_default'] = filled($validated['system_key'] ?? null);

        return $validated;
    }

    protected function afterSave(Model $record, Request $request, bool $created): void
    {
        if (! $record instanceof MailTemplate) {
            return;
        }

        $this->syncSettingsForSystemTemplate($record);
    }

    protected function beforeDelete(Model $record): void
    {
        if ($record instanceof MailTemplate && $record->is_default) {
            throw ValidationException::withMessages([
                'template' => 'Os templates padrão do sistema não podem ser removidos.',
            ]);
        }
    }

    private function syncSettingsForSystemTemplate(MailTemplate $template): void
    {
        if (! filled($template->system_key)) {
            return;
        }

        $map = [
            MailTemplate::SYSTEM_PASSWORD_RESET => [
                'mail.template_reset_subject' => $template->subject,
                'mail.template_reset_body' => $template->body_html,
            ],
            MailTemplate::SYSTEM_GENERIC_NOTIFICATION => [
                'mail.template_generic_subject' => $template->subject,
                'mail.template_generic_body' => $template->body_html,
            ],
        ];

        $basePayload = [
            'mail.template_header' => $template->header_html,
            'mail.template_footer' => $template->footer_html,
            'mail.template_show_logo' => $template->show_logo ? '1' : '0',
            'mail.template_layout' => $template->layout,
            'mail.template_font_family' => $template->font_family,
            'mail.template_background_color' => $template->background_color,
            'mail.template_body_background_color' => $template->body_background_color,
            'mail.template_card_background_color' => $template->card_background_color,
            'mail.template_border_color' => $template->border_color,
            'mail.template_heading_color' => $template->heading_color,
            'mail.template_text_color' => $template->text_color,
            'mail.template_muted_color' => $template->muted_color,
            'mail.template_button_background_color' => $template->button_background_color,
            'mail.template_button_text_color' => $template->button_text_color,
            'mail.template_custom_css' => $template->custom_css,
        ];

        foreach (array_merge($basePayload, $map[$template->system_key] ?? []) as $key => $value) {
            $setting = Setting::query()->firstWhere('key', $key);

            if (! $setting) {
                continue;
            }

            $setting->forceFill([
                'value' => $value,
            ])->save();
        }
    }
}
