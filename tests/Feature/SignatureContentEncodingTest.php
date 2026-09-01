<?php

namespace Tests\Feature;

use Tests\TestCase;

class SignatureContentEncodingTest extends TestCase
{
    /** @var array<string, array<int, string>> */
    private array $expectedTexts = [
        'app/Notifications/SignatureInvitationNotification.php' => [
            'Documento disponível para assinatura eletrônica',
            'Você recebeu o documento',
            'O link é pessoal, confidencial e possui prazo de validade.',
        ],
        'app/Notifications/SignatureStatusNotification.php' => [
            'Assinatura eletrônica',
            'A solicitação',
            'quando disponível, o comprovante.',
        ],
        'resources/views/signatures/show.blade.php' => [
            'Signatário:',
            'Confirme seu CPF/CNPJ',
            'concordo em assiná-lo eletronicamente',
        ],
        'resources/views/signatures/result.blade.php' => [
            'Operação registrada',
            'assinatura foi concluída com segurança',
        ],
        'resources/views/admin/signature-requests/show.blade.php' => [
            'Signatários',
            'Expiração:',
            'Comprovante válido',
            'Baixar comprovante',
        ],
        'resources/views/admin/system-settings/sections/mail.blade.php' => [
            'Comunicação por e-mail',
            'Usuário SMTP',
            'Testar configuração SMTP',
            'Senha configurada; deixe vazio para preservar',
        ],
    ];

    public function test_signature_and_smtp_content_is_valid_utf8_without_bom_or_mojibake(): void
    {
        $mojibake = '/(?:\x{00C3}[\x{0080}-\x{00BF}]|\x{00C2}[\x{0080}-\x{00BF}]|\x{00E2}\x{20AC}[\x{0080}-\x{00BF}]|\x{FFFD})/u';

        foreach ($this->expectedTexts as $file => $expectedTexts) {
            $contents = file_get_contents(base_path($file));

            $this->assertIsString($contents, $file);
            $this->assertTrue(mb_check_encoding($contents, 'UTF-8'), $file);
            $this->assertFalse(str_starts_with($contents, "\xEF\xBB\xBF"), $file);
            $this->assertDoesNotMatchRegularExpression($mojibake, $contents, $file);

            foreach ($expectedTexts as $expectedText) {
                $this->assertStringContainsString($expectedText, $contents, $file);
            }
        }
    }

    public function test_smtp_password_field_never_renders_the_stored_secret(): void
    {
        $view = file_get_contents(resource_path('views/admin/system-settings/sections/mail.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('name="mail_password"', $view);
        $this->assertStringContainsString('value=""', $view);
        $this->assertStringNotContainsString("old('mail_password'", $view);
        $this->assertStringNotContainsString("mailConfig['password']", $view);
    }
}
