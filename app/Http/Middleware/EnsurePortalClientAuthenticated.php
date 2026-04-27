<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalClientAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = $request->session()->get('portal_client_id');

        if (! $clientId) {
            return redirect()->route('portal.login');
        }

        $client = Client::query()
            ->whereKey($clientId)
            ->where('portal_enabled', true)
            ->where('is_active', true)
            ->first();

        if (! $client) {
            $request->session()->forget('portal_client_id');

            return redirect()
                ->route('portal.login')
                ->with('portal_error', 'Sua sessão do portal expirou. Entre novamente para continuar.');
        }

        $request->attributes->set('portalClient', $client);

        return $next($request);
    }
}
