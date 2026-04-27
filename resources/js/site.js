import './bootstrap';

import Chart from 'chart.js/auto';
import {
    appendRecaptchaToken,
    applyAutoPlaceholders,
    bindRecaptchaForms,
    configureToastr,
    flushPageToasts,
    showToast,
} from './shared/ui';

const SiteUI = {
    deferredInstallPrompt: null,

    boot() {
        configureToastr();
        flushPageToasts();
        applyAutoPlaceholders();
        bindRecaptchaForms(document);
        this.initCharts();

        [
            this.bindCursor,
            this.bindNavbar,
            this.bindMobileMenu,
            this.bindObserver,
            this.bindCounters,
            this.bindInputMasks,
            this.bindParallax,
            this.bindContactForm,
            this.bindPwa,
        ].forEach((task) => {
            try {
                task.call(this);
            } catch (error) {
                console.error('Falha ao iniciar recurso visual do site.', error);
            }
        });
    },

    initCharts() {
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

        setInstalledState(installed);

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                if (pwaEnabled) {
                    navigator.serviceWorker.register('/sw.js').catch(() => null);
                    return;
                }

                navigator.serviceWorker.getRegistrations()
                    .then((registrations) => Promise.all(registrations.map((registration) => registration.unregister())))
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
};

document.addEventListener('DOMContentLoaded', () => SiteUI.boot());
