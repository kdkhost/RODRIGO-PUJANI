<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncDjenMonitor;
use App\Models\DjenMonitor;
use App\Models\LegalCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DjenMonitorController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->ensurePermission($request);
        $validated = $request->validate([
            'type' => ['required', Rule::in([DjenMonitor::TYPE_PROCESS, DjenMonitor::TYPE_OAB])],
            'label' => ['required', 'string', 'max:255'],
            'legal_case_id' => ['nullable', 'integer', Rule::exists('legal_cases', 'id')],
            'oab_number' => ['nullable', 'string', 'max:30'],
            'oab_state' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'sync_interval_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'lookback_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'overlap_days' => ['required', 'integer', 'min:0', 'max:30'],
            'starts_at' => ['nullable', 'date'],
        ]);

        $legalCase = null;
        $processNumber = null;
        $oabNumber = null;
        $oabState = null;

        if ($validated['type'] === DjenMonitor::TYPE_PROCESS) {
            abort_unless(filled($validated['legal_case_id'] ?? null), 422, 'Selecione o processo a monitorar.');
            $legalCase = LegalCase::query()->visibleTo($request->user())->findOrFail($validated['legal_case_id']);
            $processNumber = DjenMonitor::normalizeProcessNumber($legalCase->process_number);
            abort_unless(strlen($processNumber) === 20, 422, 'O processo selecionado não possui número CNJ válido.');
        } else {
            $oabNumber = DjenMonitor::normalizeOabNumber($validated['oab_number'] ?? null);
            $oabState = DjenMonitor::normalizeOabState($validated['oab_state'] ?? null);
            abort_unless($oabNumber !== '' && preg_match('/^[A-Z]{2}$/', $oabState), 422, 'Informe o número da OAB e a UF.');
        }

        $fingerprint = DjenMonitor::fingerprintFor($validated['type'], $processNumber, $oabNumber, $oabState);
        $monitor = DjenMonitor::query()->firstOrCreate(
            ['fingerprint' => $fingerprint],
            [
                'legal_case_id' => $legalCase?->id,
                'created_by' => $request->user()->id,
                'type' => $validated['type'],
                'label' => trim($validated['label']),
                'process_number_normalized' => $processNumber,
                'oab_number_normalized' => $oabNumber,
                'oab_state' => $oabState,
                'enabled' => true,
                'sync_interval_minutes' => $validated['sync_interval_minutes'],
                'lookback_days' => $validated['lookback_days'],
                'overlap_days' => $validated['overlap_days'],
                'starts_at' => $validated['starts_at'] ?? null,
            ],
        );

        activity_log('djen_monitors', $monitor->wasRecentlyCreated ? 'created' : 'duplicate_ignored', $monitor, [
            'type' => $monitor->type,
            'fingerprint' => $monitor->fingerprint,
        ], 'Monitor DJEN configurado.');

        return back()->with(
            $monitor->wasRecentlyCreated ? 'success' : 'info',
            $monitor->wasRecentlyCreated ? 'Monitor DJEN criado com segurança.' : 'Esse monitor DJEN já estava cadastrado.',
        );
    }

    public function update(Request $request, DjenMonitor $monitor): RedirectResponse
    {
        $this->ensurePermission($request);
        $monitor = $this->visibleMonitor($request, $monitor);
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'enabled' => ['nullable', 'boolean'],
            'sync_interval_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'lookback_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'overlap_days' => ['required', 'integer', 'min:0', 'max:30'],
            'starts_at' => ['nullable', 'date'],
        ]);
        $monitor->fill([
            'label' => trim($validated['label']),
            'enabled' => $request->boolean('enabled'),
            'sync_interval_minutes' => $validated['sync_interval_minutes'],
            'lookback_days' => $validated['lookback_days'],
            'overlap_days' => $validated['overlap_days'],
            'starts_at' => $validated['starts_at'] ?? null,
            'next_sync_at' => $request->boolean('enabled') ? now() : null,
        ])->save();
        activity_log('djen_monitors', 'updated', $monitor, $monitor->getChanges(), 'Monitor DJEN atualizado.');

        return back()->with('success', 'Monitor DJEN atualizado.');
    }

    public function sync(Request $request, DjenMonitor $monitor): RedirectResponse|JsonResponse
    {
        $this->ensurePermission($request);
        $monitor = $this->visibleMonitor($request, $monitor);
        abort_unless($monitor->enabled, 422, 'Ative o monitor antes de sincronizar.');
        SyncDjenMonitor::dispatch($monitor->id, $request->user()->id, 'manual');
        activity_log('djen_monitors', 'sync_queued', $monitor, [], 'Sincronização DJEN enfileirada.');

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Sincronização DJEN enfileirada com segurança.'], 202);
        }

        return back()->with('success', 'Sincronização DJEN enfileirada com segurança.');
    }

    private function visibleMonitor(Request $request, DjenMonitor $monitor): DjenMonitor
    {
        return DjenMonitor::query()->visibleTo($request->user())->findOrFail($monitor->id);
    }

    private function ensurePermission(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ($user->canViewAllLegalOperations() || $user->can('djen-monitors.manage')), 403);
    }
}
