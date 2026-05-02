<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormSecurityLog;
use App\Models\SecurityAccessBlock;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FormSecurityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = FormSecurityLog::query()->latest('submitted_at');

        if ($request->filled('blocked')) {
            $query->where('blocked', $request->boolean('blocked'));
        }

        if ($request->filled('route')) {
            $query->where('route_name', 'like', '%'.trim((string) $request->input('route')).'%');
        }

        if ($request->filled('ip')) {
            $query->where('ip_address', 'like', '%'.trim((string) $request->input('ip')).'%');
        }

        if ($request->filled('fingerprint')) {
            $query->where('device_fingerprint', 'like', '%'.trim((string) $request->input('fingerprint')).'%');
        }

        if ($request->filled('device_id')) {
            $query->where('device_id', 'like', '%'.trim((string) $request->input('device_id')).'%');
        }

        if ($request->filled('block_type')) {
            $query->whereHas('block', fn ($builder) => $builder->where('type', $request->input('block_type')));
        }

        return view('admin.form-security-logs.index', [
            'logs' => $query->paginate(30)->withQueryString(),
        ]);
    }

    public function block(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:40'],
            'value' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $allowed = ['ip', 'device_id', 'device_fingerprint', 'mac_address', 'asn', 'user_agent'];
        if (! in_array($validated['type'], $allowed, true)) {
            return back()->with('error', 'Tipo de bloqueio invalido.');
        }

        SecurityAccessBlock::query()->updateOrCreate(
            ['type' => $validated['type'], 'value' => trim((string) $validated['value'])],
            [
                'reason' => $validated['reason'] ?: 'Bloqueio manual pelo painel',
                'notes' => $validated['notes'] ?? null,
                'is_active' => true,
                'blocked_by_user_id' => auth()->id(),
                'released_by_user_id' => null,
                'released_at' => null,
            ]
        );

        return back()->with('status', 'Bloqueio aplicado com sucesso.');
    }

    public function unblock(SecurityAccessBlock $block): RedirectResponse
    {
        $block->forceFill([
            'is_active' => false,
            'released_by_user_id' => auth()->id(),
            'released_at' => now(),
        ])->save();

        return back()->with('status', 'Bloqueio removido com sucesso.');
    }
}
