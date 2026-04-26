<?php

namespace App\Http\Middleware;

use App\Services\InstallerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function __construct(protected InstallerService $installer)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests()) {
            return $next($request);
        }

        if ($this->installer->isInstalled()) {
            if ($request->routeIs('install.*')) {
                return redirect()->route('site.home');
            }

            return $next($request);
        }

        if ($request->routeIs('install.*') || $request->is('build/*') || $request->is('favicon.ico')) {
            return $next($request);
        }

        return redirect()->route('install.index');
    }
}
