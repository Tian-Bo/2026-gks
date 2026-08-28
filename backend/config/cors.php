<?php

return [
    'paths' => ['*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))),
    'allowed_origins_patterns' => [
        '#^https?://(?:localhost|127\\.0\\.0\\.1)(?::\\d+)?$#',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['*'],
    'max_age' => 0,
    // The standalone frontend sends the selected shop token with credentialed requests.
    // Origins remain constrained to the configured list and local development hosts.
    'supports_credentials' => true,
];
