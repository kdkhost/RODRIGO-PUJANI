@extends('admin.layouts.app')

@section('content')
    <div class="app-content-header admin-page-hero">
        <div class="container-fluid">
            <div class="admin-page-hero-inner">
                <div>
                    <div class="admin-eyebrow">Centro de conhecimento</div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>Guias integrados ao sistema, organizados conforme o perfil de acesso e o fluxo real do painel.</p>
                </div>
                <div class="admin-hero-stamp">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    <div><strong>Documentação oficial</strong><small>Versão 2.4.0</small></div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-xl-3">
                    <div class="card admin-table-card admin-docs-navigation">
                        <div class="card-header">
                            <div><div class="admin-card-kicker">Nesta página</div><h2 class="card-title">Navegação</h2></div>
                        </div>
                        <div class="card-body">
                            <nav class="nav nav-pills flex-column gap-2" aria-label="Seções da documentação">
                                <a class="nav-link" href="#geral"><i class="bi bi-grid-1x2 me-2"></i>Guia geral</a>
                                @if($isSuperAdmin || $isAdministrator)
                                    <a class="nav-link" href="#marca"><i class="bi bi-brush me-2"></i>Identidade visual</a>
                                    <a class="nav-link" href="#pwa"><i class="bi bi-phone me-2"></i>Aplicativo PWA</a>
                                    <a class="nav-link" href="#seguranca"><i class="bi bi-shield-lock me-2"></i>Segurança</a>
                                @endif
                                @if($isSuperAdmin || $isAdministrator || $isLawyer)
                                    <a class="nav-link" href="#processos"><i class="bi bi-briefcase me-2"></i>Processos</a>
                                    <a class="nav-link" href="#agenda"><i class="bi bi-calendar3 me-2"></i>Agenda e prazos</a>
                                @endif
                                <a class="nav-link" href="#changelog"><i class="bi bi-clock-history me-2"></i>Histórico de versões</a>
                            </nav>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-primary w-100" data-start-tour><i class="bi bi-signpost-split-fill me-1"></i>Iniciar tour guiado</button>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-9">
                    <div class="d-grid gap-4">
                        <section id="geral" class="card admin-table-card admin-docs-section">
                            <div class="card-header">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="admin-system-preview-mark"><i class="bi bi-grid-1x2-fill"></i></span>
                                    <div><div class="admin-card-kicker">Primeiros passos</div><h2 class="card-title">Guia geral do sistema</h2></div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6"><div class="admin-premium-surface h-100 p-3"><h3 class="h6 mb-2">Interface do painel</h3><p class="text-muted mb-0">Use o menu lateral para acessar os módulos disponíveis para seu perfil. O tema claro ou escuro pode ser alternado no cabeçalho.</p></div></div>
                                    <div class="col-12 col-md-6"><div class="admin-premium-surface h-100 p-3"><h3 class="h6 mb-2">Seu perfil</h3><p class="text-muted mb-0">Mantenha telefone, documentos e dados profissionais atualizados para que notificações e documentos sejam gerados corretamente.</p></div></div>
                                </div>
                            </div>
                        </section>

                        @if($isSuperAdmin || $isAdministrator)
                            <section id="marca" class="card admin-table-card admin-docs-section">
                                <div class="card-header"><div><div class="admin-card-kicker">Configuração administrativa</div><h2 class="card-title">Identidade visual e branding</h2></div></div>
                                <div class="card-body">
                                    <ol class="admin-docs-steps mb-0">
                                        <li>Acesse <strong>Operação &gt; Sistema &gt; Identidade visual</strong>.</li>
                                        <li>Selecione a nova logo ou favicon no campo de upload.</li>
                                        <li>Salve a configuração. A imagem será atualizada automaticamente no painel, login, cabeçalho e rodapé do site.</li>
                                    </ol>
                                </div>
                            </section>

                            <section id="pwa" class="card admin-table-card admin-docs-section">
                                <div class="card-header"><div><div class="admin-card-kicker">Experiência instalada</div><h2 class="card-title">Aplicativo PWA</h2></div></div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">Configure nome, descrição, cores, ícones, instalação e comportamento offline em <strong>Operação &gt; Sistema &gt; PWA</strong>.</p>
                                    <div class="alert alert-info mb-0"><i class="bi bi-info-circle me-2"></i>Após trocar ícones ou cores, salve e reabra o aplicativo instalado para carregar a nova versão.</div>
                                </div>
                            </section>

                            <section id="seguranca" class="card admin-table-card admin-docs-section">
                                <div class="card-header"><div><div class="admin-card-kicker">Proteção operacional</div><h2 class="card-title">Segurança e registros</h2></div></div>
                                <div class="card-body"><p class="text-muted mb-0">Permissões são modulares. Conceda somente os acessos necessários e consulte os registros de segurança antes de alterar regras, usuários protegidos ou arquivos do sistema.</p></div>
                            </section>
                        @endif

                        @if($isSuperAdmin || $isAdministrator || $isLawyer)
                            <section id="processos" class="card admin-table-card admin-docs-section">
                                <div class="card-header"><div><div class="admin-card-kicker">Operação jurídica</div><h2 class="card-title">Gestão de processos</h2></div></div>
                                <div class="card-body"><p class="text-muted mb-0">Cadastre cliente, número processual e responsáveis antes de registrar andamentos. Publicações importadas permanecem em revisão e somente são exibidas ao cliente após aprovação humana.</p></div>
                            </section>

                            <section id="agenda" class="card admin-table-card admin-docs-section">
                                <div class="card-header"><div><div class="admin-card-kicker">Organização diária</div><h2 class="card-title">Agenda e prazos</h2></div></div>
                                <div class="card-body"><p class="text-muted mb-0">Use a agenda para compromissos, audiências e prazos. Eventos podem ser movimentados por arrastar e soltar; confirme sempre data, hora e responsável antes de salvar.</p></div>
                            </section>
                        @endif

                        <section id="changelog" class="card admin-table-card admin-docs-section">
                            <div class="card-header">
                                <div><div class="admin-card-kicker">Evolução do sistema</div><h2 class="card-title">Histórico de versões</h2></div>
                                <span class="badge text-bg-primary">Atual</span>
                            </div>
                            <div class="card-body">
                                <div class="admin-docs-release">
                                    <div class="admin-docs-release-marker"></div>
                                    <div>
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2"><strong>Versão 2.4.0</strong><span class="text-muted small">26/08/2026</span></div>
                                        <ul class="text-muted mb-0 ps-3">
                                            <li>Logo e favicon dinâmicos com atualização automática e controle de cache.</li>
                                            <li>Padronização de cartões, espaçamentos e alinhamentos do painel.</li>
                                            <li>Documentação incorporada ao layout administrativo.</li>
                                            <li>Central jurídica com produtividade, assinatura eletrônica e fluxos de revisão.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
