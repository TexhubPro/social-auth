<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Configure each OAuth provider. `redirect` must exactly match the callback
    | URL registered in the provider's developer console. `scopes` is optional
    | (sensible defaults are used).
    |
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect' => env('GOOGLE_REDIRECT_URI', ''),
        'scopes' => ['openid', 'profile', 'email'],
        // Request a refresh token / force consent:
        'extra' => ['access_type' => 'offline', 'prompt' => 'consent'],
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID', ''),
        'client_secret' => env('GITHUB_CLIENT_SECRET', ''),
        'redirect' => env('GITHUB_REDIRECT_URI', ''),
        'scopes' => ['read:user', 'user:email'],
    ],
];
