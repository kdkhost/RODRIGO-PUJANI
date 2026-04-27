import './bootstrap';

const SiteUI = {
    deferredInstallPrompt: null,

    boot() {
        [
            this.bindCursor,
            this.bindNavbar,
            this.bindMobileMenu,
            this.bindObserver,
            this.bindCounters,
            this.bindPhoneMasks,
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

    bindPhoneMasks() {
        document.querySelectorAll('input[type="tel"], [data-mask="phone"]').forEach((input) => {
            if (input.dataset.siteMaskReady) {
                return;
            }

            const applyMask = () => {
                input.value = this.formatPhone(input.value);
            };

            input.addEventListener('input', applyMask);
            input.addEventListener('blur', applyMask);
            applyMask();
            input.dataset.siteMaskReady = 'true';
        });
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
                await window.axios.post(form.action, new FormData(form), {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });

                form.classList.add('hidden');
                successState?.classList.remove('hidden');
                form.reset();
            } catch (error) {
                this.showFormFeedback(
                    form,
                    error.response?.data?.message || 'Não foi possível enviar sua solicitação agora.'
                );
            }
        });
    },

    bindPwa() {
        document.body.classList.toggle(
            'app-installed',
            window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true
        );

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => null);
            });
        }

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            this.deferredInstallPrompt = event;
            document.body.classList.add('pwa-installable');
        });

        document.querySelectorAll('[data-pwa-install]').forEach((button) => {
            button.addEventListener('click', async () => {
                if (!this.deferredInstallPrompt) {
                    return;
                }

                this.deferredInstallPrompt.prompt();
                await this.deferredInstallPrompt.userChoice;
                this.deferredInstallPrompt = null;
                document.body.classList.remove('pwa-installable');
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
};

document.addEventListener('DOMContentLoaded', () => SiteUI.boot());
