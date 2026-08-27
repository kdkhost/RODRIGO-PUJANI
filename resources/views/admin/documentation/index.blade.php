@extends('admin.layouts.app')

@section('content')
<div class="app-content-header admin-page-hero"><div class="container-fluid"><div class="admin-page-hero-inner">
    <div><div class="admin-eyebrow">Centro de conhecimento</div><h1>{{ $pageTitle }}</h1><p>Configuração e operação dos módulos, dentro do mesmo padrão visual do painel.</p></div>
    <div class="admin-hero-stamp"><i class="bi bi-journal-bookmark-fill"></i><div><strong>Documentação oficial</strong><small>Versão 2.5.0</small></div></div>
</div></div></div>

<div class="app-content"><div class="container-fluid"><div class="row g-4">
    <div class="col-12 col-xl-3"><div class="card admin-table-card admin-docs-navigation">
        <div class="card-header"><div><div class="admin-card-kicker">Nesta página</div><h2 class="card-title">Navegação</h2></div></div>
        <div class="card-body"><nav class="nav nav-pills flex-column gap-2" aria-label="Seções da documentação">
            <a class="nav-link" href="#geral"><i class="bi bi-grid-1x2 me-2"></i>Primeiros passos</a>
            @if($isSuperAdmin || $isAdministrator)
                <a class="nav-link" href="#marca"><i class="bi bi-brush me-2"></i>Marca e PWA</a>
                <a class="nav-link" href="#google-calendar"><i class="bi bi-google me-2"></i>Google Calendar</a>
                <a class="nav-link" href="#ia-juridica"><i class="bi bi-stars me-2"></i>IA jurídica</a>
                <a class="nav-link" href="#infraestrutura"><i class="bi bi-gear me-2"></i>Rotinas automáticas</a>
                <a class="nav-link" href="#seguranca"><i class="bi bi-shield-lock me-2"></i>Segurança e e-mail</a>
            @endif
            @if($isSuperAdmin || $isAdministrator || $isLawyer)
                <a class="nav-link" href="#processos"><i class="bi bi-briefcase me-2"></i>Processos e DJEN</a>
                <a class="nav-link" href="#agenda"><i class="bi bi-calendar3 me-2"></i>Agenda e prazos</a>
                <a class="nav-link" href="#documentos"><i class="bi bi-file-earmark-text me-2"></i>Documentos</a>
                <a class="nav-link" href="#assinaturas"><i class="bi bi-pen me-2"></i>Assinaturas</a>
                <a class="nav-link" href="#audiencias"><i class="bi bi-mic me-2"></i>Transcrições</a>
            @endif
            <a class="nav-link" href="#changelog"><i class="bi bi-clock-history me-2"></i>Histórico</a>
        </nav></div>
        <div class="card-footer"><button type="button" class="btn btn-primary w-100" data-start-tour><i class="bi bi-signpost-split-fill me-1"></i>Iniciar tour guiado</button></div>
    </div></div>

    <div class="col-12 col-xl-9"><div class="d-grid gap-4">
        <section id="geral" class="card admin-table-card admin-docs-section">
            <div class="card-header"><div><div class="admin-card-kicker">Primeiros passos</div><h2 class="card-title">Guia geral do sistema</h2></div></div>
            <div class="card-body"><div class="row g-3">
                <div class="col-12 col-md-6"><div class="admin-premium-surface h-100 p-3"><h3 class="h6 mb-2">Acesso por perfil</h3><p class="text-muted mb-0">Menus e ações aparecem conforme as permissões. Confirme o acesso em <strong>Segurança &gt; Perfis e permissões</strong>.</p></div></div>
                <div class="col-12 col-md-6"><div class="admin-premium-surface h-100 p-3"><h3 class="h6 mb-2">Dados consistentes</h3><p class="text-muted mb-0">Mantenha clientes, documentos, telefone, endereço, processo e responsáveis atualizados para alimentar agenda, documentos e notificações.</p></div></div>
            </div></div>
        </section>

        @if($isSuperAdmin || $isAdministrator)
        <section id="marca" class="card admin-table-card admin-docs-section">
            <div class="card-header"><div><div class="admin-card-kicker">Configuração administrativa</div><h2 class="card-title">Identidade visual e aplicativo PWA</h2></div></div>
            <div class="card-body admin-card-flow"><ol class="admin-docs-steps">
                <li>Acesse <strong>Operação &gt; Sistema &gt; Identidade visual</strong>, envie logo e favicon nos campos correspondentes e salve.</li>
                <li>A imagem permanece no upload e passa a ser usada dinamicamente no painel, login, cabeçalho e rodapé do site.</li>
                <li>Em <strong>Operação &gt; Sistema &gt; PWA</strong>, configure nome, descrição, cores, ícones, instalação e modo offline.</li>
                <li>Após trocar ícones, feche e reabra o aplicativo instalado para atualizar o cache visual.</li>
            </ol><div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Não renomeie nem mova manualmente arquivos enviados pelo painel.</div></div>
        </section>

        <section id="google-calendar" class="card admin-table-card admin-docs-section">
            <div class="card-header"><div><div class="admin-card-kicker">OAuth 2.0 oficial</div><h2 class="card-title">Como configurar o Google Calendar</h2></div></div>
            <div class="card-body admin-card-flow"><ol class="admin-docs-steps">
                <li>No Google Cloud Console, crie ou selecione um projeto e ative a <strong>Google Calendar API</strong>.</li>
                <li>Configure a tela de consentimento e crie uma credencial OAuth do tipo <strong>Aplicativo da Web</strong>.</li>
                <li>Cadastre como URI autorizada exatamente <code>{{ route('admin.google-calendar.callback') }}</code>.</li>
                <li>No <code>.env</code>, defina <code>GOOGLE_CALENDAR_ENABLED=true</code>, <code>GOOGLE_CALENDAR_CLIENT_ID</code>, <code>GOOGLE_CALENDAR_CLIENT_SECRET</code> e <code>GOOGLE_CALENDAR_REDIRECT_URI</code>.</li>
                <li>Execute <code>php artisan optimize:clear</code>. Em <strong>Jurídico &gt; Google Calendar</strong>, conecte a conta, selecione o calendário e ative a sincronização.</li>
            </ol><div class="alert alert-warning"><i class="bi bi-shield-exclamation me-2"></i>Nunca exponha o segredo OAuth. Os tokens da conta são criptografados no banco.</div></div>
        </section>

        <section id="ia-juridica" class="card admin-table-card admin-docs-section">
            <div class="card-header"><div><div class="admin-card-kicker">Provedor protegido</div><h2 class="card-title">IA jurídica e modelos</h2></div></div>
            <div class="card-body admin-card-flow"><ol class="admin-docs-steps">
                <li>Acesse <strong>Conteúdo &gt; IA jurídica</strong> e informe URL-base HTTPS, modelos de texto e transcrição e chave de uma API compatível com OpenAI.</li>
                <li>Ative e salve. A chave é criptografada e não volta a ser exibida.</li>
                <li>Se necessário, ajuste <code>LEGAL_AI_TIMEOUT_SECONDS</code> e <code>LEGAL_AI_MAX_SOURCE_CHARACTERS</code> no <code>.env</code>.</li>
                <li>Todo conteúdo gerado permanece em rascunho até revisão, aprovação e publicação humana.</li>
            </ol></div>
        </section>

        <section id="infraestrutura" class="card admin-table-card admin-docs-section">
            <div class="card-header"><div><div class="admin-card-kicker">Hospedagem compartilhada</div><h2 class="card-title">Fila, agenda e rotinas automáticas</h2></div></div>
            <div class="card-body admin-card-flow"><p class="text-muted">No cPanel, crie um cron a cada minuto, usando o PHP 8.4 e o caminho real do projeto:</p>
                <code class="d-block text-break p-3 rounded bg-body-tertiary">* * * * * /usr/local/bin/php /caminho/do/projeto/artisan schedule:run &gt;/dev/null 2&gt;&amp;1</code>
                <p class="text-muted">Essa rotina expira assinaturas, consulta DJEN, envia lembretes, sincroniza o Google Calendar e processa as filas.</p>
            </div>
        </section>

        <section id="seguranca" class="card admin-table-card admin-docs-section">
            <div class="card-header"><div><div class="admin-card-kicker">Proteção operacional</div><h2 class="card-title">Segurança, permissões e e-mail</h2></div></div>
            <div class="card-body admin-card-flow"><p class="text-muted">Conceda somente as permissões necessárias e consulte os registros antes de alterações sensíveis.</p><p class="text-muted">Configure o SMTP em <strong>Operação &gt; Sistema &gt; E-mail</strong> e use o teste do painel. Ele é necessário para convites de assinatura e notificações.</p></div>
        </section>
        @endif

        @if($isSuperAdmin || $isAdministrator || $isLawyer)
        <section id="processos" class="card admin-table-card admin-docs-section">
            <div class="card-header"><div><div class="admin-card-kicker">Operação jurídica</div><h2 class="card-title">Processos, área de trabalho e DJEN</h2></div></div>
            <div class="card-body admin-card-flow"><ol class="admin-docs-steps">
                <li>Cadastre cliente e processo com número CNJ válido antes de importar comunicações.</li>
                <li>Em <strong>Jurídico &gt; Intimações / DJEN</strong>, crie o monitor por processo ou OAB e defina o intervalo.</li>
                <li>Confira teor, tribunal, processo e prazos. Publicações entram em revisão e exigem aprovação humana.</li>
                <li>Somente conteúdo aprovado pode seguir para o histórico e portal do cliente.</li>
            </ol></div>
        </section>

        <section id="agenda" class="card admin-table-card admin-docs-section">
            <div class="card-header"><div><div class="admin-card-kicker">Organização diária</div><h2 class="card-title">Agenda, prazos e lembretes</h2></div></div>
            <div class="card-body admin-card-flow"><p class="text-muted">Confirme data, hora, fuso e responsável antes de salvar ou arrastar eventos.</p><p class="text-muted">Em <strong>Jurídico &gt; Prazos</strong>, configure lembretes. O cron precisa estar ativo para processá-los.</p></div>
        </section>

        <section id="documentos" class="card admin-table-card admin-docs-section">
            <div class="card-header"><div><div class="admin-card-kicker">Produção documental</div><h2 class="card-title">Modelos e gerador de documentos</h2></div></div>
            <div class="card-body admin-card-flow"><ol class="admin-docs-steps">
                <li>Cadastre o modelo no <strong>Gerador de documentos</strong> e revise o conteúdo no editor.</li>
                <li>Gere o documento vinculado ao cliente e processo; confira todos os dados antes de finalizar.</li>
                <li>Use o documento finalizado para criar uma solicitação de assinatura eletrônica.</li>
            </ol></div>
        </section>

        <section id="assinaturas" class="card admin-table-card admin-docs-section">
            <div class="card-header"><div><div class="admin-card-kicker">Evidência eletrônica</div><h2 class="card-title">Assinatura eletrônica de documentos</h2></div></div>
            <div class="card-body admin-card-flow"><ol class="admin-docs-steps">
                <li>No <code>.env</code>, habilite <code>ELECTRONIC_SIGNATURE_ENABLED=true</code>; mantenha o provedor <code>internal</code> e ajuste os prazos se necessário.</li>
                <li>Configure e teste o SMTP para enviar convites individuais aos signatários.</li>
                <li>Em <strong>Jurídico &gt; Assinaturas</strong>, selecione documento e signatários e defina se a ordem é obrigatória.</li>
                <li>Após a conclusão, baixe o PDF assinado e confira a validação das evidências.</li>
            </ol><div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>A evidência eletrônica e o certificado no PDF não equivalem a certificado ICP-Brasil.</div></div>
        </section>

        <section id="audiencias" class="card admin-table-card admin-docs-section">
            <div class="card-header"><div><div class="admin-card-kicker">Áudio privado</div><h2 class="card-title">Transcrição de audiências</h2></div></div>
            <div class="card-body admin-card-flow"><ol class="admin-docs-steps">
                <li>Ative o provedor em <strong>Conteúdo &gt; IA jurídica</strong>.</li>
                <li>Em <strong>Jurídico &gt; Transcrição de Audiências</strong>, vincule os registros e confirme o aviso legal.</li>
                <li>Envie o áudio ou grave no navegador. O arquivo permanece privado e o processamento ocorre pela fila.</li>
                <li>Revise a transcrição e a minuta, aprove com usuário autorizado e exporte a ata em DOCX.</li>
            </ol></div>
        </section>
        @endif

        <section id="changelog" class="card admin-table-card admin-docs-section">
            <div class="card-header"><div><div class="admin-card-kicker">Evolução do sistema</div><h2 class="card-title">Histórico de versões</h2></div><span class="badge text-bg-primary">Atual</span></div>
            <div class="card-body"><div class="admin-docs-release"><div class="admin-docs-release-marker"></div><div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2"><strong>Versão 2.5.0</strong><span class="text-muted small">27/08/2026</span></div>
                <ul class="text-muted mb-0 ps-3"><li>Contrato único de espaçamento e alinhamento para cards administrativos.</li><li>Guias de configuração dos novos módulos e integrações.</li><li>Modo sem padding preservado somente onde foi explicitamente marcado.</li></ul>
            </div></div></div>
        </section>
    </div></div>
</div></div></div>
@endsection
