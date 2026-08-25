@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero"><div class="container-fluid"><div class="admin-page-hero-inner">
        <div><div class="admin-eyebrow">Visão integrada do cliente</div><h1>{{ $record->name }}</h1><p>{{ $record->document_number ?: 'Documento não informado' }} · Responsável: {{ $record->assignedLawyer?->name ?: 'Não definido' }}</p></div>
        @if($moduleAccess['manage_clients'])<button class="btn btn-primary" data-modal-url="{{ route('admin.clients.edit', $record) }}">Editar cliente</button>@endif
    </div></div></div>

    <div class="app-content"><div class="container-fluid"><div class="row g-4">
        <div class="col-xl-7">
            <div class="card admin-premium-card mb-4"><div class="card-header"><strong>Processos</strong></div><div class="list-group list-group-flush">
                @forelse($cases as $case)<a class="list-group-item list-group-item-action d-flex justify-content-between" href="{{ route('admin.legal-cases.workspace', $case) }}"><span><strong>{{ $case->title }}</strong><small class="d-block text-muted">{{ $case->process_number ?: 'Sem número CNJ' }}</small></span><span class="badge badge-soft-info align-self-center">{{ str($case->status)->replace('_', ' ')->headline() }}</span></a>@empty<div class="list-group-item text-muted">Nenhum processo.</div>@endforelse
            </div></div>
            @if($moduleAccess['tasks'])
            <div class="card admin-premium-card"><div class="card-header"><strong>Próximos prazos</strong></div><div class="table-responsive"><table class="table mb-0"><tbody>
                @forelse($tasks as $item)<tr><td><strong>{{ $item->title }}</strong></td><td>{{ $item->due_at?->format('d/m/Y H:i') ?: 'Sem data' }}</td></tr>@empty<tr><td class="text-muted">Nenhum prazo.</td></tr>@endforelse
            </tbody></table></div></div>
            @endif
        </div>
        <div class="col-xl-5">
            @if($moduleAccess['documents'] || $moduleAccess['calendar'] || $moduleAccess['financial'] || $moduleAccess['transcriptions'])
            <div class="card admin-premium-card mb-4"><div class="card-header"><strong>Acessos rápidos</strong></div><div class="list-group list-group-flush">
                @if($moduleAccess['documents'])<a class="list-group-item list-group-item-action" href="{{ route('admin.legal-documents.index', ['search' => $record->name]) }}">Documentos autorizados <span class="float-end badge badge-soft-info">{{ $documents->count() }}</span></a>@endif
                @if($moduleAccess['calendar'])<a class="list-group-item list-group-item-action" href="{{ route('admin.calendar.index') }}">Agenda <span class="float-end badge badge-soft-info">{{ $events->count() }}</span></a>@endif
                @if($moduleAccess['financial'])<a class="list-group-item list-group-item-action" href="{{ route('admin.financial-entries.index', ['client_id' => $record->id]) }}">Financeiro <span class="float-end badge badge-soft-info">{{ $financialEntries->count() }}</span></a>@endif
                @if($moduleAccess['transcriptions'])<a class="list-group-item list-group-item-action" href="{{ route('admin.hearing-transcriptions.index', ['search' => $record->name]) }}">Transcrições <span class="float-end badge badge-soft-info">{{ $transcriptions->count() }}</span></a>@endif
            </div></div>
            @endif
            @if($moduleAccess['financial'])
            <div class="card admin-premium-card"><div class="card-header"><strong>Situação financeira</strong></div><div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Receitas</span><strong>R$ {{ number_format((float) $financialTotals['income'], 2, ',', '.') }}</strong></div>
                <div class="d-flex justify-content-between"><span>Despesas</span><strong>R$ {{ number_format((float) $financialTotals['expense'], 2, ',', '.') }}</strong></div>
            </div></div>
            @endif
        </div>
    </div></div></div>
@endsection
