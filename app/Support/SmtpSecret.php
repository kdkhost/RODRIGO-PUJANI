<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

final class SmtpSecret
{
    public static function encrypt(?string $plainText): string
    {
        $plainText = trim((string) $plainText);

        return $plainText === '' ? '' : Crypt::encryptString($plainText);
    }

    public static function decrypt(?string $storedValue): string
    {
        $storedValue = (string) $storedValue;
        if ($storedValue === '') {
            return '';
        }

        try {
            return Crypt::decryptString($storedValue);
        } catch (DecryptException) {
            return $storedValue;
        }
    }

    public static function isEncrypted(?string $storedValue): bool
    {
        if (blank($storedValue)) {
            return false;
        }

        try {
            Crypt::decryptString((string) $storedValue);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
