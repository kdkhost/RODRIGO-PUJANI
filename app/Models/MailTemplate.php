<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'slug',
    'system_key',
    'description',
    'subject',
    'header_html',
    'body_html',
    'footer_html',
    'layout',
    'font_family',
    'show_logo',
    'background_color',
    'body_background_color',
    'card_background_color',
    'border_color',
    'heading_color',
    'text_color',
    'muted_color',
    'button_background_color',
    'button_text_color',
    'custom_css',
    'is_default',
    'is_active',
])]
class MailTemplate extends Model
{
    public const SYSTEM_PASSWORD_RESET = 'password-reset';
    public const SYSTEM_GENERIC_NOTIFICATION = 'generic-notification';

    protected function casts(): array
    {
        return [
            'show_logo' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function systemKeyOptions(): array
    {
        return [
            self::SYSTEM_PASSWORD_RESET => 'Redefinição de senha',
            self::SYSTEM_GENERIC_NOTIFICATION => 'Notificação genérica',
        ];
    }
}
