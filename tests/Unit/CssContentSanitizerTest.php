<?php

namespace Tests\Unit;

use App\Support\CssContentSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CssContentSanitizerTest extends TestCase
{
    #[DataProvider('dangerousCss')]
    public function test_dangerous_css_is_rejected(string $css): void
    {
        $this->assertSame('', (new CssContentSanitizer())->sanitize($css));
    }

    public static function dangerousCss(): array
    {
        return [
            ['</style><script>alert(1)</script>'],
            ['@import "https://evil.test/x.css";'],
            ['a { background: url(javascript:alert(1)); }'],
            ['a { behavior: url(x.htc); }'],
        ];
    }

    public function test_basic_theme_css_is_preserved(): void
    {
        $css = '.card { border-radius: 24px; color: #c49a3c; }';

        $this->assertSame($css, (new CssContentSanitizer())->sanitize($css));
    }
}
