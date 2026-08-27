@extends('admin.layouts.app')

@section('content')
<div class="app-content-header admin-page-hero"><div class="container-fluid"><div class="admin-page-hero-inner">
    <div><div class="admin-eyebrow">Assistente de prontidão</div><h1>{{ $pageTitle }}</h1><p>Centralize os dados do escritório e do responsável antes de iniciar a operação jurídica.</p></div>
    <div class="admin-hero-stamp"><i class="bi bi-check2-circle"></i><div><strong>{{ $completion }}% preenchido</strong><small>Dados essenciais</small></div></div>
</div></div></div>

<div class="app-content"><div class="container-fluid">
    <div class="card admin-premium-card mb-4">
        <div class="card-header"><div><div class="admin-card-kicker">Prontidão operacional</div><h2 class="card-title">Itens necessários para usar todos os módulos</h2></div></div>
        <div class="card-body"><div class="row g-3">
            @foreach($readiness as $item)
                <div class="col-12 col-md-6 col-xxl-4"><a href="{{ $item['url'] }}" class="admin-premium-surface p-3 h-100 d-flex align-items-center justify-content-between gap-3 text-decoration-none">
                    <span class="fw-semibold">{{ $item['label'] }}</span>
                    <span class="badge badge-soft-{{ $item['ready'] ? 'success' : 'warning' }}">{{ $item['ready'] ? 'Pronto' : 'Configurar' }}</span>
                </a></div>
            @endforeach
        </div></div>
    </div>

    <form action="{{ route('admin.office-setup.update') }}" method="POST" data-ajax-form>
        @csrf @method('PUT')
        <div id="office-data" class="card admin-premium-card mb-4">
            <div class="card-header"><div><div class="admin-card-kicker">Dados institucionais</div><h2 class="card-title">Escritório de advocacia</h2></div></div>
            <div class="card-body"><div class="row g-3 admin-premium-form">
                <div class="col-md-7"><label class="form-label">Razão social</label><input name="company_legal_name" class="form-control" value="{{ old('company_legal_name', $office['company_legal_name']) }}" required></div>
                <div class="col-md-5"><label class="form-label">Nome do escritório</label><input name="company_trade_name" class="form-control" value="{{ old('company_trade_name', $office['company_trade_name'] ?: $office['company_legal_name']) }}" required></div>
                <div class="col-md-4"><label class="form-label">CNPJ</label><input name="company_document" data-mask="cnpj" class="form-control" value="{{ old('company_document', $office['company_document']) }}" required></div>
                <div class="col-md-4"><label class="form-label">Registro da sociedade na OAB</label><input name="company_oab_registration" class="form-control" value="{{ old('company_oab_registration', $office['company_oab_registration']) }}"></div>
                <div class="col-md-4"><label class="form-label">Horário de atendimento</label><input name="business_hours" class="form-control" value="{{ old('business_hours', $office['business_hours']) }}" placeholder="Seg. a sex.: 08h às 18h" required></div>
                <div class="col-md-3"><label class="form-label">Telefone</label><input name="company_phone" data-mask="phone" class="form-control" value="{{ old('company_phone', $office['company_phone']) }}" required></div>
                <div class="col-md-3"><label class="form-label">WhatsApp</label><input name="company_whatsapp" data-mask="phone" class="form-control" value="{{ old('company_whatsapp', $office['company_whatsapp']) }}"></div>
                <div class="col-md-3"><label class="form-label">E-mail principal</label><input type="email" name="company_email" class="form-control" value="{{ old('company_email', $office['company_email']) }}" required></div>
                <div class="col-md-3"><label class="form-label">E-mail secundário</label><input type="email" name="company_secondary_email" class="form-control" value="{{ old('company_secondary_email', $office['company_secondary_email']) }}"></div>
            </div></div>
        </div>

        <div class="card admin-premium-card mb-4">
            <div class="card-header"><div><div class="admin-card-kicker">Preenchimento automático</div><h2 class="card-title">Endereço do escritório</h2></div></div>
            <div class="card-body"><div class="row g-3 admin-premium-form">
                <div class="col-md-3"><label class="form-label">CEP</label><input name="address_zip" data-mask="cep" data-cep-autofill class="form-control" value="{{ old('address_zip', $office['address_zip']) }}" required></div>
                <div class="col-md-7"><label class="form-label">Logradouro</label><input name="address_street" class="form-control" value="{{ old('address_street', $office['address_street']) }}" required></div>
                <div class="col-md-2"><label class="form-label">Número</label><input name="address_number" class="form-control" value="{{ old('address_number', $office['address_number']) }}" required></div>
                <div class="col-md-4"><label class="form-label">Complemento</label><input name="address_complement" class="form-control" value="{{ old('address_complement', $office['address_complement']) }}"></div>
                <div class="col-md-3"><label class="form-label">Bairro</label><input name="address_district" class="form-control" value="{{ old('address_district', $office['address_district']) }}" required></div>
                <div class="col-md-4"><label class="form-label">Cidade</label><input name="address_city" class="form-control" value="{{ old('address_city', $office['address_city']) }}" required></div>
                <div class="col-md-1"><label class="form-label">UF</label><input name="address_state" class="form-control text-uppercase" maxlength="2" value="{{ old('address_state', $office['address_state']) }}" required></div>
            </div></div>
        </div>

        <div id="responsible-data" class="card admin-premium-card mb-4">
            <div class="card-header"><div><div class="admin-card-kicker">Responsável pela operação</div><h2 class="card-title">Advogado ou gestor do escritório</h2></div></div>
            <div class="card-body"><div class="row g-3 admin-premium-form">
                <div class="col-md-6"><label class="form-label">Nome completo</label><input name="responsible_name" class="form-control" value="{{ old('responsible_name', $responsible->name) }}" required></div>
                <div class="col-md-6"><label class="form-label">E-mail de acesso</label><input type="email" name="responsible_email" class="form-control" value="{{ old('responsible_email', $responsible->email) }}" required></div>
                <div class="col-md-3"><label class="form-label">Telefone</label><input name="responsible_phone" data-mask="phone" class="form-control" value="{{ old('responsible_phone', $responsible->phone) }}" required></div>
                <div class="col-md-3"><label class="form-label">WhatsApp</label><input name="responsible_whatsapp" data-mask="phone" class="form-control" value="{{ old('responsible_whatsapp', $responsible->whatsapp) }}"></div>
                <div class="col-md-3"><label class="form-label">CPF</label><input name="responsible_document" data-mask="cpf" class="form-control" value="{{ old('responsible_document', $responsible->document_number) }}" required></div>
                <div class="col-md-3"><label class="form-label">Cargo ou função</label><input name="professional_title" class="form-control" value="{{ old('professional_title', $responsible->professional_title) }}" placeholder="Advogado responsável" required></div>
                <div class="col-md-4"><label class="form-label">Número da OAB</label><input name="oab_number" class="form-control" value="{{ old('oab_number', $responsible->oab_number) }}" required></div>
                <div class="col-md-2"><label class="form-label">UF da OAB</label><input name="oab_state" class="form-control text-uppercase" maxlength="2" value="{{ old('oab_state', $responsible->oab_state) }}" required></div>
                <div class="col-md-6"><label class="form-label">Fuso horário</label><select name="timezone" class="form-select" required>@foreach(timezone_identifiers_list() as $timezone)<option value="{{ $timezone }}" @selected(old('timezone', $responsible->timezone ?: 'America/Sao_Paulo') === $timezone)>{{ $timezone }}</option>@endforeach</select></div>
            </div></div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                <p class="text-muted mb-0">As integrações externas continuam protegidas em suas próprias telas e nunca exibem os segredos salvos.</p>
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1"></i>Salvar configuração inicial</button>
            </div>
        </div>
    </form>
</div></div>
@endsection
