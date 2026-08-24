<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface LegalDocumentScannerInterface
{
    public function scan(UploadedFile $file): bool;
}
