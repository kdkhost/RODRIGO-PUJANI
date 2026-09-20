<?php

return [
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
    'timeout' => 20,
    'initial_sync_past_days' => 365,
];
