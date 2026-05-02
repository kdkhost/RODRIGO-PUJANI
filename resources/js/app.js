import './bootstrap';

import Alpine from 'alpinejs';
import {
    applyAutoPlaceholders,
    bindDeviceAuditFields,
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
    bindDeviceAuditFields(document);
    bindRecaptchaForms(document);
    bindAuthPasswordToggles(document);
    bindAuthRememberAndAutofillControl(document);
});
