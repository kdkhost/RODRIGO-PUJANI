<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! $user->is_active) {
            abort(403, 'Usuário inativo.');
        }

        if (! $user->hasRole('Super Admin') && ! $user->can('admin.access')) {
            throw new HttpException(403, 'Acesso administrativo não autorizado.');
        }

        return $next($request);
    }
}
