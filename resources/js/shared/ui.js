import toastr from 'toastr';

const TOASTR_OPTIONS = {
    closeButton: true,
    newestOnTop: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    timeOut: 4500,
};

let recaptchaLoader = null;

function normalizeText(value) {
    return String(value || '')
        .replace(/\s+/g, ' ')
        .replace(/\*/g, '')
        .trim();
}

function inferFieldLabel(field) {
    const ariaLabel = normalizeText(field.getAttribute('aria-label'));
    if (ariaLabel !== '') {
        return ariaLabel;
    }

    const explicitPlaceholder = normalizeText(field.dataset.placeholder || '');
    if (explicitPlaceholder !== '') {
        return explicitPlaceholder;
    }

    if (field.id) {
        const label = document.querySelector(`label[for="${field.id}"]`);
        const labelText = normalizeText(label?.textContent || '');

        if (labelText !== '') {
            return labelText;
        }
    }

    const nearbyLabel = field.closest('.auth-field, .portal-field, .input-group, .form-group, .mb-3, div[class*="col-"]')
        ?.querySelector('label');
    const nearbyLabelText = normalizeText(nearbyLabel?.textContent || '');

    if (nearbyLabelText !== '') {
        return nearbyLabelText;
    }

    return '';
}

function recaptchaConfig() {
    const root = document.documentElement;

    return {
        enabled: root.dataset.recaptchaEnabled === '1',
        siteKey: root.dataset.recaptchaSiteKey || '',
    };
}

export function configureToastr() {
    window.toastr = toastr;
    Object.assign(toastr.options, TOASTR_OPTIONS);

    return toastr;
}

export function showToast(type, message) {
    const instance = configureToastr();
    const method = typeof instance?.[type] === 'function' ? type : 'info';

    if (String(message || '').trim() !== '') {
        instance[method](message);
    }
}

export function flushPageToasts(scope = document) {
    scope.querySelectorAll('[data-page-toast]').forEach((element) => {
        showToast(element.dataset.type || 'info', element.dataset.message || '');
        element.remove();
    });
}

export function applyAutoPlaceholders(scope = document) {
    scope.querySelectorAll('input, textarea').forEach((field) => {
        const type = (field.getAttribute('type') || 'text').toLowerCase();

        if ([
            'hidden',
            'checkbox',
            'radio',
            'file',
            'button',
            'submit',
            'reset',
            'image',
            'color',
            'range',
        ].includes(type)) {
            return;
        }

        if (field.tagName === 'TEXTAREA' && field.hasAttribute('data-editor')) {
            return;
        }

        if (normalizeText(field.getAttribute('placeholder')) !== '') {
            return;
        }

        const label = inferFieldLabel(field);

        if (label !== '') {
            field.setAttribute('placeholder', label);
        }
    });
}

export async function loadRecaptcha() {
    const config = recaptchaConfig();

    if (! config.enabled || config.siteKey === '') {
        return null;
    }

    if (window.grecaptcha?.execute) {
        return window.grecaptcha;
    }

    if (! recaptchaLoader) {
        recaptchaLoader = new Promise((resolve, reject) => {
            const existingScript = document.querySelector('script[data-recaptcha-loader="true"]');

            if (existingScript) {
                existingScript.addEventListener('load', () => resolve(window.grecaptcha), { once: true });
                existingScript.addEventListener('error', () => reject(new Error('Falha ao carregar o reCAPTCHA.')), { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(config.siteKey)}`;
            script.async = true;
            script.defer = true;
            script.dataset.recaptchaLoader = 'true';
            script.addEventListener('load', () => resolve(window.grecaptcha), { once: true });
            script.addEventListener('error', () => reject(new Error('Falha ao carregar o reCAPTCHA.')), { once: true });
            document.head.appendChild(script);
        });
    }

    const grecaptcha = await recaptchaLoader;

    if (! grecaptcha?.ready) {
        return grecaptcha;
    }

    await new Promise((resolve) => grecaptcha.ready(resolve));

    return grecaptcha;
}

export async function appendRecaptchaToken(form, action = 'submit') {
    const config = recaptchaConfig();

    if (! config.enabled || config.siteKey === '') {
        return '';
    }

    const grecaptcha = await loadRecaptcha();

    if (! grecaptcha?.execute) {
        throw new Error('reCAPTCHA indisponível.');
    }

    const token = await grecaptcha.execute(config.siteKey, {
        action,
    });

    let field = form.querySelector('input[name="recaptcha_token"]');

    if (! field) {
        field = document.createElement('input');
        field.type = 'hidden';
        field.name = 'recaptcha_token';
        form.appendChild(field);
    }

    field.value = token;

    return token;
}

export function bindRecaptchaForms(scope = document, options = {}) {
    const config = recaptchaConfig();

    if (! config.enabled || config.siteKey === '') {
        return;
    }

    const onError = typeof options.onError === 'function'
        ? options.onError
        : () => showToast('error', 'Não foi possível validar a proteção anti-spam agora.');

    scope.querySelectorAll('form[data-recaptcha-form]').forEach((form) => {
        if (form.dataset.recaptchaReady === 'true') {
            return;
        }

        form.addEventListener('submit', async (event) => {
            if (form.dataset.recaptchaBypass === 'true') {
                delete form.dataset.recaptchaBypass;
                return;
            }

            if (form.dataset.recaptchaPending === 'true') {
                event.preventDefault();
                return;
            }

            event.preventDefault();
            form.dataset.recaptchaPending = 'true';

            try {
                await appendRecaptchaToken(form, form.dataset.recaptchaAction || 'submit');
                form.dataset.recaptchaBypass = 'true';

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit(event.submitter || undefined);
                } else {
                    form.submit();
                }
            } catch (error) {
                onError(error);
            } finally {
                delete form.dataset.recaptchaPending;
            }
        });

        form.dataset.recaptchaReady = 'true';
    });
}
