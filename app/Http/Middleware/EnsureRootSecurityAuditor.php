<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRootSecurityAuditor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && (int) $user->id === User::PROTECTED_ROOT_USER_ID, 403);

        return $next($request);
    }
}

