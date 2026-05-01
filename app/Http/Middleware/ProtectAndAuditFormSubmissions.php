<?php

namespace App\Http\Middleware;

use App\Models\FormSecurityLog;
use App\Services\IpIntelService;
use App\Support\InputSecuritySanitizer;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $rawPayload = $request->except(['password', 'password_confirmation', '_token', 'g-recaptcha-response', 'recaptcha_token']);
        $sanitized = InputSecuritySanitizer::sanitize(is_array($rawPayload) ? $rawPayload : []);
        $request->merge($sanitized['data']);

        if ($sanitized['blocked']) {
            $this->storeAudit($request, $sanitized, true, $sanitized['reason']);

            if ($request->expectsJson() || $request->ajax()) {
                return new JsonResponse([
                    'message' => 'Conteudo bloqueado por seguranca. Revise os campos enviados.',
                    'errors' => ['security' => ['Conteudo bloqueado por seguranca.']],
                ], 422);
            }

            return new RedirectResponse(url()->previous())
                ->withErrors(['security' => 'Conteudo bloqueado por seguranca. Revise os campos enviados.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        $response = $next($request);
        $this->storeAudit($request, $sanitized, false, null);

        return $response;
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
     * @param array{data:array<string,mixed>,blocked:bool,reason:?string,threats:array<int,string>} $sanitized
     */
    private function storeAudit(Request $request, array $sanitized, bool $blocked, ?string $blockReason): void
    {
        $ip = $request->ip();
        $intel = $this->ipIntel->lookup($ip);
        $routeName = optional($request->route())->getName();
        $preview = $this->payloadPreview($sanitized['data']);
        $portalClientId = (int) ($request->session()->get('portal_client_id') ?? 0);

        FormSecurityLog::query()->create([
            'user_id' => auth()->id(),
            'portal_client_id' => $portalClientId > 0 ? $portalClientId : null,
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
            'device_fingerprint' => hash('sha256', implode('|', [
                (string) $request->userAgent(),
                (string) $request->header('accept-language', ''),
                (string) $request->header('sec-ch-ua-platform', ''),
                (string) $request->header('sec-ch-ua', ''),
            ])),
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
}
