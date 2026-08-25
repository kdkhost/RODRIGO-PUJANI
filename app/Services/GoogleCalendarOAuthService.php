<?php

namespace App\Services;

use App\Models\GoogleCalendarConnection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use RuntimeException;

class GoogleCalendarOAuthService
{
    public function authorizationUrl(string $state, ?string $loginHint = null): string
    {
        $this->ensureConfigured();

        return (string) url()->query((string) config('google-calendar.authorization_url'), array_filter([
            'client_id' => config('google-calendar.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', (array) config('google-calendar.scopes', [])),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
            'login_hint' => $loginHint,
        ], fn (mixed $value): bool => filled($value)));
    }

    public function exchangeAuthorizationCode(string $code): array
    {
        $this->ensureConfigured();
        $response = Http::asForm()
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout((int) config('google-calendar.timeout', 20))
            ->post((string) config('google-calendar.token_url'), [
                'client_id' => config('google-calendar.client_id'),
                'client_secret' => config('google-calendar.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri(),
            ]);

        return $this->validatedTokenPayload($response, true);
    }

    public function accessToken(GoogleCalendarConnection $connection, bool $forceRefresh = false): string
    {
        if (! $forceRefresh
            && filled($connection->access_token)
            && $connection->token_expires_at?->greaterThan(now()->addMinutes(2))) {
            return (string) $connection->access_token;
        }

        if (blank($connection->refresh_token)) {
            throw new RuntimeException('A conexão com o Google expirou e precisa ser autorizada novamente.');
        }

        $response = Http::asForm()
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout((int) config('google-calendar.timeout', 20))
            ->post((string) config('google-calendar.token_url'), [
                'client_id' => config('google-calendar.client_id'),
                'client_secret' => config('google-calendar.client_secret'),
                'refresh_token' => $connection->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

        $payload = $this->validatedTokenPayload($response, false);
        $connection->forceFill([
            'access_token' => $payload['access_token'],
            'refresh_token' => $payload['refresh_token'] ?? $connection->refresh_token,
            'token_expires_at' => now()->addSeconds(max(60, (int) ($payload['expires_in'] ?? 3600))),
            'scopes' => filled($payload['scope'] ?? null)
                ? preg_split('/\s+/', trim((string) $payload['scope']))
                : $connection->scopes,
        ])->save();

        return (string) $connection->access_token;
    }

    public function request(
        GoogleCalendarConnection $connection,
        string $method,
        string $path,
        array $options = [],
    ): Response {
        $response = $this->send($connection, $method, $path, $options, false);

        if ($response->status() === 401 && filled($connection->refresh_token)) {
            $response = $this->send($connection->fresh(), $method, $path, $options, true);
        }

        return $response;
    }

    public function userInfo(GoogleCalendarConnection $connection): array
    {
        $response = Http::withToken($this->accessToken($connection))
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout((int) config('google-calendar.timeout', 20))
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (! $response->successful()) {
            throw new RuntimeException('Não foi possível confirmar a conta conectada ao Google.');
        }

        return is_array($response->json()) ? $response->json() : [];
    }

    public function listCalendars(GoogleCalendarConnection $connection): array
    {
        $items = [];
        $pageToken = null;

        do {
            $response = $this->request($connection, 'GET', '/users/me/calendarList', [
                'query' => array_filter(['maxResults' => 250, 'pageToken' => $pageToken]),
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('Não foi possível listar os calendários disponíveis na conta Google.');
            }

            foreach ((array) $response->json('items', []) as $calendar) {
                if (is_array($calendar) && filled($calendar['id'] ?? null)) {
                    $items[] = [
                        'id' => (string) $calendar['id'],
                        'summary' => (string) ($calendar['summaryOverride'] ?? $calendar['summary'] ?? $calendar['id']),
                        'primary' => (bool) ($calendar['primary'] ?? false),
                        'access_role' => (string) ($calendar['accessRole'] ?? ''),
                    ];
                }
            }

            $pageToken = $response->json('nextPageToken');
        } while (filled($pageToken));

        return $items;
    }

    public function revoke(GoogleCalendarConnection $connection): void
    {
        $token = $connection->refresh_token ?: $connection->access_token;

        if (blank($token)) {
            return;
        }

        Http::asForm()
            ->connectTimeout(5)
            ->timeout((int) config('google-calendar.timeout', 20))
            ->post((string) config('google-calendar.revoke_url'), ['token' => $token]);
    }

    public function redirectUri(): string
    {
        $configured = trim((string) config('google-calendar.redirect_uri'));

        if ($configured !== '') {
            return $configured;
        }

        return Route::has('admin.google-calendar.callback')
            ? route('admin.google-calendar.callback')
            : url('/admin/google-calendar/callback');
    }

    private function send(
        GoogleCalendarConnection $connection,
        string $method,
        string $path,
        array $options,
        bool $forceRefresh,
    ): Response {
        $url = rtrim((string) config('google-calendar.api_url'), '/').'/'.ltrim($path, '/');

        return Http::withToken($this->accessToken($connection, $forceRefresh))
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout((int) config('google-calendar.timeout', 20))
            ->retry(2, 500, throw: false)
            ->send(strtoupper($method), $url, $options);
    }

    private function validatedTokenPayload(Response $response, bool $refreshTokenExpected): array
    {
        $payload = is_array($response->json()) ? $response->json() : [];

        if (! $response->successful() || blank($payload['access_token'] ?? null)) {
            throw new RuntimeException('A autorização do Google Calendar não pôde ser concluída.');
        }

        if ($refreshTokenExpected && blank($payload['refresh_token'] ?? null)) {
            throw new RuntimeException('O Google não forneceu acesso offline. Remova a autorização anterior e tente novamente.');
        }

        return $payload;
    }

    private function ensureConfigured(): void
    {
        if (! config('google-calendar.enabled')
            || blank(config('google-calendar.client_id'))
            || blank(config('google-calendar.client_secret'))) {
            throw new RuntimeException('A integração Google Calendar ainda não foi configurada pelo administrador.');
        }
    }
}
