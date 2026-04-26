<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\PageVisit;
use App\Models\PracticeArea;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\View\View;

class DashboardController extends \App\Http\Controllers\Controller
{
    public function index(): View
    {
        $rawVisitsByDay = PageVisit::query()
            ->selectRaw('DATE(visited_at) as day, COUNT(*) as total')
            ->where('visited_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->pluck('total', 'day');

        $visitsByDay = collect(range(6, 0))->map(function (int $daysAgo) use ($rawVisitsByDay): array {
            $date = now()->subDays($daysAgo);

            return [
                'day' => $date->format('d/m'),
                'total' => (int) ($rawVisitsByDay[$date->toDateString()] ?? 0),
            ];
        });

        return view('admin.dashboard', [
            'pageTitle' => 'Painel Administrativo',
            'stats' => [
                'pages' => Page::query()->count(),
                'areas' => PracticeArea::query()->count(),
                'team' => TeamMember::query()->count(),
                'testimonials' => Testimonial::query()->count(),
                'contacts' => ContactMessage::query()->count(),
                'visits' => PageVisit::query()->count(),
            ],
            'latestContacts' => ContactMessage::query()->latest()->limit(5)->get(),
            'visitsByDay' => $visitsByDay,
        ]);
    }
}
