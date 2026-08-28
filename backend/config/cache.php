<?php

return [
    // The original system uses Redis, but this extracted service must run without it.
    'default' => env('AI_CACHE_DRIVER', 'file'),
    'stores' => [
        'file' => ['driver' => 'file', 'path' => storage_path('framework/cache/data')],
        'array' => ['driver' => 'array', 'serialize' => false],
    ],
    'prefix' => env('CACHE_PREFIX', 'kl_ai_cache'),
];
