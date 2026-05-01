import './bootstrap';

import Alpine from 'alpinejs';
import {
    applyAutoPlaceholders,
    bindAuthPasswordToggles,
    bindAuthRememberAndAutofillControl,
    bindRecaptchaForms,
    configureToastr,
    flushPageToasts,
} from './shared/ui';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    configureToastr();
    flushPageToasts();
    applyAutoPlaceholders();
    bindRecaptchaForms(document);
    bindAuthPasswordToggles(document);
    bindAuthRememberAndAutofillControl(document);
});
