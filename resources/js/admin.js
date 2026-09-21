import './bootstrap';

import * as bootstrap from 'bootstrap';
import $ from 'jquery';
import toastr from 'toastr';
import Swal from 'sweetalert2';
import Inputmask from 'inputmask';
import DataTable from 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';
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
import { applyAutoPlaceholders, bindDeviceAuditFields, configureToastr } from './shared/ui';

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
    notificationPollHandle: null,
    notificationLastId: 0,
    notificationUnreadCount: 0,
    notificationAudioUnlocked: false,
    notificationAudioContext: null,
    notificationFeedUrl: null,
    notificationMarkUrlTemplate: null,
    notificationFetchHandler: null,

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
        this.bindLiveClocks();
        this.bindDocumentEvents();
        this.bindSidebarTreeviewState();
        this.bindTourGuide();
        this.initNotificationCenter();
        this.initPlugins(document);
        bindDeviceAuditFields(document);
        this.initAjaxTables(document);
        initLegalDocumentDesigners(document);
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

    bindLiveClocks() {
        const clocks = Array.from(document.querySelectorAll('[data-live-clock]'));
        const timezoneSelect = document.querySelector('[data-user-timezone-select]');

        if (clocks.length === 0) {
            return;
        }

        const fallbackTimezone = 'America/Sao_Paulo';
        const formatterCache = new Map();

        const getTimezone = (clock) => (
            clock.dataset.liveClockTimezone
            || timezoneSelect?.value
            || document.body.dataset.userTimezone
            || Intl.DateTimeFormat().resolvedOptions().timeZone
            || fallbackTimezone
        );

        const getFormatters = (timezone) => {
            if (! formatterCache.has(timezone)) {
                try {
                    formatterCache.set(timezone, {
                        date: new Intl.DateTimeFormat('pt-BR', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            timeZone: timezone,
                        }),
                        time: new Intl.DateTimeFormat('pt-BR', {
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                            timeZone: timezone,
                        }),
                    });
                } catch (error) {
                    return getFormatters(fallbackTimezone);
                }
            }

            return formatterCache.get(timezone);
        };

        const syncClock = () => {
            const now = new Date();

            clocks.forEach((clock) => {
                const dateTarget = clock.querySelector('[data-live-clock-date]');
                const timeTarget = clock.querySelector('[data-live-clock-time]');
                const timezone = getTimezone(clock);
                const formatters = getFormatters(timezone);

                if (dateTarget) {
                    dateTarget.textContent = formatters.date.format(now);
                }

                if (timeTarget) {
                    timeTarget.textContent = formatters.time.format(now);
                }
            });
        };

        timezoneSelect?.addEventListener('change', () => {
            document.body.dataset.userTimezone = timezoneSelect.value;
            syncClock();
        });

        syncClock();
        window.setInterval(syncClock, 1000);
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
        document.addEventListener('pointerdown', () => {
            this.notificationAudioUnlocked = true;
        }, { once: true, passive: true });

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

            if (!event.target.closest('.fc-event, .fc-list-event, [data-calendar-event-panel]')) {
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

    bindSidebarTreeviewState() {
        const menu = document.querySelector('.admin-sidebar-menu');

        if (!menu || menu.dataset.adminSidebarReady === 'true') {
            return;
        }

        const items = Array.from(menu.querySelectorAll(':scope > .nav-item'));
        const syncExpandedStates = () => {
            items.forEach((item) => {
                const trigger = item.querySelector(':scope > .admin-sidebar-parent-link');
                trigger?.setAttribute('aria-expanded', item.classList.contains('menu-open') ? 'true' : 'false');
            });
        };

        items.forEach((item) => {
            item.addEventListener('expanded.lte.treeview', syncExpandedStates);
            item.addEventListener('collapsed.lte.treeview', syncExpandedStates);
        });

        syncExpandedStates();

        menu.dataset.adminSidebarReady = 'true';
    },

    initNotificationCenter() {
        const toggle = document.querySelector('[data-admin-notifications-toggle]');
        const badge = document.querySelector('[data-admin-notifications-badge]');
        const list = document.querySelector('[data-admin-notifications-list]');

        if (!toggle || !badge || !list) {
            return;
        }

        const feedUrl = toggle.dataset.notificationsFeedUrl || '/admin/contact-messages/notifications/feed';
        const indexUrl = toggle.dataset.notificationsIndexUrl || '/admin/contact-messages';
        this.notificationFeedUrl = feedUrl;
        this.notificationMarkUrlTemplate = toggle.dataset.notificationsMarkUrlTemplate || null;
        const refreshMessagesTable = () => {
            const table = document.querySelector('#admin-resource-table[data-ajax-table]');

            if (table && window.location.pathname.includes('/admin/contact-messages')) {
                this.refreshTable(table);
            }
        };

        const renderItems = (items) => {
            const unreadItems = Array.isArray(items)
                ? items.filter((item) => item.is_unread !== false)
                : [];

            if (unreadItems.length === 0) {
                list.innerHTML = `
                    <div class="admin-notification-empty">
                        <i class="bi bi-bell-slash"></i>
                        <strong>Nenhuma mensagem não lida.</strong>
                        <span>As novas entradas do formulário do site aparecerão aqui.</span>
                        <a href="${this.escapeHtml(indexUrl)}" class="btn btn-sm btn-outline-secondary">Ler todas</a>
                    </div>
                `;
                return;
            }

            list.innerHTML = unreadItems.map((item) => `
                <a class="admin-notification-item is-unread" href="${this.escapeHtml(item.manage_url)}" data-message-id="${this.escapeHtml(item.id)}" data-modal-url="${this.escapeHtml(item.manage_url)}" data-modal-title="Gerenciar mensagem">
                    <div class="admin-notification-item-top">
                        <strong>${this.escapeHtml(item.name)}</strong>
                        <time>${this.escapeHtml(item.created_at || '')}</time>
                    </div>
                    <span>${this.escapeHtml(item.email || item.phone || 'Contato não informado')}</span>
                    ${item.area_interest ? `<span class="admin-notification-status">${this.escapeHtml(item.area_interest)}</span>` : ''}
                    <p>${this.escapeHtml(item.message_excerpt || 'Nova mensagem recebida.')}</p>
                </a>
            `).join('');
        };
        const syncBadge = (count) => {
            const normalized = Number(count || 0);
            this.notificationUnreadCount = normalized;
            badge.textContent = String(normalized > 99 ? '99+' : normalized);
            badge.classList.toggle('d-none', normalized <= 0);
            toggle.classList.toggle('has-new', normalized > 0);
        };

        const playNotificationSound = () => {
            if (!this.notificationAudioUnlocked) {
                return;
            }

            try {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;

                if (!AudioContextClass) {
                    return;
                }

                const context = this.notificationAudioContext || new AudioContextClass();
                this.notificationAudioContext = context;
                context.resume?.();
                const oscillator = context.createOscillator();
                const gain = context.createGain();

                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(932, context.currentTime);
                gain.gain.setValueAtTime(0.0001, context.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.12, context.currentTime + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 0.32);

                oscillator.connect(gain);
                gain.connect(context.destination);
                oscillator.start();
                oscillator.stop(context.currentTime + 0.34);
            } catch (error) {
                console.warn('Nao foi possivel reproduzir o alerta sonoro.', error);
            }
        };

        const showDesktopNotification = (payload) => {
            if (!('Notification' in window) || Notification.permission !== 'granted') {
                return;
            }

            try {
                const notification = new Notification('Nova mensagem recebida', {
                    body: `${payload.name || 'Contato'}${payload.area_interest ? ` • ${payload.area_interest}` : ''}`,
                    tag: `contact-message-${payload.id}`,
                    renotify: true,
                });

                notification.onclick = () => {
                    window.focus();
                    this.loadModal(payload.manage_url, 'Gerenciar mensagem');
                    notification.close();
                };
            } catch (error) {
                console.warn('Falha ao exibir a notificacao desktop.', error);
            }
        };

        const fetchNotifications = async (silent = false) => {
            try {
                const requestUrl = new URL(feedUrl, window.location.origin);

                if (this.notificationLastId > 0) {
                    requestUrl.searchParams.set('since_id', String(this.notificationLastId));
                }

                const response = await window.axios.get(requestUrl.toString());
                const payload = response.data || {};
                const latestId = Number(payload.latest_id || 0);
                const newCount = Number(payload.new_count || 0);
                const unreadCount = Number(payload.unread_count || 0);

                renderItems(payload.items || []);
                syncBadge(unreadCount);

                if (latestId > this.notificationLastId) {
                    if (this.notificationLastId > 0 && newCount > 0 && !silent) {
                        this.showToast('info', `${newCount} nova(s) mensagem(ns) recebida(s) pelo formulário de contato.`);
                        playNotificationSound();
                        if (Array.isArray(payload.items) && payload.items.length > 0) {
                            showDesktopNotification(payload.items[0]);
                        }
                        refreshMessagesTable();
                    }

                    this.notificationLastId = latestId;
                }
            } catch (error) {
                console.warn('Falha ao atualizar o centro de notificacoes.', error);
            }
        };

        this.notificationFetchHandler = fetchNotifications;

        fetchNotifications(true);

        if (this.notificationPollHandle) {
            window.clearInterval(this.notificationPollHandle);
        }

        this.notificationPollHandle = window.setInterval(() => {
            fetchNotifications(false);
        }, 15000);

        if ('Notification' in window && Notification.permission === 'default') {
            toggle.addEventListener('click', () => {
                Notification.requestPermission().catch(() => null);
            }, { once: true });
        }
    },

    initAjaxTables(scope) {
        scope.querySelectorAll('[data-ajax-table]').forEach((table) => {
            if (table.dataset.ajaxManaged === 'inline') {
                return;
            }

            if (!table.dataset.loaded) {
                this.refreshTable(table);
            }
        });
    },

    initDataTables(scope) {
        scope.querySelectorAll('table[data-admin-datatable]').forEach((table) => {
            if (table.dataset.adminDatatableReady === 'true') {
                return;
            }

            const pageLength = Number(table.dataset.pageLength || 10);
            const enablePaging = table.dataset.datatablePaging === 'true';
            const enableSearch = table.dataset.datatableSearch === 'true';
            const nonOrderableTargets = Array.from(table.querySelectorAll('thead th.no-sort'))
                .map((header) => header.cellIndex)
                .filter((index) => index >= 0);

            new DataTable(table, {
                responsive: true,
                paging: enablePaging,
                searching: enableSearch,
                info: enablePaging,
                ordering: table.dataset.datatableOrdering !== 'false',
                pageLength,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                columnDefs: nonOrderableTargets.length ? [
                    { orderable: false, targets: nonOrderableTargets },
                ] : [],
                language: {
                    emptyTable: 'Nenhum registro encontrado.',
                    info: 'Mostrando _START_ até _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando 0 registros',
                    infoFiltered: '(filtrado de _MAX_ registros)',
                    lengthMenu: 'Mostrar _MENU_ registros',
                    loadingRecords: 'Carregando...',
                    processing: 'Processando...',
                    search: 'Pesquisar:',
                    zeroRecords: 'Nenhum registro correspondente encontrado.',
                    paginate: {
                        first: 'Primeiro',
                        last: 'Último',
                        next: 'Próximo',
                        previous: 'Anterior',
                    },
                    aria: {
                        orderable: 'Ordenar por esta coluna',
                        orderableReverse: 'Inverter ordenação desta coluna',
                    },
                },
                dom: table.dataset.datatableDom || 't',
            });

            table.dataset.adminDatatableReady = 'true';
        });
    },

    extractContactMessageId(url) {
        const match = String(url || '').match(/\/contact-messages\/(\d+)\/edit$/);
        return match ? Number(match[1]) : 0;
    },

    async markContactMessageViewed(messageId) {
        if (!messageId || !this.notificationMarkUrlTemplate) {
            return;
        }

        try {
            const endpoint = this.notificationMarkUrlTemplate.replace('__ID__', String(messageId));
            const response = await window.axios.patch(endpoint);
            const badge = document.querySelector('[data-admin-notifications-badge]');
            const toggle = document.querySelector('[data-admin-notifications-toggle]');
            const notificationItem = document.querySelector(`[data-message-id="${messageId}"]`);
            const unreadCount = Number(response.data?.unread_count || 0);

            if (notificationItem) {
                notificationItem.remove();
            }

            const notificationList = document.querySelector('[data-admin-notifications-list]');
            const hasUnreadItems = notificationList?.querySelector('[data-message-id]');

            if (notificationList && !hasUnreadItems) {
                notificationList.innerHTML = `
                    <div class="admin-notification-empty">
                        <i class="bi bi-bell-slash"></i>
                        <strong>Nenhuma mensagem não lida.</strong>
                        <span>As novas entradas do formulário do site aparecerão aqui.</span>
                        <a href="${this.escapeHtml(toggle?.dataset.notificationsIndexUrl || '/admin/contact-messages')}" class="btn btn-sm btn-outline-secondary">Ler todas</a>
                    </div>
                `;
            }

            if (badge) {
                badge.textContent = String(unreadCount > 99 ? '99+' : unreadCount);
                badge.classList.toggle('d-none', unreadCount <= 0);
            }

            if (toggle) {
                toggle.classList.toggle('has-new', unreadCount > 0);
            }

            this.notificationUnreadCount = unreadCount;
            this.notificationFetchHandler?.(true);
        } catch (error) {
            console.warn('Falha ao marcar a mensagem como lida.', error);
        }
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
            const modalUrl = new URL(url, window.location.origin);
            modalUrl.searchParams.set('_', String(Date.now()));

            const response = await window.axios.get(modalUrl.toString(), {
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    Pragma: 'no-cache',
                    Expires: '0',
                },
            });
            modal.querySelector('.modal-title').textContent = response.data.title || title;
            modal.querySelector('.modal-body').innerHTML = response.data.html;
            this.initPlugins(modal);

            const contactMessageId = this.extractContactMessageId(url);
            if (contactMessageId > 0) {
                this.markContactMessageViewed(contactMessageId);
            }
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
        this.appendFilePondFiles(form, formData);
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

            if (response.data.reload) {
                window.location.reload();
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
            .filter((item) => !this.isCurrentFilePondItem(item))
            .map((item) => item.file)
            .filter(Boolean);
    },

    isCurrentFilePondItem(item) {
        return item?.origin === FilePond.FileOrigin.LOCAL;
    },

    appendFilePondFiles(form, formData) {
        form.querySelectorAll('[data-filepond]').forEach((input) => {
            if (!input.name || !input._adminFilePond) {
                return;
            }

            const files = input._adminFilePond.getFiles()
                .filter((item) => !this.isCurrentFilePondItem(item))
                .map((item) => item.file)
                .filter((file) => file instanceof Blob);

            if (files.length === 0) {
                return;
            }

            formData.delete(input.name);
            files.forEach((file) => formData.append(input.name, file, file.name));
        });
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

        const items = fileItems
            .filter((item) => item.file)
            .map((item) => {
                const current = this.isCurrentFilePondItem(item);
                const explicit = current ? this.currentUploadInfo(input) : null;

                return {
                    current,
                    name: explicit?.name || item.file.name,
                    type: explicit?.type || item.file.type,
                    size: explicit?.size || item.file.size,
                    url: explicit?.url || this.uploadPreviewSource(input, item.file),
                };
            });

        if (items.length === 0) {
            const current = this.currentUploadInfo(input);
            if (current) {
                items.push(current);
            }
        }

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

        panel.dataset.state = items.every((item) => item.current) ? 'current' : 'ready';
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
        this.initDataTables(scope);

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
                const current = this.currentUploadInfo(input);
                const pond = FilePond.create(input, {
                    allowMultiple: input.hasAttribute('multiple'),
                    credits: false,
                    storeAsFile: true,
                    acceptedFileTypes: input.dataset.accepted ? input.dataset.accepted.split(',') : null,
                    files: current ? [{
                        source: current.url,
                        options: {
                            type: 'local',
                            file: {
                                name: current.name,
                                size: current.size,
                                type: current.type,
                            },
                            metadata: {
                                poster: current.url,
                            },
                        },
                    }] : [],
                    server: current ? {
                        load: (source, load, error, progress, abort) => {
                            const controller = new AbortController();

                            fetch(source, {
                                credentials: 'same-origin',
                                signal: controller.signal,
                            })
                                .then((response) => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP ${response.status}`);
                                    }

                                    const total = Number(response.headers.get('content-length') || 0);
                                    return response.blob().then((blob) => ({ blob, total }));
                                })
                                .then(({ blob, total }) => {
                                    progress(true, total || blob.size, total || blob.size);
                                    load(blob);
                                })
                                .catch((loadError) => {
                                    if (loadError.name !== 'AbortError') {
                                        error('Não foi possível carregar o arquivo atual.');
                                    }
                                });

                            return {
                                abort: () => {
                                    controller.abort();
                                    abort();
                                },
                            };
                        },
                    } : undefined,
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
                    const prefix = input.dataset.cepPrefix || '';
                    const map = {
                        logradouro: form?.querySelector(`[name="${prefix}address_street"]`),
                        bairro: form?.querySelector(`[name="${prefix}address_district"]`),
                        localidade: form?.querySelector(`[name="${prefix}address_city"]`),
                        uf: form?.querySelector(`[name="${prefix}address_state"]`),
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
                const chartFactory = window.Chart?.Chart || window.Chart;
                const context = typeof canvas.getContext === 'function'
                    ? canvas.getContext('2d')
                    : null;

                if (typeof chartFactory !== 'function' || !context) {
                    throw new Error('Chart.js indisponivel ou canvas sem contexto 2D.');
                }

                const config = JSON.parse(canvas.dataset.adminChart || '{}');
                const chart = new chartFactory(context, {
                    ...config,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        ...(config.options || {}),
                    },
                });

                canvas._adminChart = chart;
                canvas.dataset.chartReady = 'true';
                canvas.closest('.admin-chart-frame')?.classList.remove('is-chart-failed');

                window.requestAnimationFrame(() => {
                    chart.resize();
                    chart.update('none');
                });

                window.setTimeout(() => {
                    chart.resize();
                    chart.update('none');
                }, 180);
            } catch (error) {
                console.error('Falha ao inicializar grafico do painel.', error);

                const frame = canvas.closest('.admin-chart-frame');
                frame?.classList.add('is-chart-failed');

                if (frame && !frame.querySelector('.admin-chart-fallback')) {
                    frame.insertAdjacentHTML('beforeend', '<div class="admin-chart-fallback">Nao foi possivel carregar este grafico.</div>');
                }

                this.showToast('warning', 'Nao foi possivel inicializar um grafico do painel.');
            }
        });
    },

    initCalendars(scope) {
        const fullCalendar = window.FullCalendar;

        if (!fullCalendar?.Calendar) {
            scope.querySelectorAll?.('[data-calendar]')?.forEach((element) => {
                if (!element.dataset.calendarFailed) {
                    element.dataset.calendarFailed = 'true';
                    element.innerHTML = '<div class="admin-calendar-fallback">Agenda indisponível. Atualize a página para carregar o calendário.</div>';
                }
            });
            return;
        }

        const calendars = scope.matches?.('[data-calendar]')
            ? [scope]
            : Array.from(scope.querySelectorAll('[data-calendar]'));

        calendars.forEach((element) => {
            if (element.dataset.calendarManaged === 'inline') {
                return;
            }

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

            let calendar;

            try {
                const locale = fullCalendar.locales?.['pt-br'];

                calendar = new fullCalendar.Calendar(element, {
                    plugins: fullCalendar.plugins,
                    ...(locale ? { locales: [locale] } : {}),
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
                            .then((response) => {
                                const payload = Array.isArray(response.data)
                                    ? response.data
                                    : (Array.isArray(response.data?.data) ? response.data.data : []);

                                successCallback(payload);
                            })
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
                    datesSet: () => {
                        window.requestAnimationFrame(() => calendar?.updateSize());
                    },
                });
            } catch (error) {
                console.error('Falha ao inicializar FullCalendar.', error);
                element.dataset.calendarFailed = 'true';
                element.innerHTML = '<div class="admin-calendar-fallback">Não foi possível iniciar a agenda. Atualize a página.</div>';
                this.showToast('error', 'Não foi possível iniciar a agenda.');
                return;
            }

            element.classList.toggle('is-compact', isCompact());
            element._fullCalendar = calendar;
            window.adminCalendar = calendar;
            window.requestAnimationFrame(() => {
                calendar.render();
                calendar.updateSize();
                element.classList.add('is-ready');
            });

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
        const hasCustomColor = Boolean(props.hasCustomColor);

        info.el.setAttribute('data-status', hasCustomColor ? 'custom' : status);
        info.el.setAttribute('data-display', display);

        if (hasCustomColor) {
            const backgroundColor = event.backgroundColor || event.color || '#c49a3c';
            const textColor = event.textColor || '#111318';

            if (display === 'background' || display === 'inverse-background') {
                info.el.style.backgroundColor = backgroundColor;
                info.el.style.borderColor = backgroundColor;
                info.el.style.opacity = '0.34';
            } else {
                info.el.style.backgroundColor = backgroundColor;
                info.el.style.borderColor = backgroundColor;
                info.el.style.color = textColor;

                const shell = info.el.querySelector('.admin-calendar-event-shell');
                if (shell) {
                    shell.style.background = backgroundColor;
                    shell.style.borderColor = backgroundColor;
                    shell.style.color = textColor;
                }
            }
        }

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
        const userId = body.dataset.userId || 'usuario';
        const role = body.dataset.userRole;
        const onboardingUrl = body.dataset.onboardingUrl;

        if (!onboardingUrl) {
            return;
        }

        const commonSteps = [
            {
                element: '.app-header',
                popover: {
                    title: 'Barra de Ferramentas',
                    description: 'Aqui voce encontra atalhos rapidos para o site, alternancia de tema e as configuracoes do seu perfil.',
                    side: 'bottom',
                    align: 'start'
                }
            },
            {
                element: '.app-sidebar',
                popover: {
                    title: 'Menu de Navegacao',
                    description: 'Toda a inteligencia do sistema esta organizada nestes modulos. Explore as secoes de acordo com seu acesso.',
                    side: 'right',
                    align: 'start'
                }
            },
            {
                element: '.admin-page-hero, .app-content',
                popover: {
                    title: 'Area de Trabalho',
                    description: 'Este e o espaco principal onde ficam paineis, formularios, listas, graficos e atalhos do modulo atual.',
                    side: 'top',
                    align: 'center'
                }
            },
        ];

        const roleSteps = role === 'Super Admin' || role === 'Administrador'
            ? [
                {
                    element: '[href*="system-settings"]',
                    popover: {
                        title: 'Configuracoes Estrategicas',
                        description: 'Como administrador, voce pode ajustar a marca, SEO, PWA e integracoes de seguranca por aqui.',
                        side: 'right',
                        align: 'start'
                    }
                },
                {
                    element: '[href*="users"]',
                    popover: {
                        title: 'Gestao de Usuarios',
                        description: 'Controle quem tem acesso ao sistema e defina permissoes especificas para cada colaborador.',
                        side: 'right',
                        align: 'start'
                    }
                }
            ]
            : [
                {
                    element: '[href*="calendar"]',
                    popover: {
                        title: 'Sua Agenda',
                        description: 'Organize seus prazos e compromissos juridicos em nosso calendario interativo.',
                        side: 'right',
                        align: 'start'
                    }
                },
                {
                    element: '[href*="legal-cases"]',
                    popover: {
                        title: 'Processos Judiciais',
                        description: 'Gerencie seus casos, sincronize movimentacoes e anexe documentos.',
                        side: 'right',
                        align: 'start'
                    }
                }
            ];

        const contextualCandidates = [
            {
                selector: '#admin-calendar',
                popover: {
                    title: 'Agenda interativa',
                    description: 'Clique no dia para criar compromisso, arraste eventos para reagendar e clique em cada evento para editar ou excluir.',
                    side: 'left',
                    align: 'start',
                },
            },
            {
                selector: '#admin-calendar-events-table',
                popover: {
                    title: 'Gestao detalhada da agenda',
                    description: 'Aqui voce tem a listagem completa com filtros e acoes de manutencao dos eventos.',
                    side: 'top',
                    align: 'start',
                },
            },
            {
                selector: '[data-ajax-table]',
                popover: {
                    title: 'Lista dinamica',
                    description: 'Use pesquisa, filtros e paginacao sem recarregar a pagina para manter fluidez no trabalho.',
                    side: 'top',
                    align: 'start',
                },
            },
            {
                selector: '[data-ajax-form]',
                popover: {
                    title: 'Formulario inteligente',
                    description: 'Os envios sao processados em AJAX com validacoes e retorno imediato em notificacoes.',
                    side: 'left',
                    align: 'start',
                },
            },
            {
                selector: '[data-filepond]',
                popover: {
                    title: 'Upload com pre-visualizacao',
                    description: 'Arraste e solte arquivos para enviar com validacao e visualizacao antes do salvamento.',
                    side: 'left',
                    align: 'start',
                },
            },
            {
                selector: 'canvas',
                popover: {
                    title: 'KPI e analiticos',
                    description: 'Os graficos mostram desempenho e volume operacional com base nos dados do escritorio.',
                    side: 'top',
                    align: 'center',
                },
            },
            {
                selector: '.admin-notification-toggle',
                popover: {
                    title: 'Central de notificacoes',
                    description: 'O sino recebe novos contatos em tempo real sem precisar atualizar a pagina.',
                    side: 'bottom',
                    align: 'end',
                },
            },
            {
                selector: '.admin-tour-restart-button',
                popover: {
                    title: 'Reiniciar tour',
                    description: 'Use este botao sempre que quiser rever o passo a passo do painel.',
                    side: 'bottom',
                    align: 'end',
                },
            },
            {
                selector: '.admin-page-hero',
                popover: {
                    title: 'Cabecalho da secao',
                    description: 'Aqui voce ve contexto do modulo atual e acessa acoes principais dessa pagina.',
                    side: 'bottom',
                    align: 'start',
                },
            },
        ];

        const finalStep = [
            {
                element: '.admin-app-footer',
                popover: {
                    title: 'Tudo pronto!',
                    description: 'Agora voce conhece o basico. Use a opcao Reiniciar tour guiado no menu do usuario sempre que quiser rever este fluxo.',
                    side: 'top',
                    align: 'center'
                }
            }
        ];

        const contextualSteps = contextualCandidates
            .filter((item) => document.querySelector(item.selector))
            .map((item) => ({ element: item.selector, popover: item.popover }));

        const dedupe = new Set();
        const steps = [...commonSteps, ...roleSteps, ...contextualSteps, ...finalStep]
            .map((step) => {
                const selectors = String(step.element || '')
                    .split(',')
                    .map((selector) => selector.trim())
                    .filter(Boolean);
                const matchedSelector = selectors.find((selector) => document.querySelector(selector));

                if (!matchedSelector || dedupe.has(matchedSelector)) {
                    return null;
                }

                dedupe.add(matchedSelector);
                return { ...step, element: matchedSelector };
            })
            .filter(Boolean);

        if (steps.length === 0) {
            return;
        }

        const autoStorageKey = `admin-tour-auto-completed:${userId}`;
        let autoLaunched = false;
        const readAutoCompleted = () => {
            try {
                return window.localStorage.getItem(autoStorageKey) === 'true';
            } catch (error) {
                return false;
            }
        };
        const writeAutoCompleted = () => {
            try {
                window.localStorage.setItem(autoStorageKey, 'true');
            } catch (error) {
                // localStorage pode estar bloqueado; o banco continua sendo a fonte principal.
            }
        };

        const resolveDriverFactory = () => window.driver?.js?.driver || window.driver?.driver || null;
        const waitForDriver = () => new Promise((resolve, reject) => {
            const startedAt = Date.now();
            const tick = () => {
                const factory = resolveDriverFactory();

                if (factory) {
                    resolve(factory);
                    return;
                }

                if (Date.now() - startedAt > 5000) {
                    reject(new Error('Driver.js nao foi carregado.'));
                    return;
                }

                window.setTimeout(tick, 120);
            };

            tick();
        });

        const createDriver = async () => {
            const driverFactory = await waitForDriver();
            const driverObj = driverFactory({
                steps,
                showProgress: true,
                allowClose: true,
                overlayClickBehavior: 'close',
                nextBtnText: 'Proximo',
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

            return driverObj;
        };

        const launchTour = async ({ automatic = false } = {}) => {
            if (automatic) {
                autoLaunched = true;
                writeAutoCompleted();
                body.dataset.onboardingCompleted = 'true';
                this.markOnboardingAsCompleted(onboardingUrl);
            }

            const driverObj = await createDriver();
            driverObj.drive();
        };

        const restartTour = async () => {
            window.setTimeout(() => {
                launchTour().catch((error) => {
                    this.showToast('error', error.message || 'Nao foi possivel iniciar o tour guiado.');
                });
            }, 260);
        };

        const scheduleAutoTour = () => {
            if (autoLaunched || body.dataset.onboardingCompleted === 'true' || readAutoCompleted()) {
                return;
            }

            const fire = () => {
                window.requestAnimationFrame(() => {
                    window.setTimeout(() => {
                        launchTour({ automatic: true }).catch((error) => {
                            autoLaunched = false;
                            console.error('Falha ao iniciar tour guiado.', error);
                        });
                    }, 600);
                });
            };

            if (document.readyState === 'complete') {
                fire();
                return;
            }

            window.addEventListener('load', fire, { once: true });
        };

        document.querySelectorAll('[data-start-tour]').forEach((trigger) => {
            if (trigger.dataset.tourReady === 'true') {
                return;
            }

            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                launchTour().catch((error) => {
                    this.showToast('error', error.message || 'Nao foi possivel iniciar o tour guiado.');
                });
            });

            trigger.dataset.tourReady = 'true';
        });

        document.querySelectorAll('[data-restart-tour]').forEach((trigger) => {
            if (trigger.dataset.tourResetReady === 'true') {
                return;
            }

            trigger.addEventListener('click', async (event) => {
                event.preventDefault();
                event.stopPropagation();
                try {
                    await restartTour();
                } catch (error) {
                    this.showToast('error', error.response?.data?.message || 'Nao foi possivel reiniciar o tour guiado.');
                }
            });

            trigger.dataset.tourResetReady = 'true';
        });

        scheduleAutoTour();
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

function initLegalDocumentDesigners(scope = document) {
    scope.querySelectorAll('[data-document-designer]').forEach((designer) => {
        if (designer.dataset.ready === 'true') {
            return;
        }

        designer.dataset.ready = 'true';
        const form = designer.closest('form');
        const textarea = form?.querySelector('textarea[name="definition_json"]');
        const mirror = form?.querySelector('[data-doc-json-mirror]');
        const outputFormat = form?.querySelector('select[name="default_output_format"]');
        const pagesRoot = designer.querySelector('[data-doc-pages]');
        const inspector = designer.querySelector('.legal-doc-inspector');
        const fields = Array.from(designer.querySelectorAll('[data-doc-field]'));
        const bgPath = designer.querySelector('[data-doc-bg-path]');
        const bgOpacity = designer.querySelector('[data-doc-bg-opacity]');
        const bgFit = designer.querySelector('[data-doc-bg-fit]');
        const bgDrop = designer.querySelector('[data-doc-bg-drop]');
        const bgFile = designer.querySelector('[data-doc-bg-file]');
        const bgStatus = designer.querySelector('[data-doc-bg-status]');
        const gridVisible = designer.querySelector('[data-doc-grid-visible]');
        const snapGrid = designer.querySelector('[data-doc-snap-grid]');
        const gridSize = designer.querySelector('[data-doc-grid-size]');
        const marginsEnabled = designer.querySelector('[data-doc-margins-enabled]');
        const freePositioning = designer.querySelector('[data-doc-free-positioning]');
        const marginFields = Array.from(designer.querySelectorAll('[data-doc-margin]'));
        const uploadUrl = designer.dataset.backgroundUploadUrl || '';
        const csrfToken = designer.dataset.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
        const brandLogo = designer.dataset.brandLogo || '';
        const pageSize = { width: 210, height: 297 };
        let selected = { page: 0, id: null };
        const uniqueId = (type) => `${type}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 7)}`;
        const defaultGuides = () => ({
            grid: { visible: false, snap: false, size_mm: 5 },
            margins: { enabled: false, free_positioning: false, top_mm: 20, right_mm: 20, bottom_mm: 20, left_mm: 20 },
        });
        const defaultBackground = () => ({ color: '#ffffff', image_path: '', image_opacity: 0.08, image_fit: 'cover' });
        const cloneBackground = (page = null) => ({
            color: page?.background?.color || '#ffffff',
            image_path: page?.background?.image_path || '',
            image_opacity: Number(page?.background?.image_opacity ?? 0.08),
            image_fit: page?.background?.image_fit || 'cover',
        });

        const fallbackDefinition = (legacy = null) => {
            const legacyText = Array.isArray(legacy?.blocks)
                ? legacy.blocks.map((block) => {
                    if (block.type === 'list') {
                        return (block.items || []).map((item) => `- ${item}`).join('\n');
                    }
                    if (block.type === 'page_break') {
                        return '\n\n';
                    }
                    return block.text || '';
                }).filter(Boolean).join('\n\n')
                : 'Texto do documento';

            return ({
            layout: 'absolute',
            unit: 'mm',
            paper: { size: 'A4', width_mm: pageSize.width, height_mm: pageSize.height },
            guides: defaultGuides(),
            pages: [{
                width_mm: pageSize.width,
                height_mm: pageSize.height,
                background: defaultBackground(),
                elements: [{
                    id: uniqueId('texto'),
                    type: 'text',
                    x_mm: 24,
                    y_mm: 40,
                    w_mm: 162,
                    h_mm: 170,
                    text: legacyText,
                    font_size_pt: 11,
                    font_weight: '400',
                    line_height: 1.35,
                    align: 'justify',
                    color: '#111827',
                    opacity: 1,
                }],
            }],
        });
        };

        const normalizeGuides = (guides = {}) => {
            const defaults = defaultGuides();
            const grid = guides?.grid || {};
            const margins = guides?.margins || {};
            const bounded = (value, min, max) => {
                const numeric = Number(value);

                return Math.max(min, Math.min(max, Number.isFinite(numeric) ? numeric : min));
            };

            return {
                grid: {
                    visible: Boolean(grid.visible ?? defaults.grid.visible),
                    snap: Boolean(grid.snap ?? defaults.grid.snap),
                    size_mm: bounded(grid.size_mm ?? defaults.grid.size_mm, 1, 50),
                },
                margins: {
                    enabled: Boolean(margins.enabled ?? defaults.margins.enabled),
                    free_positioning: Boolean(margins.free_positioning ?? defaults.margins.free_positioning),
                    top_mm: bounded(margins.top_mm ?? defaults.margins.top_mm, 0, 120),
                    right_mm: bounded(margins.right_mm ?? defaults.margins.right_mm, 0, 120),
                    bottom_mm: bounded(margins.bottom_mm ?? defaults.margins.bottom_mm, 0, 120),
                    left_mm: bounded(margins.left_mm ?? defaults.margins.left_mm, 0, 120),
                },
            };
        };

        const normalizeDefinition = (value) => {
            let parsed = null;
            try {
                parsed = JSON.parse(value || '');
            } catch (error) {
                parsed = null;
            }

            if (!parsed || parsed.layout !== 'absolute' || !Array.isArray(parsed.pages)) {
                return fallbackDefinition(parsed);
            }

            parsed.unit = 'mm';
            parsed.paper = { size: 'A4', width_mm: pageSize.width, height_mm: pageSize.height };
            parsed.guides = normalizeGuides(parsed.guides);
            parsed.pages = parsed.pages.length > 0 ? parsed.pages : fallbackDefinition().pages;
            parsed.pages = parsed.pages.map((page) => ({
                width_mm: pageSize.width,
                height_mm: pageSize.height,
                background: cloneBackground(page),
                elements: Array.isArray(page.elements) ? page.elements : [],
            }));

            return parsed;
        };

        let definition = normalizeDefinition(textarea?.value);

        const currentPage = () => definition.pages[selected.page] || definition.pages[0];
        const currentElement = () => currentPage()?.elements.find((element) => element.id === selected.id) || null;
        const percent = (value, total) => `${(Number(value || 0) / total) * 100}%`;
        const assetUrl = (path) => {
            if (!path) {
                return '';
            }

            return path.startsWith('/') ? path : `/${path}`;
        };
        const round = (value) => Math.round(Number(value || 0) * 10) / 10;
        const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
        const currentGuides = () => {
            definition.guides = normalizeGuides(definition.guides);

            return definition.guides;
        };
        const activeBounds = () => {
            const margins = currentGuides().margins;
            if (!margins.enabled || margins.free_positioning) {
                return { left: 0, top: 0, right: pageSize.width, bottom: pageSize.height };
            }

            const left = clamp(Number(margins.left_mm || 0), 0, pageSize.width - 1);
            const top = clamp(Number(margins.top_mm || 0), 0, pageSize.height - 1);
            const right = clamp(pageSize.width - Number(margins.right_mm || 0), left + 1, pageSize.width);
            const bottom = clamp(pageSize.height - Number(margins.bottom_mm || 0), top + 1, pageSize.height);

            return { left, top, right, bottom };
        };
        const snapMm = (value) => {
            const grid = currentGuides().grid;
            if (!grid.snap) {
                return round(value);
            }

            const size = clamp(Number(grid.size_mm || 5), 1, 50);

            return round(Math.round(Number(value || 0) / size) * size);
        };
        const constrainElement = (element) => {
            if (!element) {
                return;
            }

            const bounds = activeBounds();
            const maxWidth = Math.max(1, bounds.right - bounds.left);
            const maxHeight = Math.max(1, bounds.bottom - bounds.top);
            element.w_mm = round(clamp(Number(element.w_mm || 1), 1, maxWidth));
            element.h_mm = round(clamp(Number(element.h_mm || 1), 1, maxHeight));
            const x = snapMm(element.x_mm);
            const y = snapMm(element.y_mm);
            element.x_mm = round(clamp(x, bounds.left, Math.max(bounds.left, bounds.right - element.w_mm)));
            element.y_mm = round(clamp(y, bounds.top, Math.max(bounds.top, bounds.bottom - element.h_mm)));
        };
        const moveSignaturesToLastPage = () => {
            if (definition.pages.length < 2) {
                return;
            }

            const lastPageIndex = definition.pages.length - 1;
            const lastPage = definition.pages[lastPageIndex];
            let selectedSignatureMoved = false;

            definition.pages.forEach((page, pageIndex) => {
                if (pageIndex === lastPageIndex) {
                    return;
                }

                const keptElements = [];
                (page.elements || []).forEach((element) => {
                    if (element.type === 'signature') {
                        lastPage.elements.push({ ...element });
                        selectedSignatureMoved = selectedSignatureMoved || selected.id === element.id;
                        return;
                    }

                    keptElements.push(element);
                });
                page.elements = keptElements;
            });

            if (selectedSignatureMoved) {
                selected.page = lastPageIndex;
            }
        };
        const sync = () => {
            const json = JSON.stringify(definition, null, 2);
            if (textarea) {
                textarea.value = json;
            }
            if (mirror) {
                mirror.value = json;
            }
        };

        const setBackgroundStatus = (message, state = 'idle') => {
            if (!bgStatus) {
                return;
            }

            bgStatus.textContent = message;
            bgStatus.dataset.state = state;
        };

        const applyBackgroundPath = (path) => {
            const page = currentPage();
            if (!page || !path) {
                return;
            }

            page.background.image_path = path;
            if (bgPath) {
                bgPath.value = path;
            }
            render();
        };

        const uploadBackground = async (file) => {
            if (!file || !uploadUrl) {
                return;
            }

            if (!file.type.startsWith('image/')) {
                setBackgroundStatus('Envie somente imagem PNG, JPG ou WEBP.', 'error');
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                setBackgroundStatus('Imagem acima de 10 MB. Reduza o arquivo antes de enviar.', 'error');
                return;
            }

            const payload = new FormData();
            payload.append('background', file);
            bgDrop?.classList.add('is-uploading');
            setBackgroundStatus('Enviando papel timbrado...', 'loading');

            try {
                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: payload,
                    credentials: 'same-origin',
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || !data.path) {
                    const message = data.message
                        || Object.values(data.errors || {}).flat().filter(Boolean).shift()
                        || 'Não foi possível enviar o plano de fundo.';
                    throw new Error(message);
                }

                applyBackgroundPath(data.path);
                setBackgroundStatus('Plano de fundo aplicado à página selecionada.', 'success');
            } catch (error) {
                setBackgroundStatus(error?.message || 'Falha ao enviar o plano de fundo.', 'error');
            } finally {
                bgDrop?.classList.remove('is-uploading');
                if (bgFile) {
                    bgFile.value = '';
                }
            }
        };

        const pageBackgroundHtml = (page) => {
            const path = page.background?.image_path || '';
            if (!path) {
                return '';
            }

            const fit = page.background?.image_fit || 'cover';
            const opacity = Number(page.background?.image_opacity ?? 0.08);

            return `<div class="legal-doc-page-bg" style="opacity:${opacity}"><img class="fit-${fit}" src="${AdminUI.escapeHtml(assetUrl(path))}" alt=""></div>`;
        };

        const pageGuideHtml = () => {
            const margins = currentGuides().margins;
            if (!margins.enabled) {
                return '';
            }

            const left = clamp(Number(margins.left_mm || 0), 0, pageSize.width);
            const top = clamp(Number(margins.top_mm || 0), 0, pageSize.height);
            const right = clamp(Number(margins.right_mm || 0), 0, pageSize.width - left);
            const bottom = clamp(Number(margins.bottom_mm || 0), 0, pageSize.height - top);
            const width = Math.max(0, pageSize.width - left - right);
            const height = Math.max(0, pageSize.height - top - bottom);

            return `<div class="legal-doc-margin-guide" style="left:${percent(left, pageSize.width)};top:${percent(top, pageSize.height)};width:${percent(width, pageSize.width)};height:${percent(height, pageSize.height)}"></div>`;
        };

        const pageStyle = (page) => {
            const grid = currentGuides().grid;

            return [
                `background:${AdminUI.escapeHtml(page.background?.color || '#ffffff')}`,
                `--doc-grid-x:${percent(grid.size_mm || 5, pageSize.width)}`,
                `--doc-grid-y:${percent(grid.size_mm || 5, pageSize.height)}`,
            ].join(';');
        };

        const renderElement = (element) => {
            const common = `left:${percent(element.x_mm, pageSize.width)};top:${percent(element.y_mm, pageSize.height)};width:${percent(element.w_mm, pageSize.width)};height:${percent(element.h_mm, pageSize.height)};opacity:${element.opacity ?? 1};`;
            const selectedClass = selected.id === element.id ? ' is-selected' : '';
            const data = `data-doc-element="${AdminUI.escapeHtml(element.id)}"`;

            if (element.type === 'signature') {
                return `<div class="legal-doc-element legal-doc-element-signature${selectedClass}" ${data} style="${common};border-bottom-color:${AdminUI.escapeHtml(element.border_color || '#111827')}">${AdminUI.escapeHtml(element.label || 'Assinatura')}</div>`;
            }
            if (element.type === 'line') {
                return `<div class="legal-doc-element legal-doc-element-line${selectedClass}" ${data} style="${common};background:${AdminUI.escapeHtml(element.color || '#111827')}"></div>`;
            }
            if (element.type === 'rectangle') {
                return `<div class="legal-doc-element legal-doc-element-rectangle${selectedClass}" ${data} style="${common};border-color:${AdminUI.escapeHtml(element.border_color || '#111827')};background:${AdminUI.escapeHtml(element.background_color || 'transparent')}"></div>`;
            }
            if (element.type === 'image') {
                const image = element.image_path ? `<img src="${AdminUI.escapeHtml(assetUrl(element.image_path))}" alt="">` : '<span class="small text-muted">Imagem</span>';
                return `<div class="legal-doc-element legal-doc-element-image${selectedClass}" ${data} style="${common}">${image}</div>`;
            }

            return `<div class="legal-doc-element legal-doc-element-text${selectedClass}" ${data} style="${common};font-size:${Number(element.font_size_pt || 11) * 1.333}px;font-weight:${element.font_weight || 400};line-height:${element.line_height || 1.35};text-align:${element.align || 'left'};color:${AdminUI.escapeHtml(element.color || '#111827')}">${AdminUI.escapeHtml(element.text || 'Texto')}</div>`;
        };

        const render = () => {
            if (!pagesRoot) {
                return;
            }

            const guides = currentGuides();
            const pageClasses = [
                'legal-doc-page',
                guides.grid.visible ? 'is-grid-visible' : '',
                guides.margins.enabled ? 'is-margins-visible' : '',
                guides.margins.enabled && !guides.margins.free_positioning ? 'is-margin-locked' : '',
            ].filter(Boolean).join(' ');

            pagesRoot.innerHTML = definition.pages.map((page, index) => `
                <div class="legal-doc-page-shell" data-doc-page-shell="${index}">
                    <div class="legal-doc-page-title">
                        <span>Página ${index + 1}</span>
                        <button class="btn btn-sm btn-outline-danger" type="button" data-doc-remove-page="${index}" ${definition.pages.length === 1 ? 'disabled' : ''}>Remover página</button>
                    </div>
                    <div class="${pageClasses}" data-doc-page="${index}" style="${pageStyle(page)}">
                        ${pageBackgroundHtml(page)}
                        ${pageGuideHtml()}
                        ${(page.elements || []).map(renderElement).join('')}
                    </div>
                </div>
            `).join('');

            bindPageEvents();
            syncInspector();
            sync();
        };

        const selectElement = (pageIndex, id) => {
            selected = { page: pageIndex, id };
            render();
        };

        const syncGuideControls = () => {
            const guides = currentGuides();
            if (gridVisible) {
                gridVisible.checked = guides.grid.visible;
            }
            if (snapGrid) {
                snapGrid.checked = guides.grid.snap;
            }
            if (gridSize) {
                gridSize.value = guides.grid.size_mm;
            }
            if (marginsEnabled) {
                marginsEnabled.checked = guides.margins.enabled;
            }
            if (freePositioning) {
                freePositioning.checked = guides.margins.free_positioning;
            }
            marginFields.forEach((field) => {
                const side = field.dataset.docMargin;
                const key = `${side}_mm`;
                field.value = guides.margins[key] ?? 0;
            });
        };

        const syncInspector = () => {
            const page = currentPage();
            const element = currentElement();
            syncGuideControls();
            if (bgPath && page) {
                bgPath.value = page.background?.image_path || '';
            }
            if (bgOpacity && page) {
                bgOpacity.value = Number(page.background?.image_opacity ?? 0.08);
            }
            if (bgFit && page) {
                bgFit.value = page.background?.image_fit || 'cover';
            }
            if (inspector) {
                inspector.dataset.selectedType = element?.type || '';
            }

            fields.forEach((field) => {
                const key = field.dataset.docField;
                if (!element || !key) {
                    field.value = '';
                    return;
                }

                field.value = typeof element[key] === 'boolean' ? String(element[key]) : (element[key] ?? '');
            });
        };

        const updateSelected = (key, value) => {
            const element = currentElement();
            if (!element) {
                return;
            }

            if (['x_mm', 'y_mm', 'w_mm', 'h_mm', 'opacity', 'font_size_pt', 'line_height'].includes(key)) {
                element[key] = Number(value);
            } else if (key === 'signer_order') {
                element[key] = Math.max(1, Number.parseInt(value || '1', 10));
            } else if (key === 'required') {
                element[key] = value === 'true';
            } else {
                element[key] = value;
            }

            if (['x_mm', 'y_mm', 'w_mm', 'h_mm'].includes(key)) {
                constrainElement(element);
            }

            render();
        };

        const addElement = (type) => {
            const page = currentPage();
            const id = uniqueId(type);
            const base = { id, type, x_mm: 24, y_mm: 36, w_mm: 80, h_mm: 18, opacity: 1 };
            const element = {
                text: { ...base, text: 'Texto do documento', font_size_pt: 11, font_weight: '400', line_height: 1.35, align: 'left', color: '#111827' },
                signature: { ...base, y_mm: 236, w_mm: 90, h_mm: 24, label: 'Assinatura', signer_order: 1, required: true, border_color: '#111827' },
                line: { ...base, h_mm: 1, color: '#111827', thickness_mm: 0.25 },
                rectangle: { ...base, w_mm: 60, h_mm: 30, border_color: '#111827', background_color: 'transparent', border_width_mm: 0.25 },
                logo: { ...base, type: 'image', w_mm: 36, h_mm: 22, image_path: brandLogo, fit: 'contain' },
            }[type] || { ...base, text: 'Texto', font_size_pt: 11 };

            constrainElement(element);
            page.elements.push(element);
            selectElement(selected.page, id);
        };

        const bindPageEvents = () => {
            pagesRoot.querySelectorAll('[data-doc-page]').forEach((pageEl) => {
                const pageIndex = Number(pageEl.dataset.docPage || 0);
                pageEl.addEventListener('pointerdown', (event) => {
                    const elementEl = event.target.closest('[data-doc-element]');
                    if (!elementEl) {
                        selected = { page: pageIndex, id: null };
                        render();
                        return;
                    }

                    event.preventDefault();
                    const id = elementEl.dataset.docElement;
                    selected = { page: pageIndex, id };
                    const element = currentElement();
                    const rect = pageEl.getBoundingClientRect();
                    const start = { x: event.clientX, y: event.clientY, xMm: Number(element.x_mm || 0), yMm: Number(element.y_mm || 0) };
                    elementEl.setPointerCapture?.(event.pointerId);

                    const move = (moveEvent) => {
                        const dx = (moveEvent.clientX - start.x) * (pageSize.width / rect.width);
                        const dy = (moveEvent.clientY - start.y) * (pageSize.height / rect.height);
                        element.x_mm = start.xMm + dx;
                        element.y_mm = start.yMm + dy;
                        constrainElement(element);
                        sync();
                        elementEl.style.left = percent(element.x_mm, pageSize.width);
                        elementEl.style.top = percent(element.y_mm, pageSize.height);
                        syncInspector();
                    };
                    const up = () => {
                        window.removeEventListener('pointermove', move);
                        window.removeEventListener('pointerup', up);
                        render();
                    };

                    window.addEventListener('pointermove', move);
                    window.addEventListener('pointerup', up, { once: true });
                });
            });

            pagesRoot.querySelectorAll('[data-doc-remove-page]').forEach((button) => {
                button.addEventListener('click', () => {
                    const index = Number(button.dataset.docRemovePage || 0);
                    if (definition.pages.length <= 1) {
                        return;
                    }
                    const [removedPage] = definition.pages.splice(index, 1);
                    const removedSignatures = (removedPage?.elements || []).filter((element) => element.type === 'signature');
                    if (removedSignatures.length > 0) {
                        definition.pages[definition.pages.length - 1].elements.push(...removedSignatures.map((element) => ({ ...element })));
                    }
                    moveSignaturesToLastPage();
                    selected = { page: Math.max(0, index - 1), id: null };
                    render();
                });
            });
        };

        designer.querySelectorAll('[data-doc-add]').forEach((button) => {
            button.addEventListener('click', () => addElement(button.dataset.docAdd));
        });

        designer.querySelector('[data-doc-add-page]')?.addEventListener('click', () => {
            const sourcePage = currentPage();
            definition.pages.push({
                width_mm: pageSize.width,
                height_mm: pageSize.height,
                background: cloneBackground(sourcePage),
                elements: [],
            });
            selected = { page: definition.pages.length - 1, id: null };
            moveSignaturesToLastPage();
            if (sourcePage?.background?.image_path) {
                setBackgroundStatus('Nova página criada com o mesmo papel timbrado; assinaturas movidas para a última folha.', 'success');
            }
            render();
        });

        designer.querySelector('[data-doc-remove]')?.addEventListener('click', () => {
            const page = currentPage();
            page.elements = page.elements.filter((element) => element.id !== selected.id);
            selected.id = null;
            render();
        });

        fields.forEach((field) => {
            field.addEventListener('input', () => updateSelected(field.dataset.docField, field.value));
            field.addEventListener('change', () => updateSelected(field.dataset.docField, field.value));
        });

        const constrainAllElements = () => {
            definition.pages.forEach((page) => {
                (page.elements || []).forEach(constrainElement);
            });
        };
        const updateGuides = (callback, shouldConstrain = false) => {
            const guides = currentGuides();
            callback(guides);
            definition.guides = normalizeGuides(guides);
            if (shouldConstrain) {
                constrainAllElements();
            }
            render();
        };

        gridVisible?.addEventListener('change', () => {
            updateGuides((guides) => {
                guides.grid.visible = gridVisible.checked;
            });
        });
        snapGrid?.addEventListener('change', () => {
            updateGuides((guides) => {
                guides.grid.snap = snapGrid.checked;
            });
        });
        gridSize?.addEventListener('input', () => {
            updateGuides((guides) => {
                guides.grid.size_mm = Number(gridSize.value || 5);
            });
        });
        marginsEnabled?.addEventListener('change', () => {
            updateGuides((guides) => {
                guides.margins.enabled = marginsEnabled.checked;
            }, true);
        });
        freePositioning?.addEventListener('change', () => {
            updateGuides((guides) => {
                guides.margins.free_positioning = freePositioning.checked;
            }, !freePositioning.checked);
        });
        marginFields.forEach((field) => {
            field.addEventListener('input', () => {
                updateGuides((guides) => {
                    const side = field.dataset.docMargin;
                    guides.margins[`${side}_mm`] = Number(field.value || 0);
                }, currentGuides().margins.enabled && !currentGuides().margins.free_positioning);
            });
        });

        bgPath?.addEventListener('input', () => {
            currentPage().background.image_path = bgPath.value;
            setBackgroundStatus('Caminho do fundo atualizado manualmente.', 'idle');
            render();
        });
        bgOpacity?.addEventListener('input', () => {
            currentPage().background.image_opacity = Number(bgOpacity.value);
            render();
        });
        bgFit?.addEventListener('change', () => {
            currentPage().background.image_fit = bgFit.value;
            render();
        });

        bgDrop?.addEventListener('click', () => bgFile?.click());
        bgDrop?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                bgFile?.click();
            }
        });
        bgFile?.addEventListener('change', () => uploadBackground(bgFile.files?.[0]));
        bgDrop?.addEventListener('dragover', (event) => {
            event.preventDefault();
            bgDrop.classList.add('is-dragging');
        });
        bgDrop?.addEventListener('dragleave', () => {
            bgDrop.classList.remove('is-dragging');
        });
        bgDrop?.addEventListener('drop', (event) => {
            event.preventDefault();
            bgDrop.classList.remove('is-dragging');
            uploadBackground(event.dataTransfer?.files?.[0]);
        });

        form?.addEventListener('submit', () => {
            sync();
            if (outputFormat && outputFormat.value === 'docx') {
                outputFormat.value = 'pdf';
            }
        });

        if (outputFormat && outputFormat.value === 'docx') {
            outputFormat.value = 'pdf';
        }

        render();
    });
}

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

let adminUiBooted = false;

const bootAdminUi = async () => {
    if (adminUiBooted) {
        return;
    }

    adminUiBooted = true;

    try {
        await adminPluginsReady;
    } finally {
        AdminUI.boot();
        window.setTimeout(() => AdminUI.initCharts(document), 240);
        window.setTimeout(() => AdminUI.initCharts(document), 900);
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAdminUi, { once: true });
} else {
    bootAdminUi();
}

window.addEventListener('load', () => {
    AdminUI.initCharts(document);
}, { once: true });
