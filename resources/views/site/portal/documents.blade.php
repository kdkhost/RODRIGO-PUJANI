@extends('site.portal.layout')

@php
    $pageTitle = 'Documentos';
    $formatBytes = static function (?int $bytes): string {
        if (! $bytes) {
            return 'Tamanho não informado';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = max(0, $bytes);
        $index = 0;

        while ($size >= 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        return number_format($size, $index === 0 ? 0 : 1, ',', '.').' '.$units[$index];
    };
@endphp

@section('portal_full_width', true)

@section('content')
    <div class="portal-dashboard-header">
        <div>
            <span>Biblioteca do cliente</span>
            <h2>Documentos compartilhados</h2>
            <p>Acesse os arquivos liberados pelo escritório para seus processos e acompanhamentos.</p>
        </div>
        <a href="{{ route('portal.dashboard') }}" class="portal-secondary-button">Voltar ao painel</a>
    </div>

    <div class="portal-stats-grid">
        <div class="portal-stat-card">
            <span>Documentos liberados</span>
            <strong>{{ number_format($documentStats['total'], 0, ',', '.') }}</strong>
        </div>
        <div class="portal-stat-card">
            <span>Categorias</span>
            <strong>{{ number_format($documentStats['categories'], 0, ',', '.') }}</strong>
        </div>
        <div class="portal-stat-card">
            <span>Processos vinculados</span>
            <strong>{{ number_format($documentStats['cases'], 0, ',', '.') }}</strong>
        </div>
        <div class="portal-stat-card">
            <span>Último envio</span>
            <strong>{{ $documentStats['latest']?->format('d/m') ?: '--' }}</strong>
        </div>
    </div>

    <div class="portal-dashboard-grid">
        <section class="portal-section">
            <div class="portal-section-heading">
                <h3>Arquivos disponíveis</h3>
            </div>

            <div class="portal-document-list portal-document-list-detailed">
                @forelse($documents as $document)
                    <article class="portal-document-card">
                        <div class="portal-document-icon">
                            <i class="bi bi-file-earmark-text"></i>
                            <small>{{ strtoupper($document->extension ?: 'doc') }}</small>
                        </div>
                        <div class="portal-document-content">
                            <strong>{{ $document->title }}</strong>
                            <span>
                                {{ $document->created_at?->format('d/m/Y H:i') ?: 'Data não informada' }}
                                · {{ $document->category ?: 'Documento' }}
                                · {{ $formatBytes($document->size) }}
                            </span>
                            @if($document->legalCase)
                                <em>{{ $document->legalCase->title }}{{ $document->legalCase->process_number ? ' · '.$document->legalCase->process_number : '' }}</em>
                            @endif
                        </div>
                        <a href="{{ route('portal.documents.download', $document->id) }}" class="portal-link-button">Baixar</a>
                    </article>
                @empty
                    <div class="portal-empty-state">
                        <strong>Nenhum documento liberado.</strong>
                        <span>Quando o escritório compartilhar arquivos com você, eles aparecerão nesta biblioteca.</span>
                    </div>
                @endforelse
            </div>
        </section>

        <aside class="portal-section">
            <div class="portal-section-heading">
                <h3>Categorias</h3>
            </div>

            <div class="portal-category-list">
                @forelse($documentsByCategory as $category)
                    <div>
                        <span>{{ $category['label'] }}</span>
                        <strong>{{ number_format($category['total'], 0, ',', '.') }}</strong>
                        <small>{{ $category['latest']?->format('d/m/Y') ?: 'Sem data' }}</small>
                    </div>
                @empty
                    <div class="portal-empty-state portal-empty-state-compact">
                        <span>Nenhuma categoria disponível.</span>
                    </div>
                @endforelse
            </div>
        </aside>
    </div>
@endsection
