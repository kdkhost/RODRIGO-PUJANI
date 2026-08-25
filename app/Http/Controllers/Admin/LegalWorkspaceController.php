<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\DjenMonitor;
use App\Models\DjenPublication;
use App\Models\FinancialEntry;
use App\Models\HearingTranscription;
use App\Models\LegalCase;
use App\Models\LegalCaseUpdate;
use App\Models\LegalDocument;
use App\Models\LegalTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalWorkspaceController extends Controller
{
    public function legalCase(string $legalCase, Request $request): View
    {
        $user = $request->user();
        $moduleAccess = $this->moduleAccess($user);
        $record = LegalCase::query()
            ->visibleTo($user)
            ->with(['client:id,name,email,phone,whatsapp', 'primaryLawyer:id,name', 'supervisingLawyer:id,name'])
            ->findOrFail($legalCase);

        $updates = $moduleAccess['updates']
            ? LegalCaseUpdate::query()->visibleTo($user)->where('legal_case_id', $record->id)->latest('occurred_at')->limit(10)->get()
            : collect();
        $publications = $moduleAccess['djen']
            ? DjenPublication::query()->visibleTo($user)->where('legal_case_id', $record->id)->latest('availability_date')->limit(10)->get()
            : collect();
        $tasks = $moduleAccess['tasks']
            ? LegalTask::query()->visibleTo($user)->where('legal_case_id', $record->id)->orderBy('due_at')->limit(10)->get()
            : collect();
        $events = $moduleAccess['calendar']
            ? CalendarEvent::query()->visibleTo($user)->where('legal_case_id', $record->id)->orderBy('start_at')->limit(10)->get()
            : collect();
        $documents = $moduleAccess['documents']
            ? LegalDocument::query()->visibleTo($user)->where('legal_case_id', $record->id)->latest()->limit(10)->get()
            : collect();
        $transcriptions = $moduleAccess['transcriptions']
            ? HearingTranscription::query()->visibleTo($user)->where('legal_case_id', $record->id)->latest()->limit(10)->get()
            : collect();
        $financialEntries = $moduleAccess['financial']
            ? FinancialEntry::query()->visibleTo($user)->where('legal_case_id', $record->id)->orderBy('due_date')->limit(10)->get()
            : collect();
        $djenMonitors = $moduleAccess['djen']
            ? DjenMonitor::query()->visibleTo($user)->where('legal_case_id', $record->id)->latest()->get()
            : collect();

        return view('admin.legal-workspace.case', [
            'pageTitle' => $record->title,
            'record' => $record,
            'moduleAccess' => $moduleAccess,
            'updates' => $updates,
            'publications' => $publications,
            'tasks' => $tasks,
            'events' => $events,
            'documents' => $documents,
            'transcriptions' => $transcriptions,
            'financialEntries' => $financialEntries,
            'djenMonitors' => $djenMonitors,
        ]);
    }

    public function client(string $client, Request $request): View
    {
        $user = $request->user();
        $moduleAccess = $this->moduleAccess($user);
        $record = Client::query()
            ->visibleTo($user)
            ->with(['assignedLawyer:id,name'])
            ->findOrFail($client);

        $caseIds = LegalCase::query()->visibleTo($user)->where('client_id', $record->id)->pluck('id');
        $financialBase = FinancialEntry::query()->visibleTo($user)->where('client_id', $record->id)->where('status', '!=', 'canceled');
        $financialEntries = $moduleAccess['financial']
            ? (clone $financialBase)->orderBy('due_date')->limit(10)->get()
            : collect();

        return view('admin.legal-workspace.client', [
            'pageTitle' => $record->name,
            'record' => $record,
            'moduleAccess' => $moduleAccess,
            'cases' => LegalCase::query()->visibleTo($user)->whereIn('id', $caseIds)->latest()->get(),
            'tasks' => $moduleAccess['tasks'] ? LegalTask::query()->visibleTo($user)->where('client_id', $record->id)->orderBy('due_at')->limit(10)->get() : collect(),
            'events' => $moduleAccess['calendar'] ? CalendarEvent::query()->visibleTo($user)->where('client_id', $record->id)->orderBy('start_at')->limit(10)->get() : collect(),
            'documents' => $moduleAccess['documents'] ? LegalDocument::query()->visibleTo($user)->where('client_id', $record->id)->latest()->limit(10)->get() : collect(),
            'transcriptions' => $moduleAccess['transcriptions'] ? HearingTranscription::query()->visibleTo($user)->where('client_id', $record->id)->latest()->limit(10)->get() : collect(),
            'financialEntries' => $financialEntries,
            'financialTotals' => [
                'income' => $moduleAccess['financial'] ? (clone $financialBase)->where('entry_type', 'income')->sum('amount') : 0,
                'expense' => $moduleAccess['financial'] ? (clone $financialBase)->where('entry_type', 'expense')->sum('amount') : 0,
            ],
        ]);
    }

    /** @return array<string, bool> */
    private function moduleAccess(?User $user): array
    {
        return [
            'updates' => $user?->can('legal-case-updates.manage') === true,
            'djen' => $user?->can('djen-publications.view') === true,
            'tasks' => $user?->can('legal-tasks.manage') === true,
            'calendar' => $user?->can('calendar.manage') === true,
            'documents' => $user?->can('legal-documents.manage') === true,
            'transcriptions' => $user?->can('hearing-transcriptions.manage') === true,
            'financial' => $user?->can('financial.manage') === true,
            'manage_cases' => $user?->can('legal-cases.manage') === true,
            'manage_clients' => $user?->can('clients.manage') === true,
        ];
    }
}
