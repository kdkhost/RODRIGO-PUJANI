<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureElectronicSignatureEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) config('signatures.enabled', false), 404);

        return $next($request);
    }
}
