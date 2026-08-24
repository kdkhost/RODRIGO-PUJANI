<?php

return [
    'enabled' => env('ELECTRONIC_SIGNATURE_ENABLED', true),
    'provider' => env('ELECTRONIC_SIGNATURE_PROVIDER', 'internal'),
    'default_expiration_days' => (int) env('ELECTRONIC_SIGNATURE_DEFAULT_EXPIRATION_DAYS', 7),
    'disk' => 'legal_documents',
    'terms_version' => '1.0',
];
