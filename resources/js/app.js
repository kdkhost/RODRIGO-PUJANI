import './bootstrap';

import Alpine from 'alpinejs';
import {
    applyAutoPlaceholders,
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
});
