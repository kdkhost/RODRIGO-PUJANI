<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemFilesPageConfirmed
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('system_files.page_confirmed') === true) {
            return $next($request);
        }

        $request->session()->put('system_files.intended_url', $request->fullUrl());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Confirme sua senha para acessar os arquivos sensíveis do sistema.',
                'redirect' => route('admin.system-files.confirm'),
            ], 423);
        }

        return redirect()->route('admin.system-files.confirm');
    }
}
