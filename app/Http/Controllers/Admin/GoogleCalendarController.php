<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleCalendarConnection;
use App\Services\GoogleCalendarOAuthService;
use App\Services\GoogleCalendarSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class GoogleCalendarController extends Controller
{
    public function index(GoogleCalendarOAuthService $oauth): View
    {
        $connection = GoogleCalendarConnection::query()->where('user_id', Auth::id())->first();
        $calendars = [];
        $connectionError = null;

        if ($connection) {
            try {
                $calendars = $oauth->listCalendars($connection);
            } catch (Throwable) {
                $connectionError = 'Não foi possível atualizar a lista de calendários. Reconecte a conta se o erro persistir.';
            }
        }

        return view('admin.google-calendar.index', [
            'pageTitle' => 'Google Calendar',
            'connection' => $connection,
            'calendars' => $calendars,
            'connectionError' => $connectionError,
            'integrationConfigured' => (bool) config('google-calendar.enabled')
                && filled(config('google-calendar.client_id'))
                && filled(config('google-calendar.client_secret')),
            'redirectUri' => $oauth->redirectUri(),
        ]);
    }

    public function connect(Request $request, GoogleCalendarOAuthService $oauth): RedirectResponse
    {
        $state = Str::random(64);
        $request->session()->put('google_calendar_oauth_state', $state);

        try {
            return redirect()->away($oauth->authorizationUrl($state, $request->user()?->email));
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.google-calendar.index')->with('error', $exception->getMessage());
        }
    }

    public function callback(Request $request, GoogleCalendarOAuthService $oauth): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('google_calendar_oauth_state', '');
        $receivedState = (string) $request->query('state', '');

        if ($expectedState === '' || $receivedState === '' || ! hash_equals($expectedState, $receivedState)) {
            return redirect()->route('admin.google-calendar.index')->with('error', 'A autorização do Google expirou ou possui estado inválido.');
        }

        if ($request->filled('error')) {
            return redirect()->route('admin.google-calendar.index')->with('error', 'A autorização do Google Calendar foi cancelada.');
        }

        $code = trim((string) $request->query('code', ''));

        if ($code === '') {
            return redirect()->route('admin.google-calendar.index')->with('error', 'O Google não retornou o código de autorização.');
        }

        try {
            $payload = $oauth->exchangeAuthorizationCode($code);
            $connection = GoogleCalendarConnection::query()->updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'access_token' => $payload['access_token'],
                    'refresh_token' => $payload['refresh_token'],
                    'token_expires_at' => now()->addSeconds(max(60, (int) ($payload['expires_in'] ?? 3600))),
                    'scopes' => preg_split('/\s+/', trim((string) ($payload['scope'] ?? implode(' ', config('google-calendar.scopes', []))))),
                    'calendar_id' => 'primary',
                    'calendar_name' => 'Principal',
                    'sync_enabled' => true,
                    'sync_token' => null,
                    'last_error' => null,
                ],
            );
            $profile = $oauth->userInfo($connection);
            $connection->forceFill(['google_account_email' => $profile['email'] ?? null])->save();
            activity_log('google_calendar', 'connected', $connection, [
                'user_id' => $connection->user_id,
                'google_account_email' => $connection->google_account_email,
            ], 'Conta Google Calendar conectada.');
        } catch (Throwable $exception) {
            report(new RuntimeException('Falha sanitizada no callback do Google Calendar.', (int) $exception->getCode()));

            return redirect()->route('admin.google-calendar.index')->with('error', 'Não foi possível concluir a conexão com o Google Calendar.');
        }

        return redirect()->route('admin.google-calendar.index')->with('success', 'Google Calendar conectado com sucesso.');
    }

    public function status(Request $request): JsonResponse
    {
        $connection = GoogleCalendarConnection::query()->where('user_id', $request->user()->id)->first();

        return response()->json([
            'connected' => (bool) $connection,
            'sync_enabled' => (bool) $connection?->sync_enabled,
            'calendar_id' => $connection?->calendar_id,
            'calendar_name' => $connection?->calendar_name,
            'account_email' => $connection?->google_account_email,
            'last_synced_at' => $connection?->last_synced_at?->toIso8601String(),
            'last_success_at' => $connection?->last_success_at?->toIso8601String(),
            'has_error' => filled($connection?->last_error),
        ]);
    }

    public function update(Request $request, GoogleCalendarOAuthService $oauth): JsonResponse
    {
        $connection = GoogleCalendarConnection::query()->where('user_id', $request->user()->id)->firstOrFail();
        $validated = $request->validate([
            'calendar_id' => ['required', 'string', 'max:1024'],
            'sync_enabled' => ['nullable', 'boolean'],
        ]);
        $calendars = collect($oauth->listCalendars($connection));
        $calendar = $calendars->firstWhere('id', $validated['calendar_id']);

        if (! $calendar || ! in_array($calendar['access_role'], ['owner', 'writer'], true)) {
            return response()->json(['message' => 'Selecione um calendário no qual a conta possua permissão de escrita.'], 422);
        }

        $connection->forceFill([
            'calendar_id' => $calendar['id'],
            'calendar_name' => $calendar['summary'],
            'sync_enabled' => $request->boolean('sync_enabled'),
            'sync_token' => null,
            'last_error' => null,
        ])->save();
        activity_log('google_calendar', 'updated', $connection, [
            'calendar_id' => $connection->calendar_id,
            'calendar_name' => $connection->calendar_name,
            'sync_enabled' => $connection->sync_enabled,
        ], 'Configuração do Google Calendar atualizada.');

        return response()->json(['message' => 'Configuração do Google Calendar atualizada.']);
    }

    public function sync(
        Request $request,
        GoogleCalendarSyncService $service,
    ): JsonResponse {
        $connection = GoogleCalendarConnection::query()->where('user_id', $request->user()->id)->firstOrFail();

        try {
            $result = $service->sync($connection);
        } catch (Throwable $exception) {
            report(new RuntimeException('Falha sanitizada na sincronização manual do Google Calendar.', (int) $exception->getCode()));

            return response()->json(['message' => 'A sincronização falhou. Verifique a conexão e tente novamente.'], 422);
        }

        activity_log('google_calendar', 'synced', $connection, $result, 'Google Calendar sincronizado manualmente.');

        return response()->json([
            'message' => "Sincronização concluída: {$result['pushed']} enviado(s), {$result['pulled']} importado(s), {$result['canceled']} cancelado(s).",
            'result' => $result,
            'calendarTarget' => '#admin-calendar',
        ]);
    }

    public function disconnect(Request $request, GoogleCalendarOAuthService $oauth): JsonResponse
    {
        $connection = GoogleCalendarConnection::query()->where('user_id', $request->user()->id)->firstOrFail();

        try {
            $oauth->revoke($connection);
        } catch (Throwable) {
            // A remoção local continua; nenhum evento remoto é apagado.
        }

        activity_log('google_calendar', 'disconnected', $connection, [
            'calendar_id' => $connection->calendar_id,
        ], 'Conta Google Calendar desconectada sem apagar eventos remotos.');
        $connection->delete();

        return response()->json([
            'message' => 'Conta desconectada. Nenhum evento foi apagado do Google Calendar.',
            'redirect' => route('admin.google-calendar.index'),
        ]);
    }
}
