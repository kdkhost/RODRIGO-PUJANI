<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentationController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        
        return view('admin.documentation.index', [
            'pageTitle' => 'Centro de Ajuda e Documentação',
            'isSuperAdmin' => $user?->isSuperAdmin(),
            'isAdministrator' => $user?->isAdministrator(),
            'isLawyer' => $user?->isAssociatedLawyer(),
        ]);
    }

    public function completeTour(Request $request)
    {
        $user = auth()->user();
        
        if ($user) {
            $user->update(['tour_completed_at' => now()]);
        }

        return response()->json(['message' => 'Tour marcado como concluído.']);
    }


    public function resetTour(Request $request)
    {
        $user = auth()->user();

        if ($user) {
            $user->update(['tour_completed_at' => null]);
        }

        return response()->json(['message' => 'Tour reativado com sucesso.']);
    }
}
