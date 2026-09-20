<div class="card admin-table-card">
    <div class="card-header">
        <div>
            <div class="admin-card-kicker">Fluxo jurídico controlado</div>
            <h3 class="card-title">Assinatura eletrônica interna</h3>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-3 admin-premium-form">
            <div class="col-md-4 form-check ps-5 pt-4">
                <input type="checkbox" class="form-check-input" id="signature_enabled" name="signature_enabled" value="1" @checked(old('signature_enabled', $signatureConfig['enabled']))>
                <label class="form-check-label" for="signature_enabled">Ativar módulo de assinatura</label>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="signature_provider">Provedor</label>
                <select id="signature_provider" name="signature_provider" class="form-select">
                    <option value="internal" @selected(old('signature_provider', $signatureConfig['provider']) === 'internal')>Interno com evidências</option>
                </select>
                <small class="text-muted">Registra aceite eletrônico, IP, dispositivo, hash e trilha de auditoria.</small>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="signature_default_expiration_days">Prazo da solicitação</label>
                <input id="signature_default_expiration_days" type="number" name="signature_default_expiration_days" class="form-control" min="1" max="90" value="{{ old('signature_default_expiration_days', $signatureConfig['default_expiration_days']) }}">
                <small class="text-muted">Em dias.</small>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="signature_token_expiration_hours">Validade do link</label>
                <input id="signature_token_expiration_hours" type="number" name="signature_token_expiration_hours" class="form-control" min="1" max="720" value="{{ old('signature_token_expiration_hours', $signatureConfig['token_expiration_hours']) }}">
                <small class="text-muted">Em horas.</small>
            </div>
        </div>
    </div>
</div>

<div class="card admin-table-card">
    <div class="card-header">
        <div>
            <div class="admin-card-kicker">Antes de ativar</div>
            <h3 class="card-title">Checklist operacional</h3>
        </div>
    </div>
    <div class="card-body p-4 admin-card-flow">
        <ol class="admin-docs-steps mb-0">
            <li>Configure e teste o SMTP em <strong>Operação &gt; Sistema &gt; SMTP</strong>, pois convites e cópias assinadas são enviados por e-mail.</li>
            <li>Use somente documentos PDF privados e com SHA-256 confirmado pelo sistema.</li>
            <li>Revise as permissões <code>signature-requests.*</code> dos perfis que podem criar, cancelar, baixar ou auditar solicitações.</li>
            <li>Após ativar, crie a solicitação em <strong>Jurídico &gt; Assinaturas</strong>, informe os signatários e envie os convites individuais.</li>
            <li>O cliente abre o link, lê o PDF original, confirma nome e CPF/CNPJ quando exigido, registra consentimento e assina.</li>
            <li>Ao concluir, o PDF assinado é armazenado no sistema e uma cópia é enviada automaticamente aos signatários por e-mail.</li>
        </ol>
        <div class="alert alert-info mb-0">
            <i class="bi bi-info-circle me-2"></i>Esta assinatura eletrônica interna não equivale a certificado digital ICP-Brasil. Se o escritório exigir ICP-Brasil, será necessário integrar um provedor externo homologado.
        </div>
    </div>
</div>
