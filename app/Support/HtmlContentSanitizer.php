<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class HtmlContentSanitizer
{
    private HtmlSanitizer $messageSanitizer;

    private HtmlSanitizer $richTextSanitizer;

    public function __construct()
    {
        $messageConfig = (new HtmlSanitizerConfig())
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('blockquote')
            ->allowElement('a', ['href', 'title'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks()
            ->forceAttribute('a', 'rel', 'noopener noreferrer');

        $richConfig = $messageConfig
            ->allowElement('h1')->allowElement('h2')->allowElement('h3')
            ->allowElement('h4')->allowElement('h5')->allowElement('h6')
            ->allowElement('div')->allowElement('span')
            ->allowElement('hr')->allowElement('pre')->allowElement('code')
            ->allowElement('table')->allowElement('thead')->allowElement('tbody')
            ->allowElement('tfoot')->allowElement('tr')
            ->allowElement('th', ['colspan', 'rowspan', 'scope'])
            ->allowElement('td', ['colspan', 'rowspan'])
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height'])
            ->allowMediaSchemes(['https'])
            ->allowRelativeMedias();

        $this->messageSanitizer = new HtmlSanitizer($messageConfig);
        $this->richTextSanitizer = new HtmlSanitizer($richConfig);
    }

    public function message(?string $html): string
    {
        return $this->messageSanitizer->sanitize((string) $html);
    }

    public function richText(?string $html): string
    {
        return $this->richTextSanitizer->sanitize((string) $html);
    }

    public function sanitize(?string $html, string $profile = 'rich'): string
    {
        return $profile === 'message' ? $this->message($html) : $this->richText($html);
    }
}
