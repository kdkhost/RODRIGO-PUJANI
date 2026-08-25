<?php

return [
    'enabled' => filter_var(env('GOOGLE_CALENDAR_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'client_id' => env('GOOGLE_CALENDAR_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_CALENDAR_REDIRECT_URI'),
    'authorization_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
    'token_url' => 'https://oauth2.googleapis.com/token',
    'revoke_url' => 'https://oauth2.googleapis.com/revoke',
    'api_url' => 'https://www.googleapis.com/calendar/v3',
    'scopes' => [
        'openid',
        'email',
        'https://www.googleapis.com/auth/calendar.readonly',
        'https://www.googleapis.com/auth/calendar.events',
    ],
    'timeout' => (int) env('GOOGLE_CALENDAR_TIMEOUT', 20),
    'initial_sync_past_days' => (int) env('GOOGLE_CALENDAR_INITIAL_SYNC_PAST_DAYS', 365),
];
