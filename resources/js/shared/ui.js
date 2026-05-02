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

export function bindAuthPasswordToggles(scope = document) {
    scope.querySelectorAll('.auth-form input[type="password"]').forEach((input, index) => {
        if (input.dataset.passwordToggleReady === 'true') {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'auth-password-wrap';
        input.parentNode?.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'auth-password-toggle';
        button.setAttribute('aria-label', 'Mostrar senha');
        button.innerHTML = '<i class="bi bi-eye"></i>';

        button.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.setAttribute('aria-label', isPassword ? 'Ocultar senha' : 'Mostrar senha');
            button.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });

        wrapper.appendChild(button);
        input.dataset.passwordToggleReady = 'true';

        if (!input.id) {
            input.id = `auth-password-${index + 1}`;
        }
    });
}

export function bindAuthRememberAndAutofillControl(scope = document) {
    const forms = scope.querySelectorAll('.auth-form');
    const storageKey = 'auth-login-remember-email';

    forms.forEach((form) => {
        form.setAttribute('autocomplete', 'off');

        form.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]').forEach((field) => {
            field.setAttribute('autocomplete', 'off');
            field.setAttribute('data-lpignore', 'true');

            if (!field.readOnly) {
                field.readOnly = true;
                const unlock = () => {
                    field.readOnly = false;
                    field.removeEventListener('focus', unlock);
                    field.removeEventListener('mousedown', unlock);
                    field.removeEventListener('touchstart', unlock);
                };

                field.addEventListener('focus', unlock, { once: true });
                field.addEventListener('mousedown', unlock, { once: true });
                field.addEventListener('touchstart', unlock, { once: true });
            }
        });

        const email = form.querySelector('input[name="email"]');
        const remember = form.querySelector('input[name="remember"]');

        if (email && remember) {
            try {
                const remembered = JSON.parse(window.localStorage.getItem(storageKey) || '{}');
                if (remembered?.email && !email.value) {
                    email.value = String(remembered.email);
                    remember.checked = true;
                }
            } catch (_) {
                // noop
            }

            form.addEventListener('submit', () => {
                try {
                    if (remember.checked && String(email.value || '').trim() !== '') {
                        window.localStorage.setItem(storageKey, JSON.stringify({
                            email: String(email.value || '').trim(),
                        }));
                        return;
                    }

                    window.localStorage.removeItem(storageKey);
                } catch (_) {
                    // noop
                }
            });
        }
    });
}

function randomDeviceToken() {
    if (window.crypto?.randomUUID) {
        return window.crypto.randomUUID();
    }

    return `dev-${Date.now()}-${Math.random().toString(16).slice(2, 10)}`;
}

function resolvePersistentDeviceId() {
    const storageKey = 'pujani-device-id';

    try {
        let value = window.localStorage.getItem(storageKey);

        if (!value) {
            value = randomDeviceToken();
            window.localStorage.setItem(storageKey, value);
        }

        return value;
    } catch (error) {
        return randomDeviceToken();
    }
}

function ensureHiddenField(form, name) {
    let field = form.querySelector(`input[name="${name}"]`);

    if (!field) {
        field = document.createElement('input');
        field.type = 'hidden';
        field.name = name;
        form.appendChild(field);
    }

    return field;
}

async function collectDeviceAuditContext() {
    const uaData = navigator.userAgentData || null;
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection || null;
    let platform = navigator.platform || '';
    let model = '';

    if (uaData?.platform) {
        platform = uaData.platform;
    }

    if (uaData?.getHighEntropyValues) {
        try {
            const entropy = await uaData.getHighEntropyValues(['model', 'platformVersion']);
            model = entropy?.model || '';
        } catch (error) {
            // Alguns navegadores bloqueiam high entropy values sem permissao adicional.
        }
    }

    return {
        id: resolvePersistentDeviceId(),
        screen: `${window.screen?.width || 0}x${window.screen?.height || 0}`,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '',
        language: navigator.language || '',
        platform,
        touchPoints: String(navigator.maxTouchPoints || 0),
        vendor: navigator.vendor || '',
        network: connection?.effectiveType || connection?.type || '',
        model,
        mac: '',
    };
}

export function bindDeviceAuditFields(scope = document) {
    const forms = Array.from(scope.querySelectorAll('form'));

    if (!forms.length) {
        return;
    }

    forms.forEach((form) => {
        if (form.dataset.deviceAuditReady === 'true') {
            return;
        }

        const method = String(form.getAttribute('method') || 'GET').toUpperCase();

        if (!['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            return;
        }

        const syncFields = async () => {
            const device = await collectDeviceAuditContext();

            ensureHiddenField(form, '_device_id').value = device.id;
            ensureHiddenField(form, '_device_screen').value = device.screen;
            ensureHiddenField(form, '_device_timezone').value = device.timezone;
            ensureHiddenField(form, '_device_language').value = device.language;
            ensureHiddenField(form, '_device_platform').value = device.platform;
            ensureHiddenField(form, '_device_touch_points').value = device.touchPoints;
            ensureHiddenField(form, '_device_vendor').value = device.vendor;
            ensureHiddenField(form, '_device_network').value = device.network;
            ensureHiddenField(form, '_device_model').value = device.model;
            ensureHiddenField(form, '_device_mac').value = device.mac;
        };

        syncFields().catch(() => null);
        form.addEventListener('submit', () => {
            syncFields().catch(() => null);
        });

        form.dataset.deviceAuditReady = 'true';
    });
}
