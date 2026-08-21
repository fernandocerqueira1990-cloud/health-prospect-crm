<?php

$trustedProxies = array_values(array_filter(
    array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,::1'))),
    static fn (string $proxy): bool => $proxy !== '' && $proxy !== '*' && $proxy !== '**',
));

$viteDevelopmentSources = env('APP_ENV', 'production') === 'local'
    ? ' http://localhost:5173 http://127.0.0.1:5173'
    : '';

$contentSecurityPolicy = "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data:; font-src 'self' data:; style-src 'self' 'unsafe-inline'{$viteDevelopmentSources}; script-src 'self'{$viteDevelopmentSources}; connect-src 'self' ws: wss:{$viteDevelopmentSources}";

return [
    'trusted_proxies' => $trustedProxies,

    'headers' => [
        'content_security_policy' => env(
            'SECURITY_CONTENT_SECURITY_POLICY',
            $contentSecurityPolicy
        ),
        'permissions_policy' => env(
            'SECURITY_PERMISSIONS_POLICY',
            'camera=(), microphone=(), geolocation=()'
        ),
        'hsts' => [
            'enabled' => env('SECURITY_HSTS_ENABLED', false),
            'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
            'include_subdomains' => env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', false),
        ],
    ],
];
