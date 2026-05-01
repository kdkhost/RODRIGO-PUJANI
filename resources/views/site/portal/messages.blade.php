@extends('site.portal.layout')

@php
    $pageTitle = 'Mensagens';
@endphp

@section('portal_full_width', true)

@section('content')
    <div class="portal-dashboard-header">
        <div>
            <span>Comunicacao</span>
            <h2>Mensagens do portal</h2>
            <p>Canal interno para tratar assuntos sobre seu processo com o escritorio.</p>
        </div>
    </div>

    @if($internalEnabled)
        <section class="portal-section">
            <div class="portal-section-heading">
                <h3>Nova mensagem</h3>
            </div>
            <form action="{{ route('portal.messages.store') }}" method="POST" class="portal-profile-form">
                @csrf
                <div class="portal-profile-grid">
                    <div class="portal-field">
                        <label for="legal_case_id">Processo (opcional)</label>
                        <select id="legal_case_id" name="legal_case_id" class="portal-input">
                            <option value="">Selecionar processo</option>
                            @foreach($cases as $legalCase)
                                <option value="{{ $legalCase->id }}">{{ $legalCase->title }}{{ $legalCase->process_number ? ' - '.$legalCase->process_number : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="portal-field portal-field-wide">
                        <label for="subject">Assunto</label>
                        <input id="subject" type="text" name="subject" class="portal-input" maxlength="160" placeholder="Resumo do assunto">
                    </div>
                    <div class="portal-field portal-field-wide">
                        <label for="message">Mensagem</label>
                        <textarea
                            id="message"
                            name="message"
                            class="portal-input"
                            rows="5"
                            required
                            placeholder="Escreva sua mensagem para o escritorio"
                            data-editor="summernote"
                            data-editor-height="220"
                        ></textarea>
                    </div>
                </div>
                <div class="portal-profile-actions">
                    <button type="submit" class="portal-button">Enviar mensagem</button>
                </div>
            </form>
        </section>
    @else
        <div class="portal-status portal-status-info">Canal interno desativado para este cadastro no momento.</div>
    @endif

    <section class="portal-section portal-section-spaced">
        <div class="portal-section-heading">
            <h3>Historico</h3>
        </div>
        <div class="portal-timeline">
            @forelse($messages as $message)
                <article class="portal-timeline-item">
                    <div class="portal-timeline-dot"></div>
                    <div>
                        <div class="portal-timeline-header">
                            <strong>{{ $message->subject ?: ($message->sender_type === 'client' ? 'Mensagem enviada' : 'Mensagem do escritorio') }}</strong>
                            <small>{{ $message->created_at?->format('d/m/Y H:i') }}</small>
                        </div>
                        <span>
                            @if($message->sender_type === 'client')
                                Enviado por voce
                            @else
                                {{ $message->senderUser?->name ?: 'Equipe do escritorio' }}
                            @endif
                            @if($message->legalCase?->title)
                                • {{ $message->legalCase->title }}
                            @endif
                        </span>
                        <div class="portal-rich-text portal-rich-text-sm">
                            {!! $message->message !!}
                        </div>
                    </div>
                </article>
            @empty
                <div class="portal-empty-state portal-empty-state-compact">
                    <span>Nenhuma mensagem registrada.</span>
                </div>
            @endforelse
        </div>
    </section>
@endsection
