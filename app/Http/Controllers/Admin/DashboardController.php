<?php

namespace App\Http\Controllers\Admin;

use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\ContactMessage;
use App\Models\LegalCase;
use App\Models\LegalCaseUpdate;
use App\Models\LegalDocument;
use App\Models\LegalTask;
use App\Models\PageVisit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends \App\Http\Controllers\Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isAssociatedLawyer = $user?->isAssociatedLawyer() ?? false;
        $rangeStart = now()->subDays(13)->startOfDay();

        $rawVisitsByDay = PageVisit::query()
            ->selectRaw('DATE(visited_at) as day, COUNT(*) as total')
            ->where('visited_at', '>=', $rangeStart)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->pluck('total', 'day');

        $visitsByDay = collect(range(13, 0))->map(function (int $daysAgo) use ($rawVisitsByDay): array {
            $date = now()->subDays($daysAgo);

            return [
                'day' => $date->format('d/m'),
                'total' => (int) ($rawVisitsByDay[$date->toDateString()] ?? 0),
            ];
        });

        $clientsQuery = $this->clientsQuery($user?->id, $isAssociatedLawyer);
        $casesQuery = $this->casesQuery($user?->id, $isAssociatedLawyer);
        $tasksQuery = $this->tasksQuery($user?->id, $isAssociatedLawyer);
        $eventsQuery = $this->eventsQuery($user?->id, $isAssociatedLawyer);
        $updatesQuery = $this->updatesQuery($user?->id, $isAssociatedLawyer);
        $documentsQuery = $this->documentsQuery($user?->id, $isAssociatedLawyer);

        $overviewCards = [
            [
                'label' => $isAssociatedLawyer ? 'Clientes da carteira' : 'Clientes ativos',
                'value' => (clone $clientsQuery)->where('is_active', true)->count(),
                'icon' => 'bi-people',
                'tone' => 'gold',
            ],
            [
                'label' => 'Processos ativos',
                'value' => (clone $casesQuery)
                    ->where('is_active', true)
                    ->whereNotIn('status', ['closed', 'archived'])
                    ->count(),
                'icon' => 'bi-briefcase',
                'tone' => 'blue',
            ],
            [
                'label' => 'Prazos em 7 dias',
                'value' => (clone $casesQuery)
                    ->where('is_active', true)
                    ->whereBetween('next_deadline_at', [now(), now()->copy()->addDays(7)->endOfDay()])
                    ->count(),
                'icon' => 'bi-alarm',
                'tone' => 'red',
            ],
            [
                'label' => 'Tarefas abertas',
                'value' => (clone $tasksQuery)
                    ->whereNotIn('status', ['done', 'canceled'])
                    ->count(),
                'icon' => 'bi-list-check',
                'tone' => 'purple',
            ],
            [
                'label' => 'Agenda de hoje',
                'value' => (clone $eventsQuery)->whereDate('start_at', today())->count(),
                'icon' => 'bi-calendar3',
                'tone' => 'green',
            ],
            [
                'label' => 'Portal liberado',
                'value' => (clone $clientsQuery)->where('portal_enabled', true)->count(),
                'icon' => 'bi-shield-lock',
                'tone' => 'cyan',
            ],
        ];

        $caseStatusBreakdown = (clone $casesQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->where('is_active', true)
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'label' => str((string) $row->status)->replace('_', ' ')->headline()->toString(),
                'total' => (int) $row->total,
            ]);

        $taskStatusBreakdown = (clone $tasksQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->whereNotIn('status', ['canceled'])
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'label' => str((string) $row->status)->replace('_', ' ')->headline()->toString(),
                'total' => (int) $row->total,
            ]);

        return view('admin.dashboard', [
            'pageTitle' => 'Painel Administrativo',
            'dashboardScopeLabel' => $isAssociatedLawyer ? 'Carteira individual do advogado' : 'Operação integrada do escritório',
            'isAssociatedLawyer' => $isAssociatedLawyer,
            'overviewCards' => $overviewCards,
            'visitsByDay' => $visitsByDay,
            'caseStatusBreakdown' => $caseStatusBreakdown,
            'taskStatusBreakdown' => $taskStatusBreakdown,
            'upcomingDeadlines' => (clone $casesQuery)
                ->with(['client:id,name', 'primaryLawyer:id,name'])
                ->where('is_active', true)
                ->whereNotNull('next_deadline_at')
                ->orderBy('next_deadline_at')
                ->limit(6)
                ->get(),
            'todayAgenda' => (clone $eventsQuery)
                ->with(['owner:id,name'])
                ->where('start_at', '>=', now()->startOfDay())
                ->orderBy('start_at')
                ->limit(6)
                ->get(),
            'latestContacts' => ContactMessage::query()->latest()->limit(6)->get(),
            'latestUpdates' => (clone $updatesQuery)
                ->with(['legalCase:id,title', 'client:id,name'])
                ->latest('occurred_at')
                ->limit(6)
                ->get(),
            'workloadByLawyer' => $isAssociatedLawyer
                ? collect()
                : User::query()
                    ->visibleTo($user)
                    ->where('is_active', true)
                    ->withCount([
                        'primaryLegalCases as open_cases_count' => fn (Builder $query) => $query
                            ->where('is_active', true)
                            ->whereNotIn('status', ['closed', 'archived']),
                        'legalTasks as open_tasks_count' => fn (Builder $query) => $query
                            ->whereNotIn('status', ['done', 'canceled']),
                    ])
                    ->orderByDesc('open_cases_count')
                    ->orderByDesc('open_tasks_count')
                    ->get(['id', 'name'])
                    ->filter(fn (User $lawyer) => $lawyer->open_cases_count > 0 || $lawyer->open_tasks_count > 0)
                    ->take(5)
                    ->values(),
            'operationalHighlights' => [
                [
                    'label' => 'Andamentos recentes',
                    'value' => (clone $updatesQuery)
                        ->where('occurred_at', '>=', now()->subDays(7)->startOfDay())
                        ->count(),
                ],
                [
                    'label' => 'Documentos compartilhados',
                    'value' => (clone $documentsQuery)->where('shared_with_client', true)->count(),
                ],
                [
                    'label' => 'Tarefas concluídas no mês',
                    'value' => (clone $tasksQuery)
                        ->where('status', 'done')
                        ->where('completed_at', '>=', now()->startOfMonth())
                        ->count(),
                ],
                [
                    'label' => 'Leads em aberto',
                    'value' => ContactMessage::query()->whereIn('status', ['new', 'pending', 'open'])->count(),
                ],
            ],
        ]);
    }

    private function clientsQuery(?int $userId, bool $isAssociatedLawyer): Builder
    {
        return Client::query()
            ->when($isAssociatedLawyer && $userId, fn (Builder $query) => $query->where('assigned_lawyer_id', $userId));
    }

    private function casesQuery(?int $userId, bool $isAssociatedLawyer): Builder
    {
        return LegalCase::query()
            ->when($isAssociatedLawyer && $userId, function (Builder $query) use ($userId): void {
                $query->where(function (Builder $builder) use ($userId): void {
                    $builder
                        ->where('primary_lawyer_id', $userId)
                        ->orWhere('supervising_lawyer_id', $userId);
                });
            });
    }

    private function tasksQuery(?int $userId, bool $isAssociatedLawyer): Builder
    {
        return LegalTask::query()
            ->when($isAssociatedLawyer && $userId, function (Builder $query) use ($userId): void {
                $query->where(function (Builder $builder) use ($userId): void {
                    $builder
                        ->where('assigned_user_id', $userId)
                        ->orWhereHas('legalCase', fn (Builder $caseQuery) => $caseQuery->where('primary_lawyer_id', $userId));
                });
            });
    }

    private function eventsQuery(?int $userId, bool $isAssociatedLawyer): Builder
    {
        return CalendarEvent::query()
            ->when($isAssociatedLawyer && $userId, function (Builder $query) use ($userId): void {
                $query->where(function (Builder $builder) use ($userId): void {
                    $builder
                        ->where('owner_id', $userId)
                        ->orWhere(function (Builder $nested) use ($userId): void {
                            $nested
                                ->whereNull('owner_id')
                                ->where('created_by', $userId);
                        });
                });
            });
    }

    private function updatesQuery(?int $userId, bool $isAssociatedLawyer): Builder
    {
        return LegalCaseUpdate::query()
            ->when($isAssociatedLawyer && $userId, fn (Builder $query) => $query->whereHas('legalCase', fn (Builder $caseQuery) => $caseQuery->where('primary_lawyer_id', $userId)));
    }

    private function documentsQuery(?int $userId, bool $isAssociatedLawyer): Builder
    {
        return LegalDocument::query()
            ->when($isAssociatedLawyer && $userId, fn (Builder $query) => $query->whereHas('legalCase', fn (Builder $caseQuery) => $caseQuery->where('primary_lawyer_id', $userId)));
    }
}
