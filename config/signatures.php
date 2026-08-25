<?php

return [
    'enabled' => env('ELECTRONIC_SIGNATURE_ENABLED', false),
    'provider' => env('ELECTRONIC_SIGNATURE_PROVIDER', 'internal'),
    'default_expiration_days' => (int) env('ELECTRONIC_SIGNATURE_DEFAULT_EXPIRATION_DAYS', 7),
    'token_expiration_hours' => (int) env('ELECTRONIC_SIGNATURE_TOKEN_EXPIRATION_HOURS', 72),
    'disk' => 'legal_documents',
    'terms_version' => '1.0',
];
