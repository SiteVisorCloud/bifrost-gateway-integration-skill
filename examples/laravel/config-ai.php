<?php
/**
 * Snippet for config/ai.php (Laravel AI SDK — composer require laravel/ai).
 *
 * Defines a 'bifrost' provider using the 'openai-compatible' driver, pointed at
 * your gateway. 'url' is required; 'key' is sent as a Bearer token (sk-bf-...).
 * Merge the 'providers' entry into your published config/ai.php.
 */

return [

    'providers' => [

        'bifrost' => [
            'driver' => 'openai-compatible',
            'url'    => env('BIFROST_BASE_URL', 'https://your-gateway.example.com/v1'),
            'key'    => env('BIFROST_API_KEY'),   // sk-bf-...
            'models' => [
                'text' => ['default' => env('BIFROST_MODEL', 'openai/gpt-4o-mini')],
            ],
        ],

        // ... your other providers ...
    ],

];
