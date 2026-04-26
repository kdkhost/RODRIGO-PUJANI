<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('settings')) {
            return $next($request);
        }

        $settings = Setting::query()
            ->where('group', 'system')
            ->get()
            ->keyBy('key');

        $enabled = filter_var(optional($settings->get('system.maintenance_enabled'))->value, FILTER_VALIDATE_BOOLEAN);

        if (! $enabled) {
            return $next($request);
        }

        if ($request->routeIs('admin.*') || $request->routeIs('login')) {
            return $next($request);
        }

        $releaseAt = optional($settings->get('system.maintenance_release_at'))->value;

        if ($releaseAt && now()->greaterThanOrEqualTo($releaseAt)) {
            return $next($request);
        }

        $allowedIps = collect(explode(',', (string) optional($settings->get('system.maintenance_allowed_ips'))->value))
            ->map(fn ($ip) => trim($ip))
            ->filter();

        if ($allowedIps->contains((string) $request->ip())) {
            return $next($request);
        }

        $allowedDevices = collect(explode(',', (string) optional($settings->get('system.maintenance_allowed_devices'))->value))
            ->map(fn ($device) => trim(strtolower($device)))
            ->filter();

        $agent = strtolower((string) $request->userAgent());

        if ($allowedDevices->first(fn ($device) => str_contains($agent, $device))) {
            return $next($request);
        }

        return response()->view('site.maintenance', [
            'releaseAt' => $releaseAt,
            'allowedIps' => $allowedIps,
        ], 503);
    }
}
