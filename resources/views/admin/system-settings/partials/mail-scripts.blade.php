@push('styles')
<style>
    .admin-mail-token-list {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tokenButtons = Array.from(document.querySelectorAll('[data-mail-token]'));
    const preview = document.getElementById('mail-template-preview');
    const testButton = document.getElementById('smtp-test-button');
    const testEmail = document.getElementById('smtp_test_email');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let activeMailField = null;

    const readFieldValue = (selector) => {
        const field = document.querySelector(selector);
        if (!field) {
            return '';
        }

        if (field.dataset.editor === 'summernote' && typeof window.$ === 'function' && field.dataset.editorReady === 'true') {
            return window.$(field).summernote('code');
        }

        return field.value || '';
    };

    const values = () => ({
        name: 'Cliente de Exemplo',
        email: 'cliente@exemplo.com',
        app_name: '{{ addslashes(config('app.name')) }}',
        from_name: document.getElementById('mail_from_name')?.value || 'Equipe',
        reset_url: '{{ addslashes(url('/reset-password/token-exemplo?email=cliente@exemplo.com')) }}',
        year: String(new Date().getFullYear()),
    });

    const compile = (text, vars) => {
        let output = String(text || '');
        Object.entries(vars).forEach(([key, val]) => {
            output = output.split('@{{' + key + '}}').join(String(val ?? ''));
        });
        return output.replace(/\{\{\s*[^}]+\s*\}\}/g, '');
    };

    const renderMarkup = (value, vars) => {
        const compiled = compile(value || '', vars).trim();
        if (!compiled) {
            return '';
        }

        const hasHtml = /<[^>]+>/.test(compiled);
        return hasHtml ? compiled : compiled.replace(/\n/g, '<br>');
    };

    const renderPreview = () => {
        if (!preview) {
            return;
        }

        const vars = values();
        const subject = compile(document.getElementById('mail_template_reset_subject')?.value || '', vars);
        const header = renderMarkup(readFieldValue('#mail_template_header'), vars);
        const body = renderMarkup(readFieldValue('#mail_template_reset_body'), vars);
        const footer = renderMarkup(readFieldValue('#mail_template_footer'), vars);
        const brand = '{{ addslashes($branding['brand_name']) }}';
        const showLogo = document.getElementById('mail_template_show_logo')?.checked;
        const layout = document.getElementById('mail_template_layout')?.value || 'premium';
        const fontFamily = document.getElementById('mail_template_font_family')?.value || 'Segoe UI, Arial, sans-serif';
        const backgroundColor = document.getElementById('mail_template_background_color')?.value || '#0F172A';
        const bodyBackground = document.getElementById('mail_template_body_background_color')?.value || '#F4F6FB';
        const cardBackground = document.getElementById('mail_template_card_background_color')?.value || '#FFFFFF';
        const headingColor = document.getElementById('mail_template_heading_color')?.value || '#0F172A';
        const textColor = document.getElementById('mail_template_text_color')?.value || '#334155';
        const mutedColor = document.getElementById('mail_template_muted_color')?.value || '#64748B';
        const borderColor = document.getElementById('mail_template_border_color')?.value || '#E5E7EF';
        const buttonBackground = document.getElementById('mail_template_button_background_color')?.value || '#C49A3C';
        const buttonText = document.getElementById('mail_template_button_text_color')?.value || '#10131A';
        const customCss = document.getElementById('mail_template_custom_css')?.value || '';

        const heroBackground = layout === 'minimal' ? cardBackground : backgroundColor;
        const heroTextColor = layout === 'minimal' ? headingColor : '#FFFFFF';
        const heroMuted = layout === 'minimal' ? mutedColor : 'rgba(255,255,255,.78)';

        preview.innerHTML = `
            <style>
                #mail-template-preview .preview-shell * { box-sizing: border-box; }
                #mail-template-preview .preview-shell p { margin: 0 0 14px; }
                #mail-template-preview .preview-shell p:last-child { margin-bottom: 0; }
                #mail-template-preview .preview-shell a { color: ${buttonBackground}; }
                #mail-template-preview .preview-shell .preview-card { max-width: 680px; margin: 0 auto; background: ${cardBackground}; border: 1px solid ${borderColor}; border-radius: 18px; overflow: hidden; box-shadow: 0 18px 52px rgba(15, 23, 42, .16); }
                #mail-template-preview .preview-shell .preview-hero { padding: 28px; background: ${heroBackground}; color: ${heroTextColor}; }
                #mail-template-preview .preview-shell .preview-brand { display:flex; align-items:center; gap:12px; margin-bottom: 18px; }
                #mail-template-preview .preview-shell .preview-badge { width: 48px; height: 48px; border-radius: 14px; border: 1px solid rgba(255,255,255,.24); display:inline-flex; align-items:center; justify-content:center; background: rgba(255,255,255,.08); font-weight: 800; }
                #mail-template-preview .preview-shell .preview-brand-copy span { display:block; font-size:12px; text-transform:uppercase; letter-spacing:.12em; color:${heroMuted}; }
                #mail-template-preview .preview-shell .preview-subject { margin:0; font-size: 28px; line-height:1.2; font-weight:800; color:${heroTextColor}; }
                #mail-template-preview .preview-shell .preview-body { padding: 28px; color: ${textColor}; font-family:${fontFamily}; }
                #mail-template-preview .preview-shell .preview-body h1,
                #mail-template-preview .preview-shell .preview-body h2,
                #mail-template-preview .preview-shell .preview-body h3,
                #mail-template-preview .preview-shell .preview-body strong { color:${headingColor}; }
                #mail-template-preview .preview-shell .preview-divider { height:1px; margin:22px 0; background:${borderColor}; }
                #mail-template-preview .preview-shell .preview-action a { display:inline-block; background:${buttonBackground}; color:${buttonText}; text-decoration:none; padding:13px 20px; border-radius: 12px; font-weight:800; }
                #mail-template-preview .preview-shell .preview-footer { margin-top:18px; padding-top:18px; border-top:1px solid ${borderColor}; color:${mutedColor}; font-size:13px; line-height:1.7; }
                ${customCss}
            </style>
            <div class="preview-shell" style="padding:20px;background:${bodyBackground};font-family:${fontFamily};">
                <div style="font-size:13px; color:${mutedColor}; margin-bottom:10px;">Assunto</div>
                <div style="font-size:18px; font-weight:700; margin-bottom:18px; color:${headingColor};">${subject || '(sem assunto)'}</div>
                <div class="preview-card">
                    <div class="preview-hero">
                        <div class="preview-brand">
                            <div class="preview-badge">${showLogo ? 'L' : (brand || 'S').charAt(0)}</div>
                            <div class="preview-brand-copy">
                                <strong>${brand}</strong>
                                <span>Comunicacao oficial do sistema</span>
                            </div>
                        </div>
                        <h2 class="preview-subject">${subject || '(sem assunto)'}</h2>
                    </div>
                    <div class="preview-body">
                        ${header ? `<div>${header}</div>` : ''}
                        ${(header && body) ? '<div class="preview-divider"></div>' : ''}
                        <div>${body || '<p>Sem conteudo configurado.</p>'}</div>
                        <div class="preview-action" style="margin-top:24px;">
                            <a href="#">Redefinir senha</a>
                        </div>
                        ${footer ? `<div class="preview-footer">${footer}</div>` : ''}
                    </div>
                </div>
            </div>
        `;
    };

    document.querySelectorAll('.mail-template-input, #mail_from_name, [data-editor="summernote"]').forEach((input) => {
        input.addEventListener('focus', () => {
            activeMailField = input;
        });
        input.addEventListener('input', renderPreview);
        input.addEventListener('change', renderPreview);
    });

    document.addEventListener('summernote.change', renderPreview);

    document.addEventListener('focusin', (event) => {
        const field = event.target.closest?.('.note-editor')?.previousElementSibling;
        if (field?.matches?.('[data-editor="summernote"]')) {
            activeMailField = field;
        }
    });

    tokenButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const tokenValue = button.dataset.mailToken || '';
            if (!tokenValue || !activeMailField) {
                return;
            }

            if (activeMailField.dataset.editor === 'summernote' && typeof window.$ === 'function' && activeMailField.dataset.editorReady === 'true') {
                window.$(activeMailField).summernote('editor.restoreRange');
                window.$(activeMailField).summernote('pasteHTML', tokenValue);
                activeMailField.dispatchEvent(new Event('change', { bubbles: true }));
                renderPreview();
                return;
            }

            const start = activeMailField.selectionStart ?? activeMailField.value.length;
            const end = activeMailField.selectionEnd ?? activeMailField.value.length;
            const current = activeMailField.value || '';
            activeMailField.value = `${current.slice(0, start)}${tokenValue}${current.slice(end)}`;
            activeMailField.focus();
            const caret = start + tokenValue.length;
            activeMailField.setSelectionRange?.(caret, caret);
            activeMailField.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });

    renderPreview();

    if (testButton && testEmail) {
        testButton.addEventListener('click', async () => {
            const email = (testEmail.value || '').trim();
            if (!email) {
                window.toastr?.warning('Informe um e-mail para teste SMTP.');
                return;
            }

            testButton.disabled = true;
            try {
                const response = await fetch(testButton.dataset.testUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        test_email: email,
                        mailer: document.getElementById('mail_mailer')?.value || 'smtp',
                        host: document.getElementById('mail_host')?.value || '',
                        port: document.getElementById('mail_port')?.value || '',
                        encryption: document.getElementById('mail_encryption')?.value || 'tls',
                        username: document.getElementById('mail_username')?.value || '',
                        password: document.getElementById('mail_password')?.value || '',
                        from_address: document.getElementById('mail_from_address')?.value || '',
                        from_name: document.getElementById('mail_from_name')?.value || '',
                        template_header: readFieldValue('#mail_template_header'),
                        template_footer: readFieldValue('#mail_template_footer'),
                        template_generic_subject: document.getElementById('mail_template_generic_subject')?.value || '',
                        template_generic_body: readFieldValue('#mail_template_generic_body'),
                        template_show_logo: document.getElementById('mail_template_show_logo')?.checked ? 1 : 0,
                        template_font_family: document.getElementById('mail_template_font_family')?.value || '',
                        template_layout: document.getElementById('mail_template_layout')?.value || 'premium',
                        template_background_color: document.getElementById('mail_template_background_color')?.value || '',
                        template_body_background_color: document.getElementById('mail_template_body_background_color')?.value || '',
                        template_card_background_color: document.getElementById('mail_template_card_background_color')?.value || '',
                        template_heading_color: document.getElementById('mail_template_heading_color')?.value || '',
                        template_text_color: document.getElementById('mail_template_text_color')?.value || '',
                        template_muted_color: document.getElementById('mail_template_muted_color')?.value || '',
                        template_border_color: document.getElementById('mail_template_border_color')?.value || '',
                        template_button_background_color: document.getElementById('mail_template_button_background_color')?.value || '',
                        template_button_text_color: document.getElementById('mail_template_button_text_color')?.value || '',
                        template_custom_css: document.getElementById('mail_template_custom_css')?.value || '',
                    }),
                });

                const payload = await response.json();
                if (!response.ok) {
                    window.toastr?.error(payload.message || 'Falha no teste SMTP.');
                    return;
                }

                window.toastr?.success(payload.message || 'Teste SMTP enviado.');
            } catch (error) {
                window.toastr?.error('Nao foi possivel testar o SMTP agora.');
            } finally {
                testButton.disabled = false;
            }
        });
    }
});
</script>
@endpush
