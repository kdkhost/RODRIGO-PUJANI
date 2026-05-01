<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class IpIntelService
{
    /**
     * @return array<string,mixed>
     */
    public function lookup(?string $ip): array
    {
        if (app()->environment('testing')) {
            return [];
        }

        $ip = trim((string) $ip);
        if ($ip === '' || $this->isPrivateIp($ip)) {
            return [];
        }

        $cacheKey = 'ip-intel:'.sha1($ip);

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($ip): array {
            $response = Http::timeout(4)
                ->acceptJson()
                ->get("https://ipwho.is/{$ip}");

            if (! $response->ok()) {
                return [];
            }

            $data = $response->json();
            if (! is_array($data) || ! ($data['success'] ?? false)) {
                return [];
            }

            return [
                'country' => (string) ($data['country'] ?? ''),
                'region' => (string) ($data['region'] ?? ''),
                'city' => (string) ($data['city'] ?? ''),
                'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
                'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
                'timezone' => (string) ($data['timezone']['id'] ?? ''),
                'isp' => (string) ($data['connection']['isp'] ?? ''),
                'organization' => (string) ($data['connection']['org'] ?? ''),
                'asn' => (string) ($data['connection']['asn'] ?? ''),
            ];
        });
    }

    private function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
