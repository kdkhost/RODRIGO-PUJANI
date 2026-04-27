<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        $impersonator = $request->user();

        if (! $impersonator || $impersonator->is($user)) {
            return back()->with('error', 'Não é possível assumir o próprio usuário.');
        }

        if (! $user->is_active) {
            return back()->with('error', 'Este usuário está inativo.');
        }

        $request->session()->put([
            'impersonator_id' => $impersonator->id,
            'impersonator_name' => $impersonator->name,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        activity_log('users', 'impersonate.started', $user, [
            'impersonator_id' => $impersonator->id,
            'target_id' => $user->id,
        ], 'Acesso assistido iniciado.');

        return redirect()->route('admin.dashboard')->with('status', 'Acesso assistido iniciado.');
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_id');
        $request->session()->forget('impersonator_name');

        if (! $impersonatorId) {
            return redirect()->route('admin.dashboard');
        }

        $target = $request->user();
        Auth::loginUsingId($impersonatorId);
        $request->session()->regenerate();

        activity_log('users', 'impersonate.stopped', $target, [
            'impersonator_id' => $impersonatorId,
            'target_id' => $target?->id,
        ], 'Acesso assistido encerrado.');

        return redirect()->route('admin.users.index')->with('status', 'Acesso assistido encerrado.');
    }
}
