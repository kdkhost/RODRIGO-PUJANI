import './bootstrap';

import Chart from 'chart.js/auto';
import $ from 'jquery';
import * as FilePond from 'filepond';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import {
    appendRecaptchaToken,
    applyAutoPlaceholders,
    bindDeviceAuditFields,
    bindRecaptchaForms,
    configureToastr,
    flushPageToasts,
    showToast,
} from './shared/ui';

FilePond.registerPlugin(
    FilePondPluginFileValidateSize,
    FilePondPluginFileValidateType,
    FilePondPluginImagePreview,
);

window.$ = window.jQuery = $;
globalThis.$ = $;
globalThis.jQuery = $;

const SiteUI = {
    deferredInstallPrompt: null,

    boot() {
        configureToastr();
        flushPageToasts();
        applyAutoPlaceholders();
        bindDeviceAuditFields(document);
        bindRecaptchaForms(document);
        this.bindPortalTheme();
        this.initCharts();

        [
            this.bindCursor,
            this.bindNavbar,
            this.bindBackToTop,
            this.bindMobileMenu,
            this.bindObserver,
            this.bindCounters,
            this.bindInputMasks,
            this.bindCepAutofill,
            this.bindPortalProfileType,
            this.bindPortalUploads,
            this.bindPortalAvatarPreview,
            this.bindPortalEditor,
            this.bindPortalTour,
            this.bindParallax,
            this.bindContactForm,
            this.bindPwa,
            this.bindPortalNotifications,
            this.bindWhatsApp,
        ].forEach((task) => {
            try {
                task.call(this);
            } catch (error) {
                console.error('Falha ao iniciar recurso visual do site.', error);
            }
        });
    },

    initCharts() {
        this.applyPortalChartTheme(document.documentElement.dataset.portalTheme === 'dark' ? 'dark' : 'light');

        document.querySelectorAll('[data-site-chart]').forEach((canvas) => {
            if (canvas.dataset.chartReady === 'true') {
                return;
            }

            try {
                const config = JSON.parse(canvas.dataset.siteChart || '{}');
                const chart = new Chart(canvas, {
                    ...config,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        ...(config.options || {}),
                    },
                });

                canvas._siteChart = chart;
                canvas.dataset.chartReady = 'true';
            } catch (error) {
                console.error('Falha ao inicializar gráfico do portal.', error);
            }
        });
    },

    bindPortalTheme() {
        const root = document.documentElement;
        const buttons = Array.from(document.querySelectorAll('[data-portal-theme-toggle]'));
        const storageKey = 'portal-client-theme';
        const resolveTheme = () => {
            try {
                const stored = window.localStorage.getItem(storageKey);

                if (stored === 'dark' || stored === 'light') {
                    return stored;
                }
            } catch (error) {
                // O tema continua funcional mesmo se o navegador bloquear localStorage.
            }

            return window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        };

        const updateControls = (theme) => {
            buttons.forEach((button) => {
                const icon = button.querySelector('[data-portal-theme-icon]');
                const nextLabel = theme === 'dark' ? 'Ativar tema claro' : 'Ativar tema escuro';

                button.title = nextLabel;
                button.setAttribute('aria-label', nextLabel);

                if (icon) {
                    icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
                }
            });
        };

        const setTheme = (theme, persist = true) => {
            root.dataset.portalTheme = theme;
            root.style.colorScheme = theme;
            this.applyPortalChartTheme(theme);
            updateControls(theme);

            if (persist) {
                try {
                    window.localStorage.setItem(storageKey, theme);
                } catch (error) {
                    // O usuário ainda consegue alternar o tema durante a sessão.
                }
            }
        };

        setTheme(root.dataset.portalTheme === 'dark' || root.dataset.portalTheme === 'light'
            ? root.dataset.portalTheme
            : resolveTheme(), false);

        buttons.forEach((button) => {
            if (button.dataset.portalThemeReady === 'true') {
                return;
            }

            button.addEventListener('click', () => {
                const current = root.dataset.portalTheme === 'dark' ? 'dark' : 'light';
                setTheme(current === 'dark' ? 'light' : 'dark');
            });

            button.dataset.portalThemeReady = 'true';
        });
    },

    applyPortalChartTheme(theme) {
        const isDark = theme === 'dark';
        const textColor = isDark ? '#c6d3e1' : '#667085';
        const gridColor = isDark ? 'rgba(198, 211, 225, 0.12)' : 'rgba(16, 24, 40, 0.08)';

        Chart.defaults.color = textColor;
        Chart.defaults.borderColor = gridColor;

        document.querySelectorAll('[data-site-chart]').forEach((canvas) => {
            const chart = canvas._siteChart;

            if (!chart) {
                return;
            }

            if (chart.options.plugins?.legend?.labels) {
                chart.options.plugins.legend.labels.color = textColor;
            }

            Object.values(chart.options.scales || {}).forEach((scale) => {
                if (scale.ticks) {
                    scale.ticks.color = textColor;
                }

                if (scale.grid) {
                    scale.grid.color = gridColor;
                }
            });

            chart.update('none');
        });
    },

    bindCursor() {
        const cursor = document.getElementById('cursor');
        const ring = document.getElementById('cursor-ring');

        if (!cursor || !ring || window.innerWidth < 768) {
            return;
        }

        document.addEventListener('mousemove', (event) => {
            cursor.style.left = `${event.clientX}px`;
            cursor.style.top = `${event.clientY}px`;
            ring.style.left = `${event.clientX}px`;
            ring.style.top = `${event.clientY}px`;
            cursor.style.transform = 'translate(-50%, -50%)';
            ring.style.transform = 'translate(-50%, -50%)';
        });

        document.querySelectorAll('a, button, input, textarea, select').forEach((element) => {
            element.addEventListener('mouseenter', () => {
                ring.style.width = '60px';
                ring.style.height = '60px';
                ring.style.opacity = '0.3';
            });

            element.addEventListener('mouseleave', () => {
                ring.style.width = '36px';
                ring.style.height = '36px';
                ring.style.opacity = '0.6';
            });
        });
    },

    bindNavbar() {
        const navbar = document.getElementById('navbar');
        const progress = document.getElementById('scroll-progress');

        if (!navbar || !progress) {
            return;
        }

        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 60);

            const total = document.documentElement.scrollHeight - window.innerHeight;
            progress.style.width = total > 0 ? `${(window.scrollY / total) * 100}%` : '0%';
        });
    },

    bindBackToTop() {
        const buttons = Array.from(document.querySelectorAll('[data-scroll-top]'));

        if (!buttons.length) {
            return;
        }

        const toggleButtons = () => {
            const visible = window.scrollY > 360;

            buttons.forEach((button) => {
                button.classList.toggle('is-visible', visible);
                button.setAttribute('aria-hidden', visible ? 'false' : 'true');
            });
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth',
                });
            });
        });

        toggleButtons();
        window.addEventListener('scroll', toggleButtons, { passive: true });
    },

    bindMobileMenu() {
        const menuButton = document.getElementById('menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const overlay = document.getElementById('menu-overlay');
        const line1 = document.getElementById('h1');
        const line2 = document.getElementById('h2');
        const line3 = document.getElementById('h3');

        if (!menuButton || !mobileMenu || !overlay) {
            return;
        }

        let open = false;

        const syncState = () => {
            mobileMenu.classList.toggle('open', open);
            overlay.classList.toggle('hidden', !open);

            if (line1 && line2 && line3) {
                line1.style.transform = open ? 'translateY(6px) rotate(45deg)' : '';
                line2.style.opacity = open ? '0' : '';
                line3.style.transform = open ? 'translateY(-6px) rotate(-45deg)' : '';
            }
        };

        menuButton.addEventListener('click', () => {
            open = !open;
            syncState();
        });

        overlay.addEventListener('click', () => {
            open = false;
            syncState();
        });

        window.closeMobile = () => {
            open = false;
            syncState();
        };
    },

    bindObserver() {
        const elements = document.querySelectorAll('.aos, .aos-left, .aos-right, .aos-scale');

        if (!elements.length) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            elements.forEach((element) => element.classList.add('visible'));
            return;
        }

        const reveal = (element) => element.classList.add('visible');
        const isVisibleNow = (element) => {
            const rect = element.getBoundingClientRect();

            return rect.top < window.innerHeight * 0.95 && rect.bottom > 0;
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    reveal(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        elements.forEach((element) => {
            if (isVisibleNow(element)) {
                reveal(element);
                return;
            }

            observer.observe(element);
        });

        document.documentElement.classList.add('site-animations-ready');
        window.setTimeout(() => elements.forEach(reveal), 1600);
    },

    bindCounters() {
        const animateCounter = (element, target) => {
            if (element.dataset.counterReady === 'true') {
                return;
            }

            element.dataset.counterReady = 'true';
            const duration = 2000;
            const start = performance.now();

            const update = (time) => {
                const progress = Math.min((time - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                element.textContent = Math.floor(eased * target).toLocaleString('pt-BR');

                if (progress < 1) {
                    requestAnimationFrame(update);
                    return;
                }

                element.textContent = target.toLocaleString('pt-BR');
            };

            requestAnimationFrame(update);
        };

        const counters = document.querySelectorAll('.counter');

        if (!counters.length) {
            return;
        }

        const startCounter = (element) => {
            const target = Number(element.dataset.target || 0);

            if (Number.isFinite(target)) {
                animateCounter(element, target);
            }
        };

        const isVisibleNow = (element) => {
            const rect = element.getBoundingClientRect();

            return rect.top < window.innerHeight && rect.bottom > 0;
        };

        if (!('IntersectionObserver' in window)) {
            counters.forEach(startCounter);
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    startCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        counters.forEach((element) => {
            if (isVisibleNow(element)) {
                startCounter(element);
                return;
            }

            observer.observe(element);
        });
    },

    bindInputMasks() {
        document.querySelectorAll('input[type="tel"], [data-mask]').forEach((input) => {
            if (input.dataset.siteMaskReady) {
                return;
            }

            const maskType = input.dataset.mask || (input.type === 'tel' ? 'phone' : null);

            if (!maskType) {
                return;
            }

            const applyMask = () => {
                input.value = this.applyMask(maskType, input.value);
            };

            input.addEventListener('input', applyMask);
            input.addEventListener('blur', applyMask);
            applyMask();
            input.dataset.siteMaskReady = 'true';
        });
    },

    bindCepAutofill() {
        document.querySelectorAll('[data-cep-autofill]').forEach((input) => {
            if (input.dataset.cepAutofillReady === 'true') {
                return;
            }

            const form = input.closest('form') || document;
            const fillAddress = async () => {
                const cep = String(input.value || '').replace(/\D/g, '');

                if (cep.length !== 8) {
                    return;
                }

                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await response.json();

                    if (data.erro) {
                        return;
                    }

                    const mappings = {
                        address_street: data.logradouro,
                        address_district: data.bairro,
                        address_city: data.localidade,
                        address_state: data.uf,
                    };

                    Object.entries(mappings).forEach(([name, value]) => {
                        const field = form.querySelector(`[name="${name}"]`);

                        if (field && !field.value && value) {
                            field.value = value;
                            field.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });
                } catch (error) {
                    console.warn('Nao foi possivel consultar o CEP.', error);
                }
            };

            input.addEventListener('blur', fillAddress);
            input.dataset.cepAutofillReady = 'true';
        });
    },

    bindPortalProfileType() {
        const select = document.querySelector('[data-portal-person-type]');

        if (!select || select.dataset.portalPersonTypeReady === 'true') {
            return;
        }

        const documentInput = document.querySelector('[data-portal-document-field]');
        const documentLabel = document.querySelector('[data-portal-document-label]');
        const nameInput = document.querySelector('#name');
        const nameLabel = document.querySelector('[data-portal-name-label]');
        const companyFields = Array.from(document.querySelectorAll('[data-portal-company-field]'));

        const sync = () => {
            const isCompany = select.value === 'company';

            if (documentInput) {
                documentInput.dataset.mask = isCompany ? 'cnpj' : 'cpf';
                documentInput.placeholder = isCompany ? '00.000.000/0000-00' : '000.000.000-00';
                documentInput.value = this.applyMask(documentInput.dataset.mask, documentInput.value);
            }

            if (documentLabel) {
                documentLabel.textContent = isCompany ? 'CNPJ' : 'CPF';
            }

            if (nameInput) {
                nameInput.placeholder = isCompany ? 'Razão social' : 'Nome completo';
            }

            if (nameLabel) {
                nameLabel.textContent = isCompany ? 'Razão social' : 'Nome completo';
            }

            companyFields.forEach((wrapper) => {
                wrapper.classList.toggle('portal-hidden', !isCompany);
                wrapper.querySelectorAll('input, select, textarea').forEach((field) => {
                    if (field.dataset.lockedByPortal === 'true') {
                        return;
                    }

                    field.disabled = !isCompany;
                });
            });
        };

        select.addEventListener('change', sync);
        sync();
        select.dataset.portalPersonTypeReady = 'true';
    },

    bindPortalEditor() {
        const editors = Array.from(document.querySelectorAll('textarea[data-editor="summernote"]'));
        if (!editors.length) {
            return;
        }

        import('summernote/dist/summernote-lite.min.js')
            .then(() => import('summernote/dist/lang/summernote-pt-BR.min.js'))
            .then(() => {
                editors.forEach((element) => {
                    if (element.dataset.summernoteReady === 'true') {
                        return;
                    }

                    $(element).summernote({
                        lang: 'pt-BR',
                        height: Number(element.dataset.editorHeight || 220),
                        toolbar: [
                            ['style', ['bold', 'italic', 'underline']],
                            ['para', ['ul', 'ol']],
                            ['insert', ['link']],
                            ['view', ['fullscreen', 'codeview']],
                        ],
                        placeholder: element.getAttribute('placeholder') || 'Escreva sua mensagem...',
                    });

                    element.dataset.summernoteReady = 'true';
                });
            })
            .catch((error) => {
                console.error('Falha ao carregar editor do portal.', error);
                showToast('warning', 'Nao foi possivel iniciar o editor avancado. O campo texto segue disponivel.');
            });
    },

    bindPortalUploads() {
        document.querySelectorAll('[data-portal-filepond]').forEach((input) => {
            if (input.dataset.portalFilepondReady === 'true') {
                return;
            }

            try {
                const accepted = input.dataset.accepted
                    ? input.dataset.accepted.split(',').map((item) => item.trim()).filter(Boolean)
                    : null;

                const pond = FilePond.create(input, {
                    allowMultiple: input.hasAttribute('multiple'),
                    credits: false,
                    storeAsFile: true,
                    acceptedFileTypes: accepted,
                    maxFileSize: input.dataset.maxFileSize || '4MB',
                    labelIdle: 'Arraste e solte ou <span class="filepond--label-action">selecione arquivos</span>',
                    labelFileTypeNotAllowed: 'Tipo de arquivo não permitido',
                    fileValidateTypeLabelExpectedTypes: 'Tipos aceitos: {allTypes}',
                    labelMaxFileSizeExceeded: 'Arquivo muito grande',
                    labelMaxFileSize: 'Tamanho máximo: {filesize}',
                    labelTapToCancel: 'toque para cancelar',
                    labelTapToRetry: 'toque para tentar novamente',
                    labelTapToUndo: 'toque para desfazer',
                });

                const updatePreview = () => {
                    const file = pond.getFiles()?.[0]?.file;
                    const preview = document.querySelector(input.dataset.previewTarget || '[data-portal-avatar-preview]');

                    if (!file || !preview || !file.type.startsWith('image/')) {
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = () => {
                        preview.innerHTML = `<img src="${reader.result}" alt="Preview da foto">`;
                    };
                    reader.readAsDataURL(file);
                };

                pond.on('addfile', updatePreview);
                pond.on('updatefiles', updatePreview);
                input.dataset.portalFilepondReady = 'true';
            } catch (error) {
                console.error('FilePond do portal não pôde ser iniciado.', error);
            }
        });
    },

    bindPortalAvatarPreview() {
        document.querySelectorAll('[data-portal-avatar-input]').forEach((input) => {
            if (input.dataset.avatarPreviewReady === 'true') {
                return;
            }

            input.addEventListener('change', () => {
                const file = input.files?.[0];
                const preview = document.querySelector('[data-portal-avatar-preview]');

                if (!file || !preview || !file.type.startsWith('image/')) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = () => {
                    preview.innerHTML = `<img src="${reader.result}" alt="Preview da foto">`;
                };
                reader.readAsDataURL(file);
            });

            input.dataset.avatarPreviewReady = 'true';
        });
    },

    bindPortalTour() {
        const body = document.body;

        if (body.dataset.portalTourEnabled !== 'true') {
            return;
        }

        const clientId = body.dataset.portalClientId || 'cliente';
        const storageKey = `client-portal-tour-completed:${clientId}`;
        const restartButtons = Array.from(document.querySelectorAll('[data-portal-restart-tour]'));
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
                    reject(new Error('Driver.js não foi carregado.'));
                    return;
                }

                window.setTimeout(tick, 120);
            };

            tick();
        });

        const steps = [
            {
                element: '[data-portal-tour-sidebar]',
                popover: {
                    title: 'Menu do portal',
                    description: 'Use este menu para acessar seu painel, seus dados cadastrais, processos e documentos compartilhados.',
                    side: 'right',
                    align: 'start',
                },
            },
            {
                element: '[data-portal-tour-topbar]',
                popover: {
                    title: 'Barra superior',
                    description: 'Aqui ficam seu perfil, o botão para reiniciar este tour e a opção de sair com segurança.',
                    side: 'bottom',
                    align: 'end',
                },
            },
            {
                element: '[data-portal-tour-content]',
                popover: {
                    title: 'Área de acompanhamento',
                    description: 'Nesta área você acompanha indicadores, próximos marcos, documentos, processos e movimentações liberadas pelo escritório.',
                    side: 'top',
                    align: 'center',
                },
            },
            {
                element: '.portal-client-nav a[href*="perfil"]',
                popover: {
                    title: 'Atualização cadastral',
                    description: 'Mantenha telefone, WhatsApp, endereço e foto atualizados para facilitar a comunicação com a equipe jurídica.',
                    side: 'right',
                    align: 'start',
                },
            },
            {
                element: '.portal-client-nav a[href*="documentos"]',
                popover: {
                    title: 'Documentos compartilhados',
                    description: 'Acesse em uma tela própria todos os arquivos liberados pelo escritório para consulta e download.',
                    side: 'right',
                    align: 'start',
                },
            },
            {
                element: '[data-portal-tour-footer]',
                popover: {
                    title: 'Rodapé do portal',
                    description: 'No rodapé ficam as informações institucionais do ambiente reservado do cliente.',
                    side: 'top',
                    align: 'center',
                },
            },
            {
                element: '[data-portal-tour-whatsapp]',
                popover: {
                    title: 'WhatsApp do processo',
                    description: 'Quando houver processo em andamento, este botão mostra somente os advogados vinculados ao seu caso. Ao encerrar o processo, o contato deixa de aparecer.',
                    side: 'left',
                    align: 'end',
                },
            },
        ].filter((step) => document.querySelector(step.element));

        if (steps.length === 0) {
            return;
        }

        const createDriver = async () => {
            const driverFactory = await waitForDriver();

            return driverFactory({
                steps,
                showProgress: true,
                allowClose: true,
                overlayClickBehavior: 'close',
                nextBtnText: 'Próximo',
                prevBtnText: 'Anterior',
                doneBtnText: 'Finalizar',
                progressText: 'Passo {{current}} de {{total}}',
            });
        };

        const markCompleted = () => {
            try {
                window.localStorage.setItem(storageKey, 'true');
            } catch (error) {
                // O tour continua funcional mesmo se o navegador bloquear armazenamento local.
            }
        };

        const hasCompleted = () => {
            try {
                return window.localStorage.getItem(storageKey) === 'true';
            } catch (error) {
                return false;
            }
        };

        const launchTour = async ({ automatic = false } = {}) => {
            if (automatic) {
                markCompleted();
            }

            const driverObj = await createDriver();
            driverObj.drive();
        };

        restartButtons.forEach((button) => {
            if (button.dataset.portalTourReady === 'true') {
                return;
            }

            button.addEventListener('click', (event) => {
                event.preventDefault();
                launchTour().catch((error) => {
                    showToast('error', error.message || 'Não foi possível iniciar o tour guiado.');
                });
            });

            button.dataset.portalTourReady = 'true';
        });

        if (hasCompleted()) {
            return;
        }

        const fire = () => {
            window.setTimeout(() => {
                launchTour({ automatic: true }).catch((error) => {
                    console.warn('Falha ao iniciar o tour do portal do cliente.', error);
                });
            }, 700);
        };

        if (document.readyState === 'complete') {
            fire();
            return;
        }

        window.addEventListener('load', fire, { once: true });
    },

    applyMask(maskType, value) {
        switch (maskType) {
            case 'cpf':
                return this.formatCpf(value);
            case 'cnpj':
                return this.formatCnpj(value);
            case 'cpf-cnpj':
                return this.formatCpfCnpj(value);
            case 'cep':
                return this.formatCep(value);
            case 'date':
                return this.formatDate(value);
            case 'time':
                return this.formatTime(value);
            case 'phone':
            default:
                return this.formatPhone(value);
        }
    },

    formatPhone(value) {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 11);

        if (digits.length <= 2) {
            return digits ? `(${digits}` : '';
        }

        if (digits.length <= 6) {
            return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
        }

        if (digits.length <= 10) {
            return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
        }

        return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
    },

    formatCpf(value) {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 11);

        return digits
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1-$2');
    },

    formatCnpj(value) {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 14);

        return digits
            .replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    },

    formatCpfCnpj(value) {
        const digits = String(value || '').replace(/\D/g, '');

        return digits.length <= 11
            ? this.formatCpf(digits)
            : this.formatCnpj(digits);
    },

    formatCep(value) {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 8);

        if (digits.length <= 5) {
            return digits;
        }

        return `${digits.slice(0, 5)}-${digits.slice(5)}`;
    },

    formatDate(value) {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 8);

        return digits
            .replace(/^(\d{2})(\d)/, '$1/$2')
            .replace(/^(\d{2})\/(\d{2})(\d)/, '$1/$2/$3');
    },

    formatTime(value) {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 4);

        if (digits.length <= 2) {
            return digits;
        }

        return `${digits.slice(0, 2)}:${digits.slice(2)}`;
    },

    bindParallax() {
        const items = document.querySelectorAll('.parallax');

        if (!items.length) {
            return;
        }

        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;

            items.forEach((element) => {
                const speed = Number(element.dataset.speed || 0.3);
                const rotation = element.classList.contains('-rotate-6') ? -6 : 12;
                element.style.transform = `translateY(${scrollY * speed}px) rotate(${rotation}deg)`;
            });
        });
    },

    bindContactForm() {
        const form = document.querySelector('[data-site-contact-form]');

        if (!form) {
            return;
        }

        const successState = document.getElementById('form-success');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            this.clearFormFeedback(form);

            try {
                await appendRecaptchaToken(form, form.dataset.recaptchaAction || 'contact_message');

                await window.axios.post(form.action, new FormData(form), {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });

                form.classList.add('hidden');
                successState?.classList.remove('hidden');
                form.reset();
                showToast('success', 'Solicitação enviada com sucesso.');
            } catch (error) {
                const message = this.resolveErrorMessage(error, 'Não foi possível enviar sua solicitação agora.');
                this.showFormFeedback(form, message);
                showToast('error', message);
            }
        });
    },

    bindPwa() {
        const root = document.documentElement;
        const pwaEnabled = root.dataset.pwaEnabled === '1';
        const installEnabled = root.dataset.pwaInstallEnabled === '1';
        const promptEnabled = root.dataset.pwaPromptEnabled === '1';
        const promptStorageKey = root.dataset.pwaPromptStorageKey || 'site-pwa-promo-dismissed-v1';
        const promo = document.querySelector('[data-pwa-promo]');
        const installButtons = Array.from(document.querySelectorAll('[data-pwa-install]'));
        const installed = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

        const setInstalledState = (value) => {
            document.body.classList.toggle('app-installed', value);

            if (value) {
                document.body.classList.remove('pwa-installable');
                promo?.setAttribute('hidden', 'hidden');
                promo?.classList.remove('is-visible');
            }
        };

        const hidePromo = (persist = false) => {
            promo?.classList.remove('is-visible');
            promo?.setAttribute('hidden', 'hidden');

            if (persist) {
                try {
                    window.localStorage.setItem(promptStorageKey, '1');
                } catch {
                    // Ignore localStorage restrictions gracefully.
                }
            }
        };

        const showPromo = () => {
            if (!promo || !promptEnabled) {
                return;
            }

            try {
                if (window.localStorage.getItem(promptStorageKey) === '1') {
                    return;
                }
            } catch {
                // Ignore localStorage restrictions gracefully.
            }

            promo.hidden = false;
            window.requestAnimationFrame(() => promo.classList.add('is-visible'));
        };

        const clearPwaLocalData = async () => {
            try {
                if ('caches' in window) {
                    const keys = await window.caches.keys();
                    await Promise.all(keys
                        .filter((key) => key.startsWith('pujani-'))
                        .map((key) => window.caches.delete(key)));
                }

                window.localStorage?.removeItem(promptStorageKey);
            } catch {
                // A limpeza local é complementar e não deve interromper a navegação.
            }
        };

        setInstalledState(installed);

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                if (pwaEnabled) {
                    navigator.serviceWorker.register('/sw.js')
                        .then((registration) => {
                            registration.update().catch(() => null);
                            registration.active?.postMessage({ type: 'PUJANI_UPDATE_PWA' });
                        })
                        .catch(() => null);
                    return;
                }

                navigator.serviceWorker.getRegistrations()
                    .then((registrations) => Promise.all(registrations.map((registration) => {
                        registration.active?.postMessage({ type: 'PUJANI_CLEAR_PWA' });
                        registration.waiting?.postMessage({ type: 'PUJANI_CLEAR_PWA' });
                        return registration.unregister();
                    })))
                    .then(clearPwaLocalData)
                    .catch(() => null);
            });
        }

        if (!pwaEnabled || !installEnabled || installed) {
            hidePromo();
            return;
        }

        promo?.querySelector('[data-pwa-dismiss]')?.addEventListener('click', () => hidePromo(true));

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            this.deferredInstallPrompt = event;
            document.body.classList.add('pwa-installable');
            showPromo();
        });

        window.addEventListener('appinstalled', () => {
            this.deferredInstallPrompt = null;
            hidePromo(true);
            setInstalledState(true);
            navigator.serviceWorker?.getRegistration?.('/').then((registration) => registration?.update()).catch(() => null);
            showToast('success', 'Aplicativo instalado com sucesso.');
        });

        installButtons.forEach((button) => {
            button.addEventListener('click', async () => {
                if (!this.deferredInstallPrompt) {
                    showToast('info', 'A instalação do aplicativo não está disponível neste dispositivo agora.');
                    return;
                }

                this.deferredInstallPrompt.prompt();
                await this.deferredInstallPrompt.userChoice;
                this.deferredInstallPrompt = null;
                document.body.classList.remove('pwa-installable');
                hidePromo(true);
            });
        });
    },

    showFormFeedback(form, message) {
        let feedback = form.querySelector('[data-form-feedback]');

        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-100';
            feedback.dataset.formFeedback = 'true';
            form.prepend(feedback);
        }

        feedback.textContent = message;
    },

    clearFormFeedback(form) {
        form.querySelector('[data-form-feedback]')?.remove();
    },

    resolveErrorMessage(error, fallbackMessage) {
        const validationErrors = error.response?.data?.errors || {};
        const firstMessage = Object.values(validationErrors)
            .flat()
            .find((message) => String(message || '').trim() !== '');

        return firstMessage || error.response?.data?.message || fallbackMessage;
    },

    escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    },

    bindWhatsApp() {
        const toggle = document.getElementById('whatsapp-toggle');
        const box = document.getElementById('whatsapp-support-box');

        if (!toggle || !box) {
            return;
        }

        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            box.classList.toggle('active');
            toggle.classList.toggle('active');
        });

        document.addEventListener('click', (event) => {
            if (!box.contains(event.target) && !toggle.contains(event.target)) {
                box.classList.remove('active');
                toggle.classList.remove('active');
            }
        });

        const tabButtons = Array.from(document.querySelectorAll('[data-portal-support-tab]'));
        const panels = Array.from(document.querySelectorAll('[data-portal-support-panel]'));
        if (tabButtons.length > 0) {
            tabButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const target = button.dataset.portalSupportTab || '';
                    tabButtons.forEach((item) => item.classList.toggle('is-active', item === button));
                    panels.forEach((panel) => panel.classList.toggle('is-active', panel.dataset.portalSupportPanel === target));
                });
            });
        }
    },

    bindPortalNotifications() {
        const body = document.body;
        const toggle = document.querySelector('[data-portal-notifications-toggle]');
        const dropdown = document.querySelector('[data-portal-notifications-dropdown]');
        const list = document.querySelector('[data-portal-notifications-list]');
        const badge = document.querySelector('[data-portal-notifications-badge]');
        const readAllButton = document.querySelector('[data-portal-notifications-read-all]');
        const feedUrl = body.dataset.portalNotificationsUrl;
        const readUrl = body.dataset.portalNotificationsReadUrl;

        if (!toggle || !dropdown || !list || !badge || !feedUrl) {
            return;
        }

        const updateBadge = (count) => {
            const unread = Number(count || 0);
            badge.textContent = String(unread);
            badge.classList.toggle('is-hidden', unread <= 0);
        };

        const renderList = (items) => {
            if (!Array.isArray(items) || items.length === 0) {
                list.innerHTML = '<div class="portal-notification-empty">Nenhuma notificacao pendente.</div>';
                return;
            }

            list.innerHTML = items.map((item) => `
                <a href="${item.url}" class="portal-notification-item ${item.is_unread ? 'is-unread' : ''}">
                    <strong>${this.escapeHtml(item.title || 'Notificacao')}</strong>
                    <span>${this.escapeHtml(item.subtitle || '')}</span>
                    <small>${this.escapeHtml(item.at_human || '')}</small>
                </a>
            `).join('');
        };

        const fetchFeed = async () => {
            try {
                const response = await window.axios.get(feedUrl, { params: { _: Date.now() } });
                updateBadge(response.data?.unread_count || 0);
                renderList(response.data?.items || []);
            } catch (error) {
                console.error('Falha ao buscar notificacoes do portal.', error);
            }
        };

        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            dropdown.classList.toggle('is-open');
            toggle.classList.toggle('active');
        });

        document.addEventListener('click', (event) => {
            if (!dropdown.contains(event.target) && !toggle.contains(event.target)) {
                dropdown.classList.remove('is-open');
                toggle.classList.remove('active');
            }
        });

        readAllButton?.addEventListener('click', async (event) => {
            event.preventDefault();
            if (!readUrl) {
                return;
            }

            try {
                await window.axios.patch(readUrl);
                updateBadge(0);
                renderList([]);
                showToast('success', 'Notificacoes marcadas como lidas.');
            } catch (error) {
                showToast('error', this.resolveErrorMessage(error, 'Nao foi possivel marcar notificacoes como lidas.'));
            }
        });

        fetchFeed();
        window.setInterval(fetchFeed, 30000);
    },
};

document.addEventListener('DOMContentLoaded', () => SiteUI.boot());
