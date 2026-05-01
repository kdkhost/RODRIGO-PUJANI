<div class="card admin-table-card">
    <div class="card-header">
        <div>
            <div class="admin-card-kicker">Suporte e atendimento</div>
            <h3 class="card-title">WhatsApp multinivel</h3>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 admin-premium-form">
            <div class="col-md-4 form-check ps-5 pt-4">
                <input type="checkbox" class="form-check-input" id="whatsapp_multiple_support" name="whatsapp_multiple_support" value="1" @checked(old('whatsapp_multiple_support', setting('site.whatsapp_multiple_support') == '1'))>
                <label class="form-check-label" for="whatsapp_multiple_support">Ativar selecao de especialistas</label>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="whatsapp_selection_title">Titulo da caixa</label>
                <input id="whatsapp_selection_title" type="text" name="whatsapp_selection_title" class="form-control" value="{{ old('whatsapp_selection_title', setting('site.whatsapp_selection_title', 'Escolha um especialista')) }}" placeholder="Ex: Fale com nossa equipe">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="whatsapp_selection_subtitle">Subtitulo da caixa</label>
                <input id="whatsapp_selection_subtitle" type="text" name="whatsapp_selection_subtitle" class="form-control" value="{{ old('whatsapp_selection_subtitle', setting('site.whatsapp_selection_subtitle', 'Selecione com quem deseja falar pelo WhatsApp:')) }}" placeholder="Ex: Clique no advogado desejado">
            </div>
            <div class="col-12">
                <div class="alert alert-info border-0 bg-opacity-10 mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Quando ativo, o botao do WhatsApp no site exibira uma caixa listando todos os <strong>membros da equipe ativos</strong> com WhatsApp cadastrado.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card admin-table-card">
    <div class="card-header">
        <div>
            <div class="admin-card-kicker">Base de demonstracao</div>
            <h3 class="card-title">Popular o sistema com dados de exemplo</h3>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="admin-premium-surface p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-8">
                    <p class="text-muted mb-3">Cria ou atualiza clientes, processos, tarefas, documentos, agenda e usuarios para exibir o sistema ja montado.</p>
                    <div class="row g-3">
                        <div class="col-md-3"><div class="admin-brand-preview-card"><span>Usuarios</span><strong class="mt-2">{{ number_format($stats['users'], 0, ',', '.') }}</strong></div></div>
                        <div class="col-md-3"><div class="admin-brand-preview-card"><span>Clientes</span><strong class="mt-2">{{ number_format($stats['clients'], 0, ',', '.') }}</strong></div></div>
                        <div class="col-md-3"><div class="admin-brand-preview-card"><span>Processos</span><strong class="mt-2">{{ number_format($stats['cases'], 0, ',', '.') }}</strong></div></div>
                        <div class="col-md-3"><div class="admin-brand-preview-card"><span>Agenda</span><strong class="mt-2">{{ number_format($stats['calendar_events'], 0, ',', '.') }}</strong></div></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <form action="{{ route('admin.system-settings.seed-demo-data') }}" method="POST" data-ajax-form class="d-grid gap-3">
                        @csrf
                        <button
                            type="submit"
                            class="btn btn-outline-primary"
                            data-confirm-submit="true"
                            data-confirm-title="Popular base de demonstracao?"
                            data-confirm-text="Os registros de exemplo serao criados ou atualizados para apresentar o escritorio com dados preenchidos."
                            data-confirm-button="Popular agora"
                        >
                            <i class="bi bi-stars me-1"></i>Popular dados de exemplo
                        </button>
                        <div class="text-muted small">
                            Credenciais demo:
                            <code>gestor.demo@pujani.adv.br</code>
                            e
                            <code>associado.demo@pujani.adv.br</code>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
