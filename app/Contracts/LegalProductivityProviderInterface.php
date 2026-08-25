<?php

namespace App\Contracts;

interface LegalProductivityProviderInterface
{
    /** @return array{text:string,provider:string,model:string,metadata:array<string,mixed>} */
    public function summarize(string $source): array;

    /** @return array{text:string,provider:string,model:string,reference:?string,metadata:array<string,mixed>} */
    public function transcribe(string $absolutePath, string $mimeType): array;

    /** @return array{text:string,provider:string,model:string,metadata:array<string,mixed>} */
    public function draftMinutes(string $transcript): array;
}
