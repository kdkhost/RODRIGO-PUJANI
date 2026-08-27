@extends('site.portal.layout')
@php($pageTitle = 'Assinaturas')
@section('portal_full_width', true)
@section('content')
<div class="portal-dashboard-header"><div><span>Documentos formais</span><h2>Assinaturas eletrônicas</h2><p>Acompanhe solicitações, prazos e comprovantes vinculados ao seu cadastro.</p></div></div>
<div class="portal-document-list portal-document-list-detailed">@forelse($signatureRequests as $item)<article class="portal-document-card"><div class="portal-document-icon"><i class="bi bi-pen"></i></div><div class="portal-document-content"><strong>{{ $item->title }}</strong><span>Status: {{ strtoupper($item->status) }} · prazo {{ $item->expires_at?->format('d/m/Y H:i') }}</span><em>{{ $item->signers->where('status','signed')->count() }}/{{ $item->signers->count() }} assinatura(s)</em></div>@if($item->status === 'completed')<div class="d-flex flex-wrap gap-2"><a href="{{ route('portal.signatures.document',$item) }}" class="portal-link-button"><i class="bi bi-file-earmark-check me-1"></i>Documento assinado</a><a href="{{ route('portal.signatures.evidence',$item) }}" class="portal-link-button"><i class="bi bi-shield-check me-1"></i>Comprovante</a></div>@endif</article>@empty<div class="portal-empty-state"><strong>Nenhuma assinatura disponível.</strong></div>@endforelse</div>{{ $signatureRequests->links() }}
@endsection
