<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContactMessage;
use App\Models\PageVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AnalyticsController extends \App\Http\Controllers\Controller
{
    public function index(Request $request): View
    {
        $window = $request->integer('window', 30);
        $window = in_array($window, [7, 14, 30, 90], true) ? $window : 30;
        $from = now()->subDays($window - 1)->startOfDay();

        $visitsWindow = PageVisit::query()->where('visited_at', '>=', $from);
        $leadsWindow = ContactMessage::query()->where('created_at', '>=', $from);

        $rawVisitsByDay = (clone $visitsWindow)
            ->selectRaw('DATE(visited_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->pluck('total', 'day');

        $visitsByDay = collect(range($window - 1, 0))->map(function (int $daysAgo) use ($rawVisitsByDay): array {
            $date = now()->subDays($daysAgo);

            return [
                'day' => $date->format('d/m'),
                'total' => (int) ($rawVisitsByDay[$date->toDateString()] ?? 0),
            ];
        });

        $totalVisits = (clone $visitsWindow)->count();
        $uniqueSessions = (clone $visitsWindow)->whereNotNull('session_id')->distinct('session_id')->count('session_id');
        $leadCount = (clone $leadsWindow)->count();
        $conversionRate = $totalVisits > 0 ? round(($leadCount / $totalVisits) * 100, 2) : 0;

        return view('admin.analytics.index', [
            'pageTitle' => 'Análises',
            'window' => $window,
            'availableWindows' => [7, 14, 30, 90],
            'kpis' => [
                [
                    'label' => 'Visitas no período',
                    'value' => $totalVisits,
                    'icon' => 'bi-activity',
                    'tone' => 'gold',
                ],
                [
                    'label' => 'Sessões únicas',
                    'value' => $uniqueSessions,
                    'icon' => 'bi-fingerprint',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Leads captados',
                    'value' => $leadCount,
                    'icon' => 'bi-envelope-paper',
                    'tone' => 'green',
                ],
                [
                    'label' => 'Conversão',
                    'value' => $conversionRate,
                    'suffix' => '%',
                    'icon' => 'bi-graph-up-arrow',
                    'tone' => 'purple',
                ],
            ],
            'visitsByDay' => $visitsByDay,
            'devices' => $this->groupByColumn(clone $visitsWindow, 'device_type', 'Não identificado'),
            'browsers' => $this->groupByColumn(clone $visitsWindow, 'browser', 'Desconhecido'),
            'platforms' => $this->groupByColumn(clone $visitsWindow, 'platform', 'Desconhecida'),
            'leadStats' => (clone $leadsWindow)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($row): array => [
                    'label' => str((string) ($row->status ?: 'novo'))->replace('_', ' ')->headline()->toString(),
                    'total' => (int) $row->total,
                ]),
            'visitsByPage' => (clone $visitsWindow)
                ->selectRaw('path, COUNT(*) as total')
                ->groupBy('path')
                ->orderByDesc('total')
                ->paginate(12)
                ->withQueryString(),
            'topReferrers' => (clone $visitsWindow)
                ->selectRaw('referrer, COUNT(*) as total')
                ->whereNotNull('referrer')
                ->where('referrer', '!=', '')
                ->groupBy('referrer')
                ->orderByDesc('total')
                ->limit(8)
                ->get()
                ->map(function ($row): array {
                    $host = parse_url((string) $row->referrer, PHP_URL_HOST);

                    return [
                        'label' => $host ?: (string) $row->referrer,
                        'total' => (int) $row->total,
                    ];
                }),
            'latestVisits' => (clone $visitsWindow)
                ->latest('visited_at')
                ->limit(15)
                ->get(),
        ]);
    }

    private function groupByColumn($query, string $column, string $fallbackLabel): Collection
    {
        return $query
            ->selectRaw("{$column}, COUNT(*) as total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) ($row->{$column} ?: $fallbackLabel),
                'total' => (int) $row->total,
            ]);
    }
}
