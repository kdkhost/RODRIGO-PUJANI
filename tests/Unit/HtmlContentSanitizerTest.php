<?php

namespace Tests\Unit;

use App\Support\HtmlContentSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HtmlContentSanitizerTest extends TestCase
{
    #[DataProvider('xssPayloads')]
    public function test_message_profile_removes_executable_content(string $payload, string $forbidden): void
    {
        $result = (new HtmlContentSanitizer())->message($payload);

        $this->assertStringNotContainsStringIgnoringCase($forbidden, $result);
    }

    public static function xssPayloads(): array
    {
        return [
            ['<script>alert(1)</script><p>Seguro</p>', '<script'],
            ['<img src=x onerror=alert(1)>', '<img'],
            ['<a href="javascript:alert(1)" onclick="alert(2)">Abrir</a>', 'javascript:'],
            ['<svg><script>alert(1)</script></svg>', '<svg'],
            ['<iframe src="https://example.test"></iframe>', '<iframe'],
            ['<p style="background:url(javascript:alert(1))">Texto</p>', 'style='],
        ];
    }

    public function test_links_receive_safe_rel_and_only_allowed_schemes(): void
    {
        $sanitizer = new HtmlContentSanitizer();
        $safe = $sanitizer->message('<a href="https://example.com" target="_blank">Site</a>');
        $unsafe = $sanitizer->message('<a href="data:text/html;base64,WA==">Arquivo</a>');

        $this->assertStringContainsString('rel="noopener noreferrer"', $safe);
        $this->assertStringNotContainsString('target=', $safe);
        $this->assertStringNotContainsString('href=', $unsafe);
    }
}
