@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid"><div class="admin-page-hero-inner">
            <div><div class="admin-eyebrow">Dossiê jurídico integrado</div><h1>{{ $record->title }}</h1><p>{{ $record->process_number ?: 'Sem número CNJ' }} · {{ $record->client->name }}</p></div>
            <div class="d-flex flex-wrap gap-2">
                @if($moduleAccess['manage_cases'])
                    <button class="btn btn-outline-primary" data-modal-url="{{ route('admin.legal-cases.edit', $record) }}">Editar processo</button>
                @endif
                <a class="btn btn-primary" href="{{ route('admin.clients.workspace', $record->client) }}">Abrir cliente</a>
            </div>
        </div></div>
    </div>

    <div class="app-content"><div class="container-fluid">
        <div class="row g-3 mb-4">
            @php
                $workspaceCards = [];
                if ($moduleAccess['updates']) $workspaceCards[] = ['Andamentos', $updates->count(), route('admin.legal-case-updates.index', ['search' => $record->process_number])];
                if ($moduleAccess['djen']) $workspaceCards[] = ['Publicações DJEN', $publications->count(), route('admin.djen-publications.index', ['legal_case_id' => $record->id])];
                if ($moduleAccess['tasks']) $workspaceCards[] = ['Prazos', $tasks->count(), route('admin.legal-tasks.index', ['legal_case_id' => $record->id])];
                if ($moduleAccess['documents']) $workspaceCards[] = ['Documentos', $documents->count(), route('admin.legal-documents.index', ['search' => $record->title])];
                if ($moduleAccess['transcriptions']) $workspaceCards[] = ['Audiências', $transcriptions->count(), route('admin.hearing-transcriptions.index', ['search' => $record->title])];
                if ($moduleAccess['financial']) $workspaceCards[] = ['Financeiro', $financialEntries->count(), route('admin.financial-entries.index', ['legal_case_id' => $record->id])];
            @endphp
            @foreach($workspaceCards as [$label, $count, $url])
                <div class="col-6 col-lg-2"><a href="{{ $url }}" class="card admin-premium-card h-100 text-decoration-none"><div class="card-body"><small class="text-muted">{{ $label }}</small><div class="fs-4 fw-semibold mt-1">{{ $count }}</div></div></a></div>
            @endforeach
        </div>

        <div class="row g-4">
            <div class="col-xl-7">
                @if($moduleAccess['djen'] || $moduleAccess['updates'])
                <div class="card admin-premium-card mb-4"><div class="card-header d-flex justify-content-between"><strong>Publicações e movimentações</strong><a href="{{ route('admin.djen-publications.index', ['legal_case_id' => $record->id]) }}">Central DJEN</a></div><div class="list-group list-group-flush">
                    @if($moduleAccess['djen']) @forelse($publications as $item)<div class="list-group-item"><div class="d-flex justify-content-between"><strong>{{ $item->communication_type ?: 'Comunicação DJEN' }}</strong><span class="badge badge-soft-info">{{ str($item->review_status)->headline() }}</span></div><small class="text-muted">{{ $item->availability_date?->format('d/m/Y') }} · {{ $item->tribunal }}</small></div>@empty<div class="list-group-item text-muted">Nenhuma publicação DJEN vinculada.</div>@endforelse @endif
                    @if($moduleAccess['updates']) @foreach($updates as $item)<div class="list-group-item"><strong>{{ $item->title }}</strong><div><small class="text-muted">{{ $item->occurred_at?->format('d/m/Y H:i') }} · {{ strtoupper($item->source) }}</small></div></div>@endforeach @endif
                </div></div>
                @endif

                @if($moduleAccess['tasks'] || $moduleAccess['calendar'])
                <div class="card admin-premium-card"><div class="card-header d-flex justify-content-between"><strong>Prazos e agenda</strong><a href="{{ route('admin.calendar.index') }}">Abrir agenda</a></div><div class="table-responsive"><table class="table align-middle mb-0"><tbody>
                    @if($moduleAccess['tasks']) @foreach($tasks as $item)<tr><td><strong>{{ $item->title }}</strong><div><small class="text-muted">Prazo · {{ str($item->status)->replace('_', ' ')->headline() }}</small></div></td><td class="text-end">{{ $item->due_at?->format('d/m/Y H:i') ?: 'Sem data' }}</td></tr>@endforeach @endif
                    @if($moduleAccess['calendar']) @foreach($events as $item)<tr><td><strong>{{ $item->title }}</strong><div><small class="text-muted">Agenda · {{ str($item->event_type)->replace('_', ' ')->headline() }}</small></div></td><td class="text-end">{{ $item->start_at?->format('d/m/Y H:i') ?: 'Sem data' }}</td></tr>@endforeach @endif
                    @if($tasks->isEmpty() && $events->isEmpty())<tr><td class="text-muted">Nenhum prazo ou evento vinculado.</td></tr>@endif
                </tbody></table></div></div>
                @endif
            </div>

            <div class="col-xl-5">
                <div class="card admin-premium-card mb-4"><div class="card-header"><strong>Dados centrais</strong></div><div class="card-body">
                    <dl class="row mb-0"><dt class="col-5">Cliente</dt><dd class="col-7">{{ $record->client->name }}</dd><dt class="col-5">Responsável</dt><dd class="col-7">{{ $record->primaryLawyer?->name ?: 'Não definido' }}</dd><dt class="col-5">Tribunal</dt><dd class="col-7">{{ $record->court_name ?: 'Não informado' }}</dd><dt class="col-5">Vara</dt><dd class="col-7">{{ $record->court_division ?: 'Não informada' }}</dd><dt class="col-5">Status</dt><dd class="col-7">{{ str($record->status)->replace('_', ' ')->headline() }}</dd></dl>
                </div></div>

                @if($moduleAccess['documents'])
                <div class="card admin-premium-card mb-4"><div class="card-header d-flex justify-content-between"><strong>Documentos privados</strong><a href="{{ route('admin.legal-documents.index', ['search' => $record->title]) }}">Todos</a></div><div class="list-group list-group-flush">
                    @forelse($documents as $item)<a class="list-group-item list-group-item-action" href="{{ route('admin.legal-documents.download', $item) }}"><strong>{{ $item->title }}</strong><div><small class="text-muted">{{ $item->original_name }}</small></div></a>@empty<div class="list-group-item text-muted">Nenhum documento.</div>@endforelse
                </div></div>
                @endif

                @if($moduleAccess['financial'])
                <div class="card admin-premium-card"><div class="card-header d-flex justify-content-between"><strong>Financeiro</strong><a href="{{ route('admin.financial-entries.index', ['legal_case_id' => $record->id]) }}">Abrir</a></div><div class="list-group list-group-flush">
                    @forelse($financialEntries as $item)<div class="list-group-item d-flex justify-content-between"><span>{{ $item->description }}<small class="d-block text-muted">{{ $item->due_date?->format('d/m/Y') }}</small></span><strong>R$ {{ number_format((float) $item->amount, 2, ',', '.') }}</strong></div>@empty<div class="list-group-item text-muted">Nenhum lançamento.</div>@endforelse
                </div></div>
                @endif
            </div>
        </div>
    </div></div>
@endsection
