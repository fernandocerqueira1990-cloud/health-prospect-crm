<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The forwarded headers honored only when the direct peer is trusted.
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO;

    /**
     * Get the explicitly configured trusted proxy addresses.
     *
     * @return array<int, string>
     */
    protected function proxies(): array
    {
        return config('security.trusted_proxies', ['127.0.0.1', '::1']);
    }
}
