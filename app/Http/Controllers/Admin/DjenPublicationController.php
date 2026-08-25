<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DjenMonitor;
use App\Models\DjenPublication;
use App\Models\LegalCase;
use App\Services\DjenPublicationReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class DjenPublicationController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensurePermission($request, 'view');
        $base = DjenPublication::query()->visibleTo($request->user());
        $query = (clone $base)->with([
            'legalCase:id,client_id,title,process_number',
            'client:id,name',
            'reviewer:id,name',
            'lastSyncRun:id,uuid,status,created_at',
        ]);

        if ($request->filled('status')) {
            $query->where('review_status', $request->string('status')->toString());
        }

        if ($request->filled('tribunal')) {
            $query->where('tribunal', $request->string('tribunal')->trim()->upper()->toString());
        }

        if ($request->filled('process_number')) {
            $query->where('process_number_normalized', DjenMonitor::normalizeProcessNumber($request->string('process_number')->toString()));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim()->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('raw_text', 'like', $search)
                    ->orWhere('communication_number', 'like', $search)
                    ->orWhere('court_body', 'like', $search)
                    ->orWhereHas('legalCase', fn ($caseQuery) => $caseQuery->where('title', 'like', $search));
            });
        }

        $monitors = DjenMonitor::query()
            ->visibleTo($request->user())
            ->with(['legalCase:id,title,process_number', 'syncRuns' => fn ($runQuery) => $runQuery->latest()->limit(1)])
            ->latest()
            ->get();

        return view('admin.djen-publications.index', [
            'publications' => $query->latest('availability_date')->latest('id')->paginate(25)->withQueryString(),
            'monitors' => $monitors,
            'legalCases' => LegalCase::query()->visibleTo($request->user())->whereNotNull('process_number')->orderBy('title')->get(['id', 'title', 'process_number']),
            'tribunals' => (clone $base)->whereNotNull('tribunal')->distinct()->orderBy('tribunal')->pluck('tribunal'),
            'summary' => [
                'total' => (clone $base)->count(),
                'pending' => (clone $base)->where('review_status', DjenPublication::STATUS_PENDING)->count(),
                'approved' => (clone $base)->where('review_status', DjenPublication::STATUS_APPROVED)->count(),
                'rejected' => (clone $base)->where('review_status', DjenPublication::STATUS_REJECTED)->count(),
            ],
            'canReview' => $this->hasPermission($request, 'review'),
            'canManageMonitors' => $this->hasPermission($request, 'manage'),
        ]);
    }

    public function show(Request $request, DjenPublication $publication): View
    {
        $this->ensurePermission($request, 'view');
        $publication = $this->visiblePublication($request, $publication);
        $publication->load([
            'legalCase:id,client_id,title,process_number',
            'client:id,name',
            'reviewer:id,name',
            'monitors:id,label,type',
            'lastSyncRun',
            'legalCaseUpdate.summaries.generator:id,name',
            'legalCaseUpdate.summaries.reviewer:id,name',
            'legalCaseUpdate.summaries.approver:id,name',
        ]);

        return view('admin.djen-publications.show', [
            'publication' => $publication,
            'legalCases' => LegalCase::query()->visibleTo($request->user())->whereNotNull('process_number')->orderBy('title')->get(['id', 'title', 'process_number']),
            'canReview' => $this->hasPermission($request, 'review'),
            'canGenerateSummary' => $request->user()?->can('legal-ai.generate') === true,
            'canReviewSummary' => $request->user()?->can('legal-ai.review') === true,
            'canApproveSummary' => $request->user()?->can('legal-ai.approve') === true,
            'canPublishSummary' => $request->user()?->can('legal-ai.publish') === true,
        ]);
    }

    public function approve(
        Request $request,
        DjenPublication $publication,
        DjenPublicationReviewService $service,
    ): RedirectResponse {
        $this->ensurePermission($request, 'review');
        $publication = $this->visiblePublication($request, $publication);
        $validated = $request->validate([
            'legal_case_id' => ['nullable', 'integer', Rule::exists('legal_cases', 'id')],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);
        $legalCase = filled($validated['legal_case_id'] ?? null)
            ? LegalCase::query()->visibleTo($request->user())->findOrFail($validated['legal_case_id'])
            : null;

        try {
            $service->approve($publication, $request->user(), $legalCase, $validated['notes'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Publicação aprovada e liberada no andamento do cliente.');
    }

    public function reject(
        Request $request,
        DjenPublication $publication,
        DjenPublicationReviewService $service,
    ): RedirectResponse {
        $this->ensurePermission($request, 'review');
        $publication = $this->visiblePublication($request, $publication);
        $validated = $request->validate(['notes' => ['required', 'string', 'min:5', 'max:4000']]);

        try {
            $service->reject($publication, $request->user(), $validated['notes']);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Publicação rejeitada sem exposição ao cliente.');
    }

    public function reopen(
        Request $request,
        DjenPublication $publication,
        DjenPublicationReviewService $service,
    ): RedirectResponse {
        $this->ensurePermission($request, 'review');
        $publication = $this->visiblePublication($request, $publication);
        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:4000']]);
        $service->reopen($publication, $request->user(), $validated['notes'] ?? null);

        return back()->with('success', 'Publicação devolvida para revisão e ocultada do cliente.');
    }

    private function visiblePublication(Request $request, DjenPublication $publication): DjenPublication
    {
        return DjenPublication::query()->visibleTo($request->user())->findOrFail($publication->id);
    }

    private function ensurePermission(Request $request, string $action): void
    {
        abort_unless($this->hasPermission($request, $action), 403);
    }

    private function hasPermission(Request $request, string $action): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if ($user->canViewAllLegalOperations()) {
            return true;
        }

        $permissions = match ($action) {
            'view' => ['djen-publications.view', 'djen-publications.review', 'djen-monitors.manage'],
            'review' => ['djen-publications.review'],
            default => ['djen-monitors.manage'],
        };

        return collect($permissions)->contains(fn (string $permission): bool => $user->can($permission));
    }
}
