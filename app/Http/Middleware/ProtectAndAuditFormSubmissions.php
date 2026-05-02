<?php

namespace App\Http\Middleware;

use App\Models\FormSecurityLog;
use App\Models\SecurityAccessBlock;
use App\Services\IpIntelService;
use App\Support\InputSecuritySanitizer;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ProtectAndAuditFormSubmissions
{
    public function __construct(private readonly IpIntelService $ipIntel)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldInspect($request)) {
            return $next($request);
        }

        $rawPayload = $this->sanitizablePayload($request);
        $sanitized = InputSecuritySanitizer::sanitize(is_array($rawPayload) ? $rawPayload : []);
        $request->merge($sanitized['data']);

        $device = $this->resolveDeviceContext($request);
        $intel = $this->ipIntel->lookup($request->ip());
        $matchedBlock = $this->resolveMatchedBlock($request, $device, $intel);

        if ($matchedBlock) {
            $this->registerBlockHit($matchedBlock);
            $this->storeAudit($request, $sanitized, true, 'Origem bloqueada manualmente no painel.', $device, $intel, $matchedBlock);

            return $this->denyRequest($request, 'Origem bloqueada por politica de seguranca.');
        }

        if ($sanitized['blocked']) {
            $this->storeAudit($request, $sanitized, true, $sanitized['reason'], $device, $intel, null);

            return $this->denyRequest($request, 'Conteudo bloqueado por seguranca. Revise os campos enviados.');
        }

        $response = $next($request);
        $this->storeAudit($request, $sanitized, false, null, $device, $intel, null);

        return $response;
    }

    private function denyRequest(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return new JsonResponse([
                'message' => $message,
                'errors' => ['security' => [$message]],
            ], 422);
        }

        return new RedirectResponse(url()->previous())
            ->withErrors(['security' => $message])
            ->withInput($request->except(['password', 'password_confirmation']));
    }

    private function shouldInspect(Request $request): bool
    {
        if (! in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        $path = trim($request->path(), '/');
        if ($path === '' || str_starts_with($path, 'up')) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizablePayload(Request $request): array
    {
        $excluded = [
            'password',
            'password_confirmation',
            '_token',
            'g-recaptcha-response',
            'recaptcha_token',
        ];

        $payload = $request->except($excluded);

        foreach (array_keys($payload) as $key) {
            if (str_starts_with((string) $key, '_device_')) {
                unset($payload[$key]);
            }
        }

        return is_array($payload) ? $payload : [];
    }

    /**
     * @param array{data:array<string,mixed>,blocked:bool,reason:?string,threats:array<int,string>} $sanitized
     * @param array<string,mixed> $device
     * @param array<string,mixed> $intel
     */
    private function storeAudit(
        Request $request,
        array $sanitized,
        bool $blocked,
        ?string $blockReason,
        array $device,
        array $intel,
        ?SecurityAccessBlock $matchedBlock
    ): void {
        $ip = $request->ip();
        $routeName = optional($request->route())->getName();
        $preview = $this->payloadPreview($sanitized['data']);
        $portalClientId = (int) ($request->session()->get('portal_client_id') ?? 0);

        FormSecurityLog::query()->create([
            'user_id' => auth()->id(),
            'portal_client_id' => $portalClientId > 0 ? $portalClientId : null,
            'security_access_block_id' => $matchedBlock?->id,
            'route_name' => $routeName,
            'method' => strtoupper($request->method()),
            'path' => '/'.ltrim($request->path(), '/'),
            'ip_address' => $ip,
            'forwarded_for' => (string) $request->header('X-Forwarded-For', ''),
            'user_agent' => (string) $request->userAgent(),
            'referer' => (string) $request->headers->get('referer', ''),
            'origin' => (string) $request->headers->get('origin', ''),
            'host' => (string) $request->getHost(),
            'session_id' => $request->session()->getId(),
            'device_fingerprint' => $device['fingerprint'],
            'device_id' => $device['device_id'],
            'device_type' => $device['device_type'],
            'device_platform' => $device['platform'],
            'device_model' => $device['model'],
            'browser_name' => $device['browser_name'],
            'browser_version' => $device['browser_version'],
            'os_name' => $device['os_name'],
            'os_version' => $device['os_version'],
            'network_type' => $device['network_type'],
            'mac_address' => $device['mac_address'],
            'device_metadata' => $device['metadata'],
            'reverse_dns' => $ip ? @gethostbyaddr($ip) : null,
            'country' => $intel['country'] ?? null,
            'region' => $intel['region'] ?? null,
            'city' => $intel['city'] ?? null,
            'latitude' => $intel['latitude'] ?? null,
            'longitude' => $intel['longitude'] ?? null,
            'timezone' => $intel['timezone'] ?? null,
            'isp' => $intel['isp'] ?? null,
            'organization' => $intel['organization'] ?? null,
            'asn' => $intel['asn'] ?? null,
            'payload_preview' => $preview,
            'payload_field_count' => count($preview),
            'blocked' => $blocked,
            'block_reason' => $blockReason,
            'threats' => $sanitized['threats'],
            'submitted_at' => now(),
        ]);
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function payloadPreview(array $data): array
    {
        $sensitive = ['password', 'token', 'secret', 'key', 'authorization', 'cookie', 'recaptcha'];
        $result = [];

        foreach ($data as $key => $value) {
            $name = strtolower((string) $key);
            $masked = collect($sensitive)->contains(fn (string $needle): bool => str_contains($name, $needle));

            if ($masked) {
                $result[$key] = '[masked]';
                continue;
            }

            if (is_array($value)) {
                $result[$key] = '[array]';
                continue;
            }

            if (is_string($value)) {
                $result[$key] = mb_substr($value, 0, 500);
                continue;
            }

            if (is_object($value)) {
                $result[$key] = '[object:'.class_basename($value).']';
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @return array<string,mixed>
     */
    private function resolveDeviceContext(Request $request): array
    {
        $userAgent = (string) $request->userAgent();
        $platform = (string) $request->header('sec-ch-ua-platform', '');
        $acceptLanguage = (string) $request->header('accept-language', '');
        $secUa = (string) $request->header('sec-ch-ua', '');

        $frontId = trim((string) ($request->input('_device_id') ?? $request->header('X-Device-Id', '')));
        $macAddress = trim((string) ($request->input('_device_mac') ?? ''));

        if ($macAddress === '') {
            $macAddress = null;
        }

        $fingerprint = hash('sha256', implode('|', [
            $userAgent,
            $acceptLanguage,
            $platform,
            $secUa,
            (string) $request->header('sec-ch-ua-mobile', ''),
            (string) $request->header('sec-fetch-site', ''),
            (string) $request->header('sec-fetch-mode', ''),
            (string) $request->header('sec-fetch-dest', ''),
            (string) $request->input('_device_screen', ''),
            (string) $request->input('_device_timezone', ''),
        ]));

        [$browserName, $browserVersion] = $this->detectBrowser($userAgent);
        [$osName, $osVersion] = $this->detectOs($userAgent);

        $resolvedDeviceId = $frontId !== '' ? Str::limit($frontId, 120, '') : 'fp-'.substr($fingerprint, 0, 24);

        return [
            'device_id' => $resolvedDeviceId,
            'fingerprint' => $fingerprint,
            'device_type' => $this->detectDeviceType($request, $userAgent),
            'platform' => Str::limit(trim($platform, "\"' "), 120, ''),
            'model' => Str::limit((string) $request->input('_device_model', ''), 120, ''),
            'browser_name' => $browserName,
            'browser_version' => $browserVersion,
            'os_name' => $osName,
            'os_version' => $osVersion,
            'network_type' => Str::limit((string) $request->input('_device_network', ''), 30, ''),
            'mac_address' => $macAddress,
            'metadata' => [
                'accept' => (string) $request->header('accept', ''),
                'accept_language' => $acceptLanguage,
                'sec_ch_ua' => $secUa,
                'sec_ch_ua_mobile' => (string) $request->header('sec-ch-ua-mobile', ''),
                'sec_ch_ua_platform' => $platform,
                'sec_fetch_site' => (string) $request->header('sec-fetch-site', ''),
                'sec_fetch_mode' => (string) $request->header('sec-fetch-mode', ''),
                'sec_fetch_dest' => (string) $request->header('sec-fetch-dest', ''),
                'front_device' => [
                    'screen' => (string) $request->input('_device_screen', ''),
                    'timezone' => (string) $request->input('_device_timezone', ''),
                    'language' => (string) $request->input('_device_language', ''),
                    'platform' => (string) $request->input('_device_platform', ''),
                    'touch_points' => (string) $request->input('_device_touch_points', ''),
                    'vendor' => (string) $request->input('_device_vendor', ''),
                ],
            ],
        ];
    }

    private function detectDeviceType(Request $request, string $userAgent): string
    {
        $mobileHeader = strtolower((string) $request->header('sec-ch-ua-mobile', ''));

        if ($mobileHeader === '?1' || preg_match('/mobile|android|iphone|ipod/i', $userAgent)) {
            return 'mobile';
        }

        if (preg_match('/ipad|tablet/i', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * @return array{0:string|null,1:string|null}
     */
    private function detectBrowser(string $ua): array
    {
        $patterns = [
            'Edge' => '/Edg\/([0-9\.]+)/',
            'Opera' => '/OPR\/([0-9\.]+)/',
            'Chrome' => '/Chrome\/([0-9\.]+)/',
            'Firefox' => '/Firefox\/([0-9\.]+)/',
            'Safari' => '/Version\/([0-9\.]+).*Safari/',
        ];

        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $ua, $matches)) {
                return [$name, $matches[1] ?? null];
            }
        }

        return [null, null];
    }

    /**
     * @return array{0:string|null,1:string|null}
     */
    private function detectOs(string $ua): array
    {
        $patterns = [
            'Windows' => '/Windows NT ([0-9\.]+)/',
            'Android' => '/Android ([0-9\.]+)/',
            'iOS' => '/OS ([0-9\_]+) like Mac OS X/',
            'macOS' => '/Mac OS X ([0-9\_]+)/',
            'Linux' => '/Linux/',
        ];

        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $ua, $matches)) {
                $version = $matches[1] ?? null;
                return [$name, $version ? str_replace('_', '.', $version) : null];
            }
        }

        return [null, null];
    }

    /**
     * @param array<string,mixed> $device
     * @param array<string,mixed> $intel
     */
    private function resolveMatchedBlock(Request $request, array $device, array $intel): ?SecurityAccessBlock
    {
        $matches = array_filter([
            ['ip', (string) $request->ip()],
            ['device_id', (string) ($device['device_id'] ?? '')],
            ['device_fingerprint', (string) ($device['fingerprint'] ?? '')],
            ['mac_address', (string) ($device['mac_address'] ?? '')],
            ['asn', (string) ($intel['asn'] ?? '')],
            ['user_agent', substr((string) $request->userAgent(), 0, 255)],
        ], fn (array $item): bool => trim($item[1]) !== '');

        foreach ($matches as [$type, $value]) {
            $block = SecurityAccessBlock::query()
                ->active()
                ->where('type', $type)
                ->where('value', $value)
                ->first();

            if ($block) {
                return $block;
            }
        }

        return null;
    }

    private function registerBlockHit(SecurityAccessBlock $block): void
    {
        $block->forceFill([
            'last_hit_at' => now(),
            'hits' => (int) $block->hits + 1,
        ])->save();
    }
}
