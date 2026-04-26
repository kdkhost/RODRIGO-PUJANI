<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class TrackPageVisit
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $request->isMethod('GET')
            && str_contains((string) $response->headers->get('content-type'), 'text/html')
            && ! $request->expectsJson()
            && ! $request->routeIs('admin.*')
            && Schema::hasTable('page_visits')
        ) {
            PageVisit::query()->create([
                'url' => $request->fullUrl(),
                'path' => '/'.ltrim($request->path(), '/'),
                'route_name' => $request->route()?->getName(),
                'page_title' => optional(data_get($request->route()?->defaults, 'page'))->title,
                'page_slug' => optional(data_get($request->route()?->defaults, 'page'))->slug,
                'referrer' => $request->headers->get('referer'),
                'session_id' => $request->session()->getId(),
                'ip_hash' => sha1((string) $request->ip().config('app.key')),
                'device_type' => $this->resolveDeviceType($request),
                'browser' => $this->resolveBrowser($request),
                'platform' => $this->resolvePlatform($request),
                'payload' => [
                    'query' => $request->query(),
                ],
                'visited_at' => now(),
            ]);
        }

        return $response;
    }

    protected function resolveDeviceType(Request $request): string
    {
        $agent = strtolower((string) $request->userAgent());

        return str_contains($agent, 'mobile') ? 'mobile' : (str_contains($agent, 'tablet') ? 'tablet' : 'desktop');
    }

    protected function resolveBrowser(Request $request): string
    {
        $agent = strtolower((string) $request->userAgent());

        return match (true) {
            str_contains($agent, 'edg') => 'Edge',
            str_contains($agent, 'chrome') => 'Chrome',
            str_contains($agent, 'firefox') => 'Firefox',
            str_contains($agent, 'safari') => 'Safari',
            default => 'Outro',
        };
    }

    protected function resolvePlatform(Request $request): string
    {
        $agent = strtolower((string) $request->userAgent());

        return match (true) {
            str_contains($agent, 'windows') => 'Windows',
            str_contains($agent, 'android') => 'Android',
            str_contains($agent, 'iphone') || str_contains($agent, 'ipad') || str_contains($agent, 'mac os') => 'Apple',
            str_contains($agent, 'linux') => 'Linux',
            default => 'Outro',
        };
    }
}
