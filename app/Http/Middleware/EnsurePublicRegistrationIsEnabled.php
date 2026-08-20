<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicRegistrationIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            (bool) config('features.public_registration'),
            Response::HTTP_NOT_FOUND
        );

        return $next($request);
    }
}
