<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContactMessage;
use App\Models\PageVisit;
use Illuminate\View\View;

class AnalyticsController extends \App\Http\Controllers\Controller
{
    public function index(): View
    {
        return view('admin.analytics.index', [
            'pageTitle' => 'Analytics',
            'visitsByPage' => PageVisit::query()
                ->selectRaw('path, COUNT(*) as total')
                ->groupBy('path')
                ->orderByDesc('total')
                ->paginate(15),
            'devices' => PageVisit::query()
                ->selectRaw('device_type, COUNT(*) as total')
                ->groupBy('device_type')
                ->get(),
            'latestVisits' => PageVisit::query()->latest('visited_at')->limit(15)->get(),
            'leadStats' => ContactMessage::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->get(),
        ]);
    }
}
