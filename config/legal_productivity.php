<?php

return [
    'ai' => [
        'timeout_seconds' => (int) env('LEGAL_AI_TIMEOUT_SECONDS', 120),
        'max_source_characters' => (int) env('LEGAL_AI_MAX_SOURCE_CHARACTERS', 30000),
    ],
    'hearing_audio' => [
        'disk' => 'hearing_audio',
        'max_size_kb' => (int) env('HEARING_AUDIO_MAX_SIZE_KB', 262144),
        'max_duration_seconds' => (int) env('HEARING_AUDIO_MAX_DURATION_SECONDS', 14400),
    ],
];
