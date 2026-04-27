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

window.bootstrap = bootstrap;
window.$ = window.jQuery = $;
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

toastr.options = {
    closeButton: true,
    newestOnTop: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    timeOut: 4000,
};

const AdminUI = {
    modalInstance: null,
    summernoteWarningShown: false,

    boot() {
        this.ensureModal();
        this.ensureProgressCard();
        this.flushPageToasts();
        this.bindDocumentEvents();
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
            </div>
        `;
        document.body.appendChild(wrapper);
    },

    bindDocumentEvents() {
        document.addEventListener('click', (event) => {
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

            const calendarReset = event.target.closest('[data-calendar-reset]');
            if (calendarReset) {
                window.setTimeout(() => {
                    const form = calendarReset.closest('form');
                    const calendar = document.querySelector(form?.dataset.calendarToolbar || '#admin-calendar');
                    this.refetchCalendar(calendar);
                }, 0);
                return;
            }

            const paginationLink = event.target.closest('[data-ajax-table] .pagination a');
            if (paginationLink) {
                event.preventDefault();
                const table = paginationLink.closest('[data-ajax-table]');
                this.refreshTable(table, paginationLink.href);
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
            clearTimeout(searchInput._searchTimer);
            searchInput._searchTimer = setTimeout(() => {
                this.refreshTable(table);
            }, 350);
        });

        document.addEventListener('change', (event) => {
            const filterInput = event.target.closest('[data-table-filter]');
            if (!filterInput) {
                return;
            }

            const table = document.querySelector(filterInput.dataset.tableTarget);
            this.refreshTable(table);
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
        const confirmResult = await Swal.fire({
            title: 'Confirmar exclusão?',
            text: trigger.dataset.confirmText || 'Essa ação não poderá ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Excluir',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
        });

        if (!confirmResult.isConfirmed) {
            return;
        }

        try {
            const response = await window.axios.delete(trigger.dataset.deleteUrl);
            this.showToast('success', response.data.message || 'Registro excluido com sucesso.');
            const table = document.querySelector(trigger.dataset.tableTarget);
            this.refreshTable(table);
            this.refetchCalendar(response.data.calendarTarget || trigger.dataset.calendarTarget);
        } catch (error) {
            this.showToast('error', error.response?.data?.message || 'Falha ao excluir o registro.');
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
                    const elapsedSeconds = Math.max(
                        1,
                        (Date.now() - (form._uploadStartedAt || (form._uploadStartedAt = Date.now()))) / 1000,
                    );
                    const speed = loaded / elapsedSeconds;
                    const eta = speed > 0 && total > 0 ? Math.max(0, Math.round((total - loaded) / speed)) : 0;
                    this.updateProgress(percent, eta);
                },
            });

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
            delete form._uploadStartedAt;
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

    updateProgress(percent, etaSeconds) {
        const card = document.getElementById('admin-upload-progress');
        const bar = card.querySelector('[data-progress-bar]');
        const percentLabel = card.querySelector('[data-progress-percent]');
        const etaLabel = card.querySelector('[data-progress-eta]');

        card.classList.add('active');
        bar.style.width = `${percent}%`;
        percentLabel.textContent = `${percent}%`;
        etaLabel.textContent = etaSeconds > 0 ? `Tempo restante aproximado: ${etaSeconds}s` : 'Finalizando upload...';
    },

    hideProgress() {
        const card = document.getElementById('admin-upload-progress');
        const bar = card.querySelector('[data-progress-bar]');
        const percentLabel = card.querySelector('[data-progress-percent]');
        const etaLabel = card.querySelector('[data-progress-eta]');

        bar.style.width = '0%';
        percentLabel.textContent = '0%';
        etaLabel.textContent = 'Calculando tempo restante...';
        card.classList.remove('active');
    },

    refetchCalendar(target) {
        const calendarElement = typeof target === 'string' ? document.querySelector(target) : target;

        if (calendarElement?._fullCalendar) {
            calendarElement._fullCalendar.refetchEvents();
        }
    },

    initPlugins(scope) {
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
                FilePond.create(input, {
                    allowMultiple: input.hasAttribute('multiple'),
                    credits: false,
                    storeAsFile: true,
                    acceptedFileTypes: input.dataset.accepted ? input.dataset.accepted.split(',') : null,
                    labelIdle: 'Arraste e solte ou <span class="filepond--label-action">selecione arquivos</span>',
                });

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
        scope.querySelectorAll('[data-calendar]').forEach((element) => {
            if (element._fullCalendar) {
                return;
            }

            const toolbar = element.dataset.calendarToolbar ? document.querySelector(element.dataset.calendarToolbar) : null;
            const readFilters = () => {
                const params = {};

                if (!toolbar) {
                    return params;
                }

                new FormData(toolbar).forEach((value, key) => {
                    if (value) {
                        params[key] = value;
                    }
                });

                return params;
            };

            const updateEventPosition = async (info) => {
                const event = info.event;
                const moveUrl = event.extendedProps?.moveUrl;

                if (!moveUrl) {
                    info.revert();
                    return;
                }

                try {
                    await window.axios.patch(moveUrl, {
                        start_at: event.start ? event.start.toISOString() : null,
                        end_at: event.end ? event.end.toISOString() : null,
                        all_day: event.allDay ? 1 : 0,
                    });
                    this.showToast('success', 'Agenda atualizada.');
                } catch (error) {
                    info.revert();
                    this.showToast('error', error.response?.data?.message || 'Não foi possível mover o evento.');
                }
            };

            const calendar = new Calendar(element, {
                plugins: window.FullCalendar.plugins,
                locales: [window.FullCalendar.locales['pt-br']],
                locale: 'pt-br',
                timeZone: 'local',
                defaultView: 'dayGridMonth',
                height: Number(element.dataset.calendarHeight || 640),
                contentHeight: Number(element.dataset.calendarContentHeight || 560),
                aspectRatio: Number(element.dataset.calendarAspectRatio || 1.72),
                handleWindowResize: true,
                nowIndicator: true,
                weekNumbers: true,
                navLinks: true,
                selectable: true,
                selectMirror: true,
                editable: true,
                eventResizableFromStart: true,
                droppable: true,
                eventLimit: true,
                eventLimitClick: 'popover',
                businessHours: true,
                allDaySlot: true,
                displayEventTime: true,
                displayEventEnd: true,
                slotDuration: '00:30:00',
                snapDuration: '00:15:00',
                minTime: '06:00:00',
                maxTime: '22:00:00',
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
                },
                buttonText: {
                    today: 'Hoje',
                    month: 'Mês',
                    week: 'Semana',
                    day: 'Dia',
                    list: 'Lista',
                },
                views: {
                    dayGridMonth: { eventLimit: 4 },
                    timeGridWeek: { slotEventOverlap: true },
                    timeGridDay: { slotEventOverlap: true },
                },
                eventSources: [{
                    url: element.dataset.calendarEventsUrl,
                    method: 'GET',
                    extraParams: readFilters,
                    failure: () => this.showToast('error', 'Não foi possível carregar a agenda.'),
                }],
                loading: (isLoading) => element.classList.toggle('is-loading', isLoading),
                select: (info) => {
                    const url = new URL(element.dataset.calendarCreateUrl, window.location.origin);
                    url.searchParams.set('start', info.startStr);
                    url.searchParams.set('end', info.endStr);
                    url.searchParams.set('all_day', info.allDay ? '1' : '0');
                    this.loadModal(url.toString(), 'Novo evento');
                    calendar.unselect();
                },
                eventClick: (info) => {
                    const editUrl = info.event.extendedProps?.editUrl;

                    if (editUrl) {
                        info.jsEvent.preventDefault();
                        this.loadModal(editUrl, info.event.title);
                    }
                },
                eventDrop: updateEventPosition,
                eventResize: updateEventPosition,
                eventRender: (info) => {
                    const props = info.event.extendedProps || {};
                    const details = [
                        info.event.title,
                        props.status ? `Status: ${props.status}` : '',
                        props.owner ? `Responsável: ${props.owner}` : '',
                        props.location ? `Local: ${props.location}` : '',
                        props.description || '',
                    ].filter(Boolean).join('\n');

                    if (details) {
                        info.el.setAttribute('title', details);
                    }

                    if (props.status) {
                        info.el.dataset.status = props.status;
                    }
                },
            });

            calendar.render();
            element._fullCalendar = calendar;

            toolbar?.querySelectorAll('[data-calendar-filter]').forEach((field) => {
                const eventName = field.tagName === 'INPUT' ? 'input' : 'change';
                field.addEventListener(eventName, () => {
                    window.clearTimeout(field._calendarTimer);
                    field._calendarTimer = window.setTimeout(() => calendar.refetchEvents(), 250);
                });
            });
        });
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
