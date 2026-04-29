import './bootstrap';

import * as bootstrap from 'bootstrap';
import $ from 'jquery';
import toastr from 'toastr';
import Swal from 'sweetalert2';
import Inputmask from 'inputmask';
import * as FilePond from 'filepond';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import Chart from 'chart.js/auto';
import { Calendar } from '@fullcalendar/core';
import ptBrLocale from '@fullcalendar/core/locales/pt-br';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import { applyAutoPlaceholders, configureToastr } from './shared/ui';

window.bootstrap = bootstrap;
window.$ = window.jQuery = $;
globalThis.$ = $;
globalThis.jQuery = $;
window.toastr = toastr;
window.Swal = Swal;
window.Chart = Chart;
window.FullCalendar = {
    Calendar,
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin],
    locales: { 'pt-br': ptBrLocale },
};

FilePond.registerPlugin(
    FilePondPluginFileValidateSize,
    FilePondPluginFileValidateType,
    FilePondPluginImagePreview,
);

configureToastr();

const AdminUI = {
    modalInstance: null,
    calendarEventPanel: null,
    activeCalendarEventId: null,
    summernoteWarningShown: false,

    escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    boot() {
        this.ensureModal();
        this.ensureProgressCard();
        this.ensureCalendarEventPanel();
        this.flushPageToasts();
        this.bindBackToTop();
        this.bindDocumentEvents();
        this.bindTourGuide();
        this.initPlugins(document);
        this.initAjaxTables(document);
    },

    showToast(type, message) {
        const method = typeof window.toastr?.[type] === 'function' ? type : 'info';
        window.toastr[method](message);
    },

    flushPageToasts() {
        document.querySelectorAll('[data-page-toast]').forEach((element) => {
            this.showToast(element.dataset.type || 'info', element.dataset.message || '');
            element.remove();
        });
    },

    bindBackToTop() {
        const button = document.querySelector('[data-admin-scroll-top]');

        if (!button) {
            return;
        }

        const syncVisibility = () => {
            const visible = window.scrollY > 320;
            button.classList.toggle('is-visible', visible);
            button.setAttribute('aria-hidden', visible ? 'false' : 'true');
        };

        button.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth',
            });
        });

        syncVisibility();
        window.addEventListener('scroll', syncVisibility, { passive: true });
    },

    ensureModal() {
        if (document.getElementById('admin-modal')) {
            this.modalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('admin-modal'));
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <div class="modal fade" id="admin-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Carregando</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body"></div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(wrapper.firstElementChild);
        this.modalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('admin-modal'));
    },

    ensureProgressCard() {
        if (document.getElementById('admin-upload-progress')) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.id = 'admin-upload-progress';
        wrapper.className = 'admin-upload-progress card shadow-sm';
        wrapper.innerHTML = `
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="small text-uppercase">Upload em andamento</strong>
                    <span data-progress-percent class="small text-muted">0%</span>
                </div>
                <div class="progress mb-2" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" data-progress-bar style="width: 0%"></div>
                </div>
                <div class="small text-muted" data-progress-eta>Calculando tempo restante...</div>
                <div class="admin-upload-progress-summary" data-progress-summary></div>
            </div>
        `;
        document.body.appendChild(wrapper);
    },

    ensureCalendarEventPanel() {
        if (document.getElementById('admin-calendar-event-panel')) {
            this.calendarEventPanel = document.getElementById('admin-calendar-event-panel');
            return;
        }

        const panel = document.createElement('div');
        panel.id = 'admin-calendar-event-panel';
        panel.className = 'admin-calendar-event-panel';
        panel.setAttribute('data-calendar-event-panel', 'true');
        document.body.appendChild(panel);
        this.calendarEventPanel = panel;
    },

    hideCalendarEventPanel() {
        if (!this.calendarEventPanel) {
            return;
        }

        this.calendarEventPanel.classList.remove('active');
        this.calendarEventPanel.innerHTML = '';
        this.activeCalendarEventId = null;
    },

    formatCalendarEventDate(event) {
        if (!event.start) {
            return 'Sem data definida';
        }

        const dateFormatter = new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
        const dateTimeFormatter = new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });

        if (event.allDay) {
            if (event.end) {
                const inclusiveEnd = new Date(event.end.getTime() - 1000);
                return `${dateFormatter.format(event.start)} até ${dateFormatter.format(inclusiveEnd)} • Dia inteiro`;
            }

            return `${dateFormatter.format(event.start)} • Dia inteiro`;
        }

        if (!event.end) {
            return dateTimeFormatter.format(event.start);
        }

        return `${dateTimeFormatter.format(event.start)} até ${dateTimeFormatter.format(event.end)}`;
    },

    showCalendarEventPanel(event, triggerEvent) {
        this.ensureCalendarEventPanel();

        if (!this.calendarEventPanel) {
            return;
        }

        if (this.activeCalendarEventId === event.id && this.calendarEventPanel.classList.contains('active')) {
            this.hideCalendarEventPanel();
            return;
        }

        const props = event.extendedProps || {};
        const details = [
            props.owner ? `<div><span>Responsável</span><strong>${this.escapeHtml(props.owner)}</strong></div>` : '',
            props.location ? `<div><span>Local</span><strong>${this.escapeHtml(props.location)}</strong></div>` : '',
            props.category ? `<div><span>Categoria</span><strong>${this.escapeHtml(props.category)}</strong></div>` : '',
            props.visibilityLabel ? `<div><span>Visibilidade</span><strong>${this.escapeHtml(props.visibilityLabel)}</strong></div>` : '',
        ].filter(Boolean).join('');

        const badges = [
            props.statusLabel ? `<span class="admin-calendar-panel-badge">${this.escapeHtml(props.statusLabel)}</span>` : '',
            props.displayLabel && props.display !== 'auto'
                ? `<span class="admin-calendar-panel-badge admin-calendar-panel-badge-soft">${this.escapeHtml(props.displayLabel)}</span>`
                : '',
        ].filter(Boolean).join('');

        this.calendarEventPanel.innerHTML = `
            <button type="button" class="admin-calendar-panel-close" aria-label="Fechar" data-calendar-panel-close>
                <i class="bi bi-x-lg"></i>
            </button>
            <div class="admin-calendar-panel-head">
                <div class="admin-card-kicker">Evento selecionado</div>
                <h4>${this.escapeHtml(event.title)}</h4>
                <p>${this.escapeHtml(this.formatCalendarEventDate(event))}</p>
            </div>
            ${badges ? `<div class="admin-calendar-panel-badges">${badges}</div>` : ''}
            ${details ? `<div class="admin-calendar-panel-grid">${details}</div>` : ''}
            ${props.description ? `<div class="admin-calendar-panel-copy">${this.escapeHtml(props.description)}</div>` : ''}
            <div class="admin-calendar-panel-actions">
                <button type="button" class="btn btn-primary" data-modal-url="${this.escapeHtml(props.editUrl || '')}" data-modal-title="${this.escapeHtml(event.title)}">
                    <i class="bi bi-pencil-square me-1"></i>Editar
                </button>
                <button
                    type="button"
                    class="btn btn-outline-danger"
                    data-delete-url="${this.escapeHtml(props.deleteUrl || '')}"
                    data-table-target="#admin-calendar-events-table"
                    data-calendar-target="#admin-calendar"
                    data-confirm-text="O evento será removido permanentemente da agenda."
                >
                    <i class="bi bi-trash me-1"></i>Excluir
                </button>
                ${props.externalUrl ? `
                    <a href="${this.escapeHtml(props.externalUrl)}" class="btn btn-outline-secondary" target="_blank" rel="noopener">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Abrir link
                    </a>
                ` : ''}
            </div>
        `;

        const isMobile = window.innerWidth < 992;
        this.calendarEventPanel.style.left = '';
        this.calendarEventPanel.style.top = '';
        this.calendarEventPanel.style.right = '';
        this.calendarEventPanel.style.bottom = '';

        if (isMobile) {
            this.calendarEventPanel.style.left = '1rem';
            this.calendarEventPanel.style.right = '1rem';
            this.calendarEventPanel.style.bottom = '1rem';
        } else {
            const width = 360;
            const estimatedHeight = 320;
            const left = Math.min(triggerEvent.clientX + 16, window.innerWidth - width - 20);
            const top = Math.min(triggerEvent.clientY + 16, window.innerHeight - estimatedHeight - 20);
            this.calendarEventPanel.style.left = `${Math.max(16, left)}px`;
            this.calendarEventPanel.style.top = `${Math.max(16, top)}px`;
        }

        this.calendarEventPanel.classList.add('active');
        this.activeCalendarEventId = event.id;
    },

    bindDocumentEvents() {
        document.addEventListener('click', (event) => {
            const panelClose = event.target.closest('[data-calendar-panel-close]');
            if (panelClose) {
                event.preventDefault();
                this.hideCalendarEventPanel();
                return;
            }

            const modalTrigger = event.target.closest('[data-modal-url]');
            if (modalTrigger) {
                event.preventDefault();
                this.loadModal(modalTrigger.dataset.modalUrl, modalTrigger.dataset.modalTitle || 'Editar');
                return;
            }

            const deleteTrigger = event.target.closest('[data-delete-url]');
            if (deleteTrigger) {
                event.preventDefault();
                this.confirmDelete(deleteTrigger);
                return;
            }

            const toggleTrigger = event.target.closest('[data-toggle-url]');
            if (toggleTrigger) {
                event.preventDefault();
                this.toggleUserStatus(toggleTrigger);
                return;
            }

            const codeTrigger = event.target.closest('[data-generate-client-code]');
            if (codeTrigger) {
                event.preventDefault();
                const input = codeTrigger.closest('.input-group')?.querySelector('input[name="portal_access_code"]');
                if (input) {
                    input.value = Math.random().toString(36).slice(-8).toUpperCase();
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
                return;
            }

            const calendarReset = event.target.closest('[data-calendar-reset]');
            if (calendarReset) {
                window.setTimeout(() => {
                    const form = calendarReset.closest('form');
                    const calendar = document.querySelector(form?.dataset.calendarToolbar || '#admin-calendar');
                    const tableTargets = new Set(
                        Array.from(form?.querySelectorAll('[data-table-target]') || [])
                            .map((item) => item.dataset.tableTarget)
                            .filter(Boolean),
                    );
                    this.refetchCalendar(calendar);
                    tableTargets.forEach((selector) => this.refreshTable(document.querySelector(selector)));
                }, 0);
                return;
            }

            const paginationLink = event.target.closest('[data-ajax-table] .pagination a, [data-ajax-table] .admin-pagination a');
            if (paginationLink) {
                event.preventDefault();
                const table = paginationLink.closest('[data-ajax-table]');
                this.refreshTable(table, paginationLink.href);
                return;
            }

            if (!event.target.closest('.fc-event, .fc-list-item, [data-calendar-event-panel]')) {
                this.hideCalendarEventPanel();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.hideCalendarEventPanel();
            }
        });

        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('form');
            if (!form) {
                return;
            }

            const submitter = event.submitter;

            if (submitter?.dataset.confirmSubmit === 'true') {
                event.preventDefault();

                const result = await Swal.fire({
                    title: submitter.dataset.confirmTitle || 'Confirmar ação?',
                    text: submitter.dataset.confirmText || 'Deseja continuar com esta operação?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: submitter.dataset.confirmButton || 'Confirmar',
                    cancelButtonText: 'Cancelar',
                });

                if (!result.isConfirmed) {
                    return;
                }

                if (form.matches('[data-ajax-form]')) {
                    this.submitForm(form);
                    return;
                }

                form.submit();
                return;
            }

            if (!form.matches('[data-ajax-form]')) {
                return;
            }

            event.preventDefault();
            this.submitForm(form);
        });

        document.addEventListener('input', (event) => {
            const searchInput = event.target.closest('[data-table-search]');
            if (!searchInput) {
                return;
            }

            const table = document.querySelector(searchInput.dataset.tableTarget);
            const toolbar = searchInput.closest('form');
            clearTimeout(searchInput._searchTimer);
            searchInput._searchTimer = setTimeout(() => {
                this.refreshTable(table);
                this.refetchCalendar(toolbar?.dataset.calendarToolbar);
            }, 350);
        });

        document.addEventListener('change', (event) => {
            const filterInput = event.target.closest('[data-table-filter]');
            if (!filterInput) {
                return;
            }

            const table = document.querySelector(filterInput.dataset.tableTarget);
            const toolbar = filterInput.closest('form');
            this.refreshTable(table);
            this.refetchCalendar(toolbar?.dataset.calendarToolbar);
        });
    },

    initAjaxTables(scope) {
        scope.querySelectorAll('[data-ajax-table]').forEach((table) => {
            if (!table.dataset.loaded) {
                this.refreshTable(table);
            }
        });
    },

    serializeToolbar(table) {
        const selector = table.dataset.toolbar;
        const toolbar = selector ? document.querySelector(selector) : null;

        if (!toolbar) {
            return new URLSearchParams();
        }

        return new URLSearchParams(new FormData(toolbar));
    },

    async refreshTable(table, url = null) {
        if (!table) {
            return;
        }

        const endpoint = url || table.dataset.url;
        const params = this.serializeToolbar(table);
        const requestUrl = new URL(endpoint, window.location.origin);

        params.forEach((value, key) => {
            if (value) {
                requestUrl.searchParams.set(key, value);
            }
        });

        table.classList.add('opacity-50');

        try {
            const response = await window.axios.get(requestUrl.toString());
            table.innerHTML = response.data.html;
            table.dataset.loaded = 'true';
            this.initPlugins(table);
        } catch (error) {
            this.showToast('error', error.response?.data?.message || 'Não foi possível carregar a listagem.');
        } finally {
            table.classList.remove('opacity-50');
        }
    },

    async loadModal(url, title) {
        this.hideCalendarEventPanel();
        const modal = document.getElementById('admin-modal');
        modal.querySelector('.modal-title').textContent = title;
        modal.querySelector('.modal-body').innerHTML = '<div class="py-5 text-center text-muted">Carregando...</div>';
        this.modalInstance.show();

        try {
            const response = await window.axios.get(url);
            modal.querySelector('.modal-title').textContent = response.data.title || title;
            modal.querySelector('.modal-body').innerHTML = response.data.html;
            this.initPlugins(modal);
        } catch (error) {
            console.error('Admin modal load failed.', error);
            modal.querySelector('.modal-body').innerHTML = `<div class="alert alert-danger mb-0">${error.response?.data?.message || 'Falha ao carregar o formulário.'}</div>`;
        }
    },

    async confirmDelete(trigger) {
        this.hideCalendarEventPanel();
        const requiresPassword = trigger.dataset.requirePassword === 'true';
        const confirmResult = await Swal.fire({
            title: trigger.dataset.confirmTitle || 'Confirmar exclusão?',
            text: trigger.dataset.confirmText || 'Essa ação não poderá ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Excluir',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
            input: requiresPassword ? 'password' : undefined,
            inputLabel: requiresPassword ? (trigger.dataset.passwordLabel || 'Senha do administrador') : undefined,
            inputPlaceholder: requiresPassword ? 'Digite a senha para confirmar' : undefined,
            inputAttributes: requiresPassword ? {
                autocomplete: 'current-password',
                autocapitalize: 'off',
                spellcheck: 'false',
            } : undefined,
            inputValidator: requiresPassword
                ? (value) => (!value ? 'Informe a senha para continuar.' : undefined)
                : undefined,
        });

        if (!confirmResult.isConfirmed) {
            return;
        }

        try {
            const payload = requiresPassword ? { password: confirmResult.value } : {};
            const response = await window.axios.delete(trigger.dataset.deleteUrl, { data: payload });
            this.showToast('success', response.data.message || 'Registro excluído com sucesso.');
            const table = document.querySelector(trigger.dataset.tableTarget);
            this.refreshTable(table);
            this.refetchCalendar(response.data.calendarTarget || trigger.dataset.calendarTarget);
        } catch (error) {
            this.showToast('error', error.response?.data?.message || 'Falha ao excluir o registro.');
        }
    },

    async toggleUserStatus(trigger) {
        this.hideCalendarEventPanel();

        const confirmResult = await Swal.fire({
            title: trigger.dataset.toggleTitle || 'Alterar status?',
            text: trigger.dataset.toggleText || 'O acesso deste usuário será atualizado imediatamente.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: trigger.dataset.toggleButton || 'Confirmar',
            cancelButtonText: 'Cancelar',
        });

        if (!confirmResult.isConfirmed) {
            return;
        }

        trigger.disabled = true;

        try {
            const response = await window.axios.patch(trigger.dataset.toggleUrl);
            this.showToast('success', response.data.message || 'Status atualizado com sucesso.');
            const table = document.querySelector(response.data.tableTarget || trigger.dataset.tableTarget);
            this.refreshTable(table);
        } catch (error) {
            this.showToast('error', error.response?.data?.message || 'Falha ao alterar o status do usuário.');
        } finally {
            trigger.disabled = false;
        }
    },

    async submitForm(form) {
        const formData = new FormData(form);
        const method = (form.dataset.method || form.method || 'POST').toUpperCase();
        const url = form.action;
        const submitButton = form.querySelector('[type="submit"]');

        if (submitButton) {
            submitButton.disabled = true;
        }

        this.resetFormErrors(form);
        this.markUploadPreviewsState(form, 'uploading', 0);

        try {
            const response = await window.axios({
                method,
                url,
                data: formData,
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                onUploadProgress: (progressEvent) => {
                    const total = progressEvent.total || 0;
                    const loaded = progressEvent.loaded || 0;
                    const percent = total ? Math.round((loaded / total) * 100) : 0;
                    
                    if (!form._uploadStats) {
                        form._uploadStats = {
                            start: Date.now(),
                            samples: [],
                            lastLoaded: 0,
                            lastTime: Date.now()
                        };
                    }

                    const now = Date.now();
                    const timeDiff = (now - form._uploadStats.lastTime) / 1000;
                    const loadedDiff = loaded - form._uploadStats.lastLoaded;
                    
                    if (timeDiff > 0.1) {
                        const currentSpeed = loadedDiff / timeDiff;
                        form._uploadStats.samples.push(currentSpeed);
                        if (form._uploadStats.samples.length > 10) form._uploadStats.samples.shift();
                        
                        form._uploadStats.lastTime = now;
                        form._uploadStats.lastLoaded = loaded;
                    }

                    const avgSpeed = form._uploadStats.samples.length > 0 
                        ? form._uploadStats.samples.reduce((a, b) => a + b, 0) / form._uploadStats.samples.length 
                        : 0;

                    const eta = avgSpeed > 0 && total > 0 ? Math.max(0, Math.round((total - loaded) / avgSpeed)) : 0;
                    
                    this.updateProgress(percent, eta, form);
                    this.markUploadPreviewsState(form, 'uploading', percent);
                },
            });

            this.markUploadPreviewsState(form, 'done', 100);
            this.hideProgress();
            this.showToast('success', response.data.message || 'Registro salvo com sucesso.');

            if (response.data.closeModal !== false && this.modalInstance) {
                this.modalInstance.hide();
            }

            if (response.data.redirect) {
                window.location.href = response.data.redirect;
                return;
            }

            if (response.data.tableTarget) {
                const table = document.querySelector(response.data.tableTarget);
                this.refreshTable(table);
            }

            this.refetchCalendar(response.data.calendarTarget);
        } catch (error) {
            this.markUploadPreviewsState(form, 'error', 0);
            this.hideProgress();

            if (error.response?.status === 423) {
                const redirectUrl = error.response?.data?.redirect;
                this.showToast('warning', error.response?.data?.message || 'Confirme sua senha novamente para continuar.');

                if (redirectUrl) {
                    window.setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 500);
                }

                return;
            }

            if (error.response?.status === 422) {
                this.renderValidationErrors(form, error.response.data.errors || {});
                this.showToast('warning', 'Revise os campos destacados.');
                return;
            }

            this.showToast('error', error.response?.data?.message || 'Falha ao processar a solicitação.');
        } finally {
            delete form._uploadStats;
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    },

    resetFormErrors(form) {
        form.querySelectorAll('.is-invalid').forEach((item) => item.classList.remove('is-invalid'));
        form.querySelectorAll('[data-error-for]').forEach((item) => {
            item.textContent = '';
        });
        form.querySelectorAll('[data-generated-error="true"]').forEach((item) => item.remove());
    },

    renderValidationErrors(form, errors) {
        Object.entries(errors).forEach(([field, messages]) => {
            this.applyFieldError(form, field, messages[0]);
        });
    },

    applyFieldError(form, field, message) {
        const input = this.resolveInput(form, field);
        const errorNode = this.resolveErrorNode(form, input, field);

        if (input) {
            this.markInvalid(input);
        }

        if (errorNode) {
            errorNode.textContent = message;
            return;
        }

        this.showToast('warning', message);
    },

    resolveInput(form, field) {
        const root = field.split('.')[0];
        const candidates = Array.from(new Set([
            field,
            field.replace(/\.\d+$/g, '[]'),
            `${root}[]`,
            root,
        ]));

        for (const candidate of candidates) {
            const selector = `[name="${candidate.replace(/"/g, '\\"')}"]`;
            const input = form.querySelector(selector);
            if (input) {
                return input;
            }
        }

        return form.querySelector(`[name^="${root}["]`);
    },

    resolveErrorNode(form, input, field) {
        const root = field.split('.')[0];
        const candidates = Array.from(new Set([field, `${root}[]`, root]));

        for (const candidate of candidates) {
            const node = form.querySelector(`[data-error-for="${candidate.replace(/"/g, '\\"')}"]`);
            if (node) {
                return node;
            }
        }

        if (!input) {
            return null;
        }

        const node = document.createElement('div');
        node.className = 'invalid-feedback d-block';
        node.dataset.errorFor = field;
        node.dataset.generatedError = 'true';

        this.feedbackAnchor(input).insertAdjacentElement('afterend', node);

        return node;
    },

    markInvalid(input) {
        input.classList.add('is-invalid');

        const anchor = this.feedbackAnchor(input);
        if (anchor && anchor !== input) {
            anchor.classList.add('is-invalid');
        }
    },

    feedbackAnchor(input) {
        if (input.nextElementSibling?.classList?.contains('note-editor')) {
            return input.nextElementSibling;
        }

        const filepondRoot = input.closest('.filepond--root');
        if (filepondRoot) {
            return filepondRoot;
        }

        if (input.nextElementSibling?.classList?.contains('filepond--root')) {
            return input.nextElementSibling;
        }

        return input;
    },

    formatBytes(bytes) {
        const value = Number(bytes || 0);

        if (value <= 0) {
            return 'Tamanho não informado';
        }

        const units = ['B', 'KB', 'MB', 'GB'];
        const index = Math.min(Math.floor(Math.log(value) / Math.log(1024)), units.length - 1);
        const amount = value / (1024 ** index);

        return `${amount.toLocaleString('pt-BR', {
            minimumFractionDigits: index === 0 ? 0 : 1,
            maximumFractionDigits: index === 0 ? 0 : 1,
        })} ${units[index]}`;
    },

    fileExtension(name, type = '') {
        const cleanName = String(name || '').split('?')[0];
        const fromName = cleanName.includes('.') ? cleanName.split('.').pop() : '';

        if (fromName) {
            return fromName.toUpperCase();
        }

        const fromType = String(type || '').split('/').pop();

        return (fromType || 'MIDIA').toUpperCase();
    },

    mediaKind(mimeType = '', extension = '') {
        const type = String(mimeType || '').toLowerCase();
        const ext = String(extension || '').toLowerCase();

        if (type.startsWith('image/') || ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico'].includes(ext)) {
            return { key: 'image', label: 'Imagem', icon: 'bi-file-earmark-image' };
        }

        if (type.startsWith('video/') || ['mp4', 'webm', 'mov', 'avi', 'mkv'].includes(ext)) {
            return { key: 'video', label: 'Vídeo', icon: 'bi-file-earmark-play' };
        }

        if (type.startsWith('audio/') || ['mp3', 'wav', 'ogg', 'm4a'].includes(ext)) {
            return { key: 'audio', label: 'Áudio', icon: 'bi-file-earmark-music' };
        }

        if (type === 'application/pdf' || ext === 'pdf') {
            return { key: 'pdf', label: 'PDF', icon: 'bi-file-earmark-pdf' };
        }

        if (['doc', 'docx', 'odt', 'rtf'].includes(ext)) {
            return { key: 'document', label: 'Documento', icon: 'bi-file-earmark-word' };
        }

        if (['xls', 'xlsx', 'csv', 'ods'].includes(ext)) {
            return { key: 'sheet', label: 'Planilha', icon: 'bi-file-earmark-spreadsheet' };
        }

        if (['zip', 'rar', '7z'].includes(ext)) {
            return { key: 'archive', label: 'Compactado', icon: 'bi-file-earmark-zip' };
        }

        return { key: 'file', label: 'Arquivo', icon: 'bi-file-earmark' };
    },

    currentUploadInfo(input) {
        const explicitUrl = input.dataset.currentUrl || '';
        const explicitName = input.dataset.currentName || '';
        const explicitType = input.dataset.currentType || '';
        const explicitSize = input.dataset.currentSize || '';

        if (explicitUrl) {
            return {
                current: true,
                url: explicitUrl,
                name: explicitName || explicitUrl.split('/').pop(),
                type: explicitType,
                size: Number(explicitSize || 0),
            };
        }

        const currentLink = input.parentElement?.querySelector('a[href]');

        if (!currentLink) {
            return null;
        }

        return {
            current: true,
            url: currentLink.href,
            name: currentLink.textContent.trim() || currentLink.href.split('/').pop(),
            type: '',
            size: 0,
        };
    },

    selectedUploadFiles(form) {
        return Array.from(form?.querySelectorAll?.('[data-filepond]') || [])
            .flatMap((input) => input._adminFilePond?.getFiles?.() || [])
            .map((item) => item.file)
            .filter(Boolean);
    },

    revokeUploadPreviewUrls(input) {
        (input._adminUploadPreviewUrls || []).forEach((url) => URL.revokeObjectURL(url));
        input._adminUploadPreviewUrls = [];
    },

    uploadPreviewSource(input, file) {
        const kind = this.mediaKind(file.type, this.fileExtension(file.name, file.type));

        if (!['image', 'video', 'audio'].includes(kind.key)) {
            return null;
        }

        const url = URL.createObjectURL(file);
        input._adminUploadPreviewUrls = input._adminUploadPreviewUrls || [];
        input._adminUploadPreviewUrls.push(url);

        return url;
    },

    renderUploadPreviewMedia(item, kind) {
        const source = item.url ? this.escapeHtml(item.url) : '';

        if (source && kind.key === 'image') {
            return `<img src="${source}" alt="${this.escapeHtml(item.name)}">`;
        }

        if (source && kind.key === 'video') {
            return `<video src="${source}" muted playsinline controls></video>`;
        }

        if (source && kind.key === 'audio') {
            return `<i class="bi ${kind.icon}"></i>`;
        }

        return `<i class="bi ${kind.icon}"></i>`;
    },

    renderUploadPreview(input, fileItems = []) {
        const panel = input._adminUploadPreviewPanel;

        if (!panel) {
            return;
        }

        this.revokeUploadPreviewUrls(input);

        const files = fileItems
            .map((item) => item.file)
            .filter(Boolean)
            .map((file) => ({
                current: false,
                name: file.name,
                type: file.type,
                size: file.size,
                url: this.uploadPreviewSource(input, file),
            }));
        const current = files.length === 0 ? this.currentUploadInfo(input) : null;
        const items = current ? [current] : files;

        if (items.length === 0) {
            panel.dataset.state = 'empty';
            panel.innerHTML = `
                <div class="admin-upload-preview-empty">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <div>
                        <strong>Aguardando mídia</strong>
                        <span>A prévia será exibida antes, durante e depois da seleção.</span>
                    </div>
                </div>
            `;
            return;
        }

        panel.dataset.state = current ? 'current' : 'ready';
        panel.innerHTML = items.map((item) => {
            const extension = this.fileExtension(item.name, item.type);
            const kind = this.mediaKind(item.type, extension);
            const status = item.current ? 'Arquivo atual' : 'Pronto para envio';
            const size = item.size ? this.formatBytes(item.size) : 'Tamanho não informado';
            const audio = item.url && kind.key === 'audio'
                ? `<audio src="${this.escapeHtml(item.url)}" controls></audio>`
                : '';

            return `
                <div class="admin-upload-preview-item" data-upload-preview-item>
                    <div class="admin-upload-preview-media admin-upload-preview-media-${kind.key}">
                        ${this.renderUploadPreviewMedia(item, kind)}
                    </div>
                    <div class="admin-upload-preview-info">
                        <div class="admin-upload-preview-title">
                            <strong title="${this.escapeHtml(item.name)}">${this.escapeHtml(item.name)}</strong>
                            <span>${kind.label}</span>
                        </div>
                        <div class="admin-upload-preview-meta">
                            <span class="admin-upload-extension">${this.escapeHtml(extension)}</span>
                            <span>${this.escapeHtml(size)}</span>
                            <span data-upload-status>${status}</span>
                        </div>
                        ${audio}
                        <div class="admin-upload-item-progress">
                            <span data-upload-item-progress style="width: 0%"></span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    },

    enhanceUploadInput(input, pond) {
        const root = input.nextElementSibling?.classList?.contains('filepond--root')
            ? input.nextElementSibling
            : input.closest('.filepond--root');
        const panel = document.createElement('div');
        panel.className = 'admin-upload-preview-panel';
        panel.dataset.uploadPreview = 'true';

        if (root) {
            root.insertAdjacentElement('afterend', panel);
        } else {
            input.insertAdjacentElement('afterend', panel);
        }

        input._adminFilePond = pond;
        input._adminUploadPreviewPanel = panel;

        const render = () => this.renderUploadPreview(input, pond.getFiles());

        pond.on('addfile', render);
        pond.on('removefile', render);
        pond.on('updatefiles', render);
        render();
    },

    markUploadPreviewsState(form, state, percent = 0) {
        const statusMap = {
            uploading: percent > 0 ? `Enviando ${percent}%` : 'Preparando envio',
            done: 'Upload concluído',
            error: 'Falha no envio',
        };

        form.querySelectorAll('[data-filepond]').forEach((input) => {
            const panel = input._adminUploadPreviewPanel;

            if (!panel || panel.dataset.state === 'empty' || panel.dataset.state === 'current') {
                return;
            }

            panel.dataset.state = state;
            panel.querySelectorAll('[data-upload-status]').forEach((node) => {
                node.textContent = statusMap[state] || 'Pronto para envio';
            });
            panel.querySelectorAll('[data-upload-item-progress]').forEach((bar) => {
                bar.style.width = `${state === 'done' ? 100 : Math.max(0, Math.min(100, percent))}%`;
            });
        });
    },

    updateProgress(percent, etaSeconds, form = null) {
        const card = document.getElementById('admin-upload-progress');
        const bar = card.querySelector('[data-progress-bar]');
        const percentLabel = card.querySelector('[data-progress-percent]');
        const etaLabel = card.querySelector('[data-progress-eta]');
        const summary = card.querySelector('[data-progress-summary]');
        const files = this.selectedUploadFiles(form);

        card.classList.add('active');
        bar.style.width = `${percent}%`;
        percentLabel.textContent = `${percent}%`;
        
        let etaText = 'Finalizando upload...';
        if (etaSeconds > 0) {
            const minutes = Math.floor(etaSeconds / 60);
            const seconds = etaSeconds % 60;
            etaText = `Tempo restante: ${minutes > 0 ? `${minutes}m ` : ''}${seconds}s`;
        }
        
        etaLabel.textContent = etaText;
        
        const count = files.length;
        const countText = count > 1 ? `<div class="mt-1 fw-bold text-primary small">${count} arquivos sendo enviados</div>` : '';
        
        summary.innerHTML = files.slice(0, 3).map((file) => {
            const extension = this.fileExtension(file.name, file.type);

            return `
                <span>
                    <strong>${this.escapeHtml(extension)}</strong>
                    ${this.escapeHtml(file.name)}
                </span>
            `;
        }).join('') + countText;
    },

    hideProgress() {
        const card = document.getElementById('admin-upload-progress');
        const bar = card.querySelector('[data-progress-bar]');
        const percentLabel = card.querySelector('[data-progress-percent]');
        const etaLabel = card.querySelector('[data-progress-eta]');
        const summary = card.querySelector('[data-progress-summary]');

        bar.style.width = '0%';
        percentLabel.textContent = '0%';
        etaLabel.textContent = 'Calculando tempo restante...';
        summary.innerHTML = '';
        card.classList.remove('active');
    },

    refetchCalendar(target) {
        this.hideCalendarEventPanel();
        const calendarElement = typeof target === 'string' ? document.querySelector(target) : target;

        if (calendarElement?._fullCalendar) {
            calendarElement._fullCalendar.refetchEvents();
        }
    },

    initPlugins(scope) {
        applyAutoPlaceholders(scope);
        this.initCharts(scope);
        this.initCalendars(scope);

        scope.querySelectorAll('[data-editor="summernote"]').forEach((element) => {
            if (element.dataset.editorReady) {
                return;
            }

            if (typeof $.fn?.summernote !== 'function') {
                if (!this.summernoteWarningShown) {
                    console.warn('Summernote is unavailable. Textareas will remain editable without rich text controls.');
                    this.summernoteWarningShown = true;
                }
                return;
            }

            try {
                $(element).summernote({
                    height: Number(element.dataset.editorHeight || 320),
                    lang: 'pt-BR',
                    dialogsInBody: true,
                    placeholder: element.getAttribute('placeholder') || '',
                    fontNames: ['Arial', 'Segoe UI', 'Roboto', 'Times New Roman', 'Georgia', 'Courier New'],
                    styleTags: ['p', 'blockquote', 'pre', 'h2', 'h3', 'h4'],
                    toolbar: [
                        ['style', ['style']],
                        ['fontname', ['fontname']],
                        ['fontsize', ['fontsize']],
                        ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
                        ['color', ['forecolor', 'backcolor']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['height', ['height']],
                        ['table', ['table']],
                        ['insert', ['link', 'picture', 'video', 'hr']],
                        ['view', ['fullscreen', 'codeview', 'help']],
                    ],
                    callbacks: {
                        onChange: () => {
                            element.dispatchEvent(new Event('change', { bubbles: true }));
                        },
                    },
                });

                element.dataset.editorReady = 'true';
            } catch (error) {
                console.error('Summernote initialization failed.', error);
            }
        });

        scope.querySelectorAll('[data-filepond]').forEach((input) => {
            if (input.dataset.filepondReady) {
                return;
            }

            try {
                const pond = FilePond.create(input, {
                    allowMultiple: input.hasAttribute('multiple'),
                    credits: false,
                    storeAsFile: true,
                    acceptedFileTypes: input.dataset.accepted ? input.dataset.accepted.split(',') : null,
                    labelIdle: 'Arraste e solte ou <span class="filepond--label-action">selecione arquivos</span>',
                    labelFileTypeNotAllowed: 'Tipo de arquivo não permitido',
                    fileValidateTypeLabelExpectedTypes: 'Tipos aceitos: {allTypes}',
                    labelTapToCancel: 'toque para cancelar',
                    labelTapToRetry: 'toque para tentar novamente',
                    labelTapToUndo: 'toque para desfazer',
                });

                this.enhanceUploadInput(input, pond);
                input.dataset.filepondReady = 'true';
            } catch (error) {
                console.error('FilePond initialization failed.', error);
            }
        });

        scope.querySelectorAll('[data-mask]').forEach((input) => {
            if (input.dataset.maskReady) {
                return;
            }

            const mask = input.dataset.mask;
            const config = {
                phone: { mask: ['(99) 9999-9999', '(99) 99999-9999'] },
                cep: { mask: '99999-999' },
                cpf: { mask: '999.999.999-99' },
                cnpj: { mask: '99.999.999/9999-99' },
                'cpf-cnpj': { mask: ['999.999.999-99', '99.999.999/9999-99'] },
                cnj: { mask: '9999999-99.9999.9.99.9999' },
                time: { mask: '99:99' },
                date: { mask: '99/99/9999' },
                currency: { alias: 'currency', prefix: 'R$ ', groupSeparator: '.', radixPoint: ',', digits: 2, autoGroup: true },
            };

            if (config[mask]) {
                Inputmask(config[mask]).mask(input);
                input.dataset.maskReady = 'true';
            }
        });

        scope.querySelectorAll('[data-cep-autofill]').forEach((input) => {
            if (input.dataset.cepReady) {
                return;
            }

            input.addEventListener('blur', async () => {
                const cep = input.value.replace(/\D/g, '');
                if (cep.length !== 8) {
                    return;
                }

                try {
                    const response = await window.axios.get(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = response.data;
                    if (data.erro) {
                        return;
                    }

                    const form = input.closest('form');
                    const map = {
                        logradouro: form?.querySelector('[name="address_street"]'),
                        bairro: form?.querySelector('[name="address_district"]'),
                        localidade: form?.querySelector('[name="address_city"]'),
                        uf: form?.querySelector('[name="address_state"]'),
                    };

                    Object.entries(map).forEach(([key, field]) => {
                        if (field && !field.value) {
                            field.value = data[key] || '';
                        }
                    });
                } catch (error) {
                    this.showToast('warning', 'Não foi possível consultar o CEP automaticamente.');
                }
            });

            input.dataset.cepReady = 'true';
        });
    },

    initCharts(scope) {
        scope.querySelectorAll('[data-admin-chart]').forEach((canvas) => {
            if (canvas.dataset.chartReady) {
                return;
            }

            try {
                const config = JSON.parse(canvas.dataset.adminChart || '{}');
                const chart = new window.Chart(canvas, {
                    ...config,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        ...(config.options || {}),
                    },
                });

                canvas._adminChart = chart;
                canvas.dataset.chartReady = 'true';
            } catch (error) {
                this.showToast('warning', 'Não foi possível inicializar um gráfico do painel.');
            }
        });
    },

    initCalendars(scope) {
        const fullCalendar = window.FullCalendar;

        if (!fullCalendar?.Calendar) {
            return;
        }

        const calendars = scope.matches?.('[data-calendar]')
            ? [scope]
            : Array.from(scope.querySelectorAll('[data-calendar]'));

        calendars.forEach((element) => {
            if (element._fullCalendar) {
                return;
            }

            const compactQuery = window.matchMedia('(max-width: 767.98px)');
            const isCompact = () => compactQuery.matches;
            const eventsUrl = element.dataset.eventsUrl;

            if (!eventsUrl) {
                return;
            }

            const readFilters = () => {
                const form = document.querySelector(element.dataset.calendarToolbar);
                const params = {};

                if (!form) {
                    return params;
                }

                new FormData(form).forEach((value, key) => {
                    const normalized = typeof value === 'string' ? value.trim() : value;

                    if (normalized !== '' && normalized !== null && normalized !== undefined) {
                        params[key] = normalized;
                    }
                });

                return params;
            };

            const resolveHeight = () => isCompact()
                ? 'auto'
                : Number(element.dataset.calendarHeight || 650);
            const resolveContentHeight = () => isCompact()
                ? 'auto'
                : Number(element.dataset.calendarContentHeight || 590);

            const calendar = new fullCalendar.Calendar(element, {
                plugins: fullCalendar.plugins,
                locales: [fullCalendar.locales['pt-br']],
                locale: 'pt-br',
                timeZone: 'local',
                initialView: isCompact() ? 'listWeek' : 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: isCompact()
                        ? 'dayGridMonth,listWeek'
                        : 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
                },
                buttonText: {
                    today: 'Hoje',
                    month: 'Mês',
                    week: 'Semana',
                    day: 'Dia',
                    list: 'Lista',
                },
                allDayText: 'Dia inteiro',
                noEventsMessage: 'Nenhum evento encontrado.',
                height: resolveHeight(),
                contentHeight: resolveContentHeight(),
                fixedWeekCount: false,
                showNonCurrentDates: true,
                dayMaxEvents: isCompact() ? 2 : 4,
                editable: true,
                eventStartEditable: true,
                eventDurationEditable: true,
                selectable: true,
                selectMirror: true,
                nowIndicator: true,
                navLinks: true,
                businessHours: true,
                events: (fetchInfo, successCallback, failureCallback) => {
                    const url = new URL(eventsUrl, window.location.origin);
                    const params = {
                        start: fetchInfo.startStr,
                        end: fetchInfo.endStr,
                        timeZone: fetchInfo.timeZone,
                        ...readFilters(),
                    };

                    Object.entries(params).forEach(([key, value]) => {
                        if (value !== '' && value !== null && value !== undefined) {
                            url.searchParams.set(key, value);
                        }
                    });

                    window.axios.get(url.toString())
                        .then((response) => successCallback(response.data || []))
                        .catch((error) => {
                            this.showToast('error', 'Não foi possível carregar a agenda.');
                            failureCallback(error);
                        });
                },
                loading: (loading) => {
                    element.classList.toggle('is-loading', loading);
                },
                select: (info) => {
                    const createUrl = element.dataset.createUrl;

                    if (!createUrl) {
                        calendar.unselect();
                        return;
                    }

                    const url = new URL(createUrl, window.location.origin);
                    url.searchParams.set('start', info.startStr);
                    url.searchParams.set('end', info.endStr);
                    url.searchParams.set('all_day', info.allDay ? '1' : '0');

                    this.loadModal(url.toString(), 'Novo evento');
                    calendar.unselect();
                },
                eventClick: (info) => {
                    info.jsEvent.preventDefault();
                    this.showCalendarEventPanel(info.event, info.jsEvent);
                },
                eventContent: (info) => this.renderCalendarEventContent(info),
                eventDidMount: (info) => {
                    this.decorateCalendarEvent(info);
                },
                eventDrop: (info) => {
                    this.updateCalendarEventPosition(info, element);
                },
                eventResize: (info) => {
                    this.updateCalendarEventPosition(info, element);
                },
            });

            element.classList.toggle('is-compact', isCompact());
            element._fullCalendar = calendar;
            calendar.render();
            window.adminCalendar = calendar;

            const syncCompactMode = () => {
                const compact = isCompact();
                element.classList.toggle('is-compact', compact);

                if (typeof calendar.setOption === 'function') {
                    calendar.setOption('height', resolveHeight());
                    calendar.setOption('contentHeight', resolveContentHeight());
                    calendar.setOption('dayMaxEvents', compact ? 2 : 4);
                }

                if (compact && calendar.view?.type !== 'listWeek') {
                    calendar.changeView('listWeek');
                    return;
                }

                if (!compact && calendar.view?.type === 'listWeek') {
                    calendar.changeView('dayGridMonth');
                    return;
                }

                calendar.updateSize();
            };

            if (typeof compactQuery.addEventListener === 'function') {
                compactQuery.addEventListener('change', syncCompactMode);
            } else if (typeof compactQuery.addListener === 'function') {
                compactQuery.addListener(syncCompactMode);
            }

            window.addEventListener('resize', () => {
                window.clearTimeout(element._calendarResizeTimer);
                element._calendarResizeTimer = window.setTimeout(() => calendar.updateSize(), 180);
            }, { passive: true });
        });
    },

    renderCalendarEventContent(info) {
        const event = info.event;
        const props = event.extendedProps || {};
        const display = props.display || event.display || 'auto';

        if (display === 'background' || display === 'inverse-background') {
            return undefined;
        }

        const timeText = info.timeText
            ? `<span class="admin-calendar-event-time">${this.escapeHtml(info.timeText)}</span>`
            : '';
        const category = props.category
            ? `<span class="admin-calendar-event-chip">${this.escapeHtml(props.category)}</span>`
            : '';

        return {
            html: `
            <div class="admin-calendar-event-shell">
                <div class="admin-calendar-event-heading">
                    ${timeText}
                    <span class="admin-calendar-event-title">${this.escapeHtml(event.title)}</span>
                </div>
                ${category ? `<div class="admin-calendar-event-meta">${category}</div>` : ''}
            </div>
            `,
        };
    },

    decorateCalendarEvent(info) {
        const event = info.event;
        const props = event.extendedProps || {};
        const status = props.status || 'scheduled';
        const display = props.display || event.display || 'auto';

        info.el.setAttribute('data-status', status);
        info.el.setAttribute('data-display', display);

        if (props.statusLabel || props.owner || props.category) {
            info.el.setAttribute('title', [event.title, props.statusLabel, props.owner, props.category].filter(Boolean).join(' - '));
        }
    },

    calendarDateForRequest(date, allDay) {
        if (!date) {
            return null;
        }

        if (!allDay) {
            return date.toISOString();
        }

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day} 00:00:00`;
    },

    async updateCalendarEventPosition(info, element) {
        const event = info.event;
        const moveUrl = event.extendedProps?.moveUrl;

        if (!moveUrl) {
            info.revert();
            return;
        }

        try {
            await window.axios.patch(moveUrl, {
                start_at: this.calendarDateForRequest(event.start, event.allDay),
                end_at: this.calendarDateForRequest(event.end, event.allDay),
                all_day: event.allDay ? 1 : 0,
            });

            this.showToast('success', 'Agenda atualizada.');

            const table = document.querySelector(element.dataset.recordsTarget);
            this.refreshTable(table);
            element._fullCalendar?.refetchEvents();
        } catch (error) {
            info.revert();
            this.showToast('error', error.response?.data?.message || 'Não foi possível mover o evento.');
        }
    },

    bindTourGuide() {
        const body = document.body;
        const onboardingCompleted = body.dataset.onboardingCompleted === 'true';
        const role = body.dataset.userRole;
        const onboardingUrl = body.dataset.onboardingUrl;

        if (onboardingCompleted || !window.driver || !onboardingUrl) {
            return;
        }

        const driverObj = window.driver.js.driver({
            showProgress: true,
            allowClose: false,
            nextBtnText: 'Próximo',
            prevBtnText: 'Anterior',
            doneBtnText: 'Finalizar',
            progressText: 'Passo {{current}} de {{total}}',
            onDeselected: (element, step, { config, state }) => {
                if (state.activeIndex === config.steps.length - 1) {
                    this.markOnboardingAsCompleted(onboardingUrl);
                }
            },
            onDestroyed: () => {
                this.markOnboardingAsCompleted(onboardingUrl);
            }
        });

        const commonSteps = [
            { 
                element: '.app-header', 
                popover: { 
                    title: 'Barra de Ferramentas', 
                    description: 'Aqui você encontra atalhos rápidos para o site, alternância de tema e as configurações do seu perfil.', 
                    side: "bottom", 
                    align: 'start' 
                } 
            },
            { 
                element: '.app-sidebar', 
                popover: { 
                    title: 'Menu de Navegação', 
                    description: 'Toda a inteligência do sistema está organizada nestes módulos. Explore as seções de acordo com seu acesso.', 
                    side: "right", 
                    align: 'start' 
                } 
            },
        ];

        const roleSteps = role === 'Super Admin' || role === 'Administrador'
            ? [
                { 
                    element: '[href*="system-settings"]', 
                    popover: { 
                        title: 'Configurações Estratégicas', 
                        description: 'Como administrador, você pode ajustar a marca, SEO, PWA e integrações de segurança por aqui.', 
                        side: "right", 
                        align: 'start' 
                    } 
                },
                { 
                    element: '[href*="users"]', 
                    popover: { 
                        title: 'Gestão de Usuários', 
                        description: 'Controle quem tem acesso ao sistema e defina permissões específicas para cada colaborador.', 
                        side: "right", 
                        align: 'start' 
                    } 
                }
            ]
            : [
                { 
                    element: '[href*="calendar"]', 
                    popover: { 
                        title: 'Sua Agenda', 
                        description: 'Organize seus prazos e compromissos jurídicos em nosso calendário interativo.', 
                        side: "right", 
                        align: 'start' 
                    } 
                },
                { 
                    element: '[href*="legal-cases"]', 
                    popover: { 
                        title: 'Processos Judiciais', 
                        description: 'Gerencie todos os seus casos, sincronize movimentações via DataJud e anexe documentos.', 
                        side: "right", 
                        align: 'start' 
                    } 
                }
            ];

        const finalStep = [
            { 
                element: '.admin-app-footer', 
                popover: { 
                    title: 'Tudo pronto!', 
                    description: 'Agora você conhece o básico. Caso tenha dúvidas, acesse o menu "Documentação" no final da barra lateral.', 
                    side: "top", 
                    align: 'center' 
                } 
            }
        ];

        driverObj.setSteps([...commonSteps, ...roleSteps, ...finalStep]);
        
        window.setTimeout(() => {
            driverObj.drive();
        }, 1500);
    },

    async markOnboardingAsCompleted(url) {
        try {
            await window.axios.post(url);
            document.body.dataset.onboardingCompleted = 'true';
        } catch (error) {
            console.error('Falha ao marcar onboarding como concluído.', error);
        }
    },
};

window.AdminUI = AdminUI;

const adminPluginsReady = Promise.allSettled([
    import('admin-lte'),
    import('summernote/dist/summernote-lite.min.js')
        .then(() => import('summernote/dist/lang/summernote-pt-BR.min.js')),
]).then((results) => {
    results
        .filter((result) => result.status === 'rejected')
        .forEach((result) => console.error('Admin plugin failed to load.', result.reason));
});

document.addEventListener('DOMContentLoaded', async () => {
    try {
        await adminPluginsReady;
    } finally {
        AdminUI.boot();
    }
});
