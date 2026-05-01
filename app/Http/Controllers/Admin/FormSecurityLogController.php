<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormSecurityLog;
use Illuminate\Contracts\View\View;
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

        return view('admin.form-security-logs.index', [
            'logs' => $query->paginate(30)->withQueryString(),
        ]);
    }
}

