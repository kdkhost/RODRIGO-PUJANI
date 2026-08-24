<?php

namespace App\Services;

use App\Contracts\LegalDocumentScannerInterface;
use Illuminate\Http\UploadedFile;

class NullLegalDocumentScanner implements LegalDocumentScannerInterface
{
    public function scan(UploadedFile $file): bool
    {
        return true;
    }
}
