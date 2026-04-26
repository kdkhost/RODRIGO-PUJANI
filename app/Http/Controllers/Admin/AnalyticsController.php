<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContactMessage;
use App\Models\PageVisit;
use Illuminate\View\View;

class AnalyticsController extends \App\Http\Controllers\Controller
{
    public function index(): View
    {
        $devices = PageVisit::query()
            ->selectRaw('device_type, COUNT(*) as total')
            ->groupBy('device_type')
            ->get()
            ->map(fn ($row): array => [
                'device_type' => $row->device_type ?: 'Nao identificado',
                'total' => (int) $row->total,
            ]);

        $leadStats = ContactMessage::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->map(fn ($row): array => [
                'status' => $row->status ?: 'novo',
                'total' => (int) $row->total,
            ]);

        return view('admin.analytics.index', [
            'pageTitle' => 'Analytics',
            'visitsByPage' => PageVisit::query()
                ->selectRaw('path, COUNT(*) as total')
                ->groupBy('path')
                ->orderByDesc('total')
                ->paginate(15),
            'devices' => $devices,
            'latestVisits' => PageVisit::query()->latest('visited_at')->limit(15)->get(),
            'leadStats' => $leadStats,
        ]);
    }
}
