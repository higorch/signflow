import { computePosition, autoUpdate, flip, offset } from "@floating-ui/dom";

// ALPINE
import '@alpine/choices';
import '@alpine/intl-tel-input';
import '@alpine/mask';
import '@alpine/sortable';

// UTILS
import '@utils/currency';
import '@utils/debounce';
import '@utils/modal-confirm';
import '@utils/percent';
import '@utils/notify';

// LAYOUTS
import '@layouts/signer';
import '@layouts/panel';

Livewire.directive('confirm-modal', ({ el, directive, cleanup }) => {

    const click = (e) => {

        if (el.dataset.confirmed === 'true') {
            el.dataset.confirmed = 'false';
            return;
        }

        e.preventDefault();
        e.stopImmediatePropagation();

        let title = 'Confirmar ação';
        let message = 'Tem certeza que deseja continuar?';

        if (directive.expression) {
            const value = directive.expression.replace(/^['"]|['"]$/g, '');
            const [customTitle, customMessage] = value.split('|');

            title = customTitle?.trim() || title;
            message = customMessage?.trim() || message;
        }

        window.confirmModal({
            title,
            message,
            onConfirm() {
                el.dataset.confirmed = 'true';
                el.click();
            }
        });

    };

    el.addEventListener('click', click, true);

    cleanup(() => {
        el.removeEventListener('click', click, true);
    });

});

// START IMPLEMENTATION
document.addEventListener('alpine:init', () => {

    Alpine.magic('clipboard', () => {
        return (text) => {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                return navigator.clipboard.writeText(text)
            } else {
                const el = document.createElement('textarea')
                el.value = text
                document.body.appendChild(el)
                el.select()
                try {
                    document.execCommand('copy')
                    document.body.removeChild(el)
                    return Promise.resolve()
                } catch (e) {
                    document.body.removeChild(el)
                    return Promise.reject(e)
                }
            }
        }
    });

    Alpine.data('dropdown', (placement = 'left', strategy = 'absolute', distance = 0, manualCloseOnly = false) => ({
        open: false,
        cleanup: null,
        placeholder: null,
        originalParent: null,
        nextSibling: null,
        init() {
            this.$nextTick(() => this.setup());
        },
        setup() {
            const referenceEl = this.$refs.referenceDropdown;
            const floatingEl = this.$refs.floatingDropdown;

            if (!referenceEl || !floatingEl) return;

            this.$watch('open', (isOpen) => {
                if (isOpen) {
                    this.mountToBody(floatingEl);
                    this.position(referenceEl, floatingEl);
                    this.enableAutoUpdate(referenceEl, floatingEl);

                    this.closeOnEscape();
                    this.closeOnScroll();
                } else {
                    this.restoreToOrigin(floatingEl);
                    this.stopAutoUpdate();
                    this.removeGlobalListeners();
                }
            });

            this.$el.addEventListener('alpine:destroy', () => {
                this.restoreToOrigin(floatingEl);
                this.stopAutoUpdate();
                this.removeGlobalListeners();
            });
        },
        mountToBody(el) {
            if (this.placeholder) return;

            this.placeholder = document.createComment('dropdown-placeholder');
            this.originalParent = el.parentNode;
            this.nextSibling = el.nextSibling;

            this.originalParent.insertBefore(this.placeholder, this.nextSibling);
            document.body.appendChild(el);

            el.classList.remove('hidden');
            el.classList.add('flex');
            el.style.position = strategy;
            el.style.zIndex = Date.now();
        },
        restoreToOrigin(el) {
            if (!this.placeholder) return;

            el.classList.add('hidden');
            el.classList.remove('flex');

            this.originalParent.insertBefore(el, this.placeholder);
            this.placeholder.remove();

            this.placeholder = null;
            this.originalParent = null;
            this.nextSibling = null;
        },
        position(referenceEl, floatingEl) {
            computePosition(referenceEl, floatingEl, {
                strategy,
                placement,
                middleware: [flip(), offset(distance)],
            }).then(({ x, y }) => {
                Object.assign(floatingEl.style, {
                    left: `${x}px`,
                    top: `${y}px`,
                });
            });
        },
        enableAutoUpdate(referenceEl, floatingEl) {
            this.cleanup = autoUpdate(referenceEl, floatingEl, () => {
                this.position(referenceEl, floatingEl);
            });
        },
        stopAutoUpdate() {
            if (typeof this.cleanup === 'function') {
                this.cleanup();
                this.cleanup = null;
            }
        },
        closeOnEscape() {
            this._escapeHandler = (e) => {
                if (manualCloseOnly) return;
                if (e.key === 'Escape') this.open = false;
            };

            window.addEventListener('keydown', this._escapeHandler);
        },
        closeOnScroll() {
            this._scrollHandler = () => {
                if (manualCloseOnly) return;
                this.open = false;
            };

            window.addEventListener('scroll', this._scrollHandler, true);
            window.addEventListener('resize', this._scrollHandler);
        },
        removeGlobalListeners() {
            window.removeEventListener('keydown', this._escapeHandler);
            window.removeEventListener('scroll', this._scrollHandler, true);
            window.removeEventListener('resize', this._scrollHandler);
        }
    }));

    Alpine.data('modal', (ref) => ({
        open: false,
        ref: ref,
        payload: {},
        events: {
            ['@open-modal.window']() {
                if (this.$event.detail.ref == this.ref) {
                    this.payload = this.$event.detail; // guarda tudo
                    this.open = true;
                }
            },
            ['@close-modal.window']() {
                if (this.$event.detail.ref == this.ref) this.open = false;
            },
            ['@keydown.escape.window']() {
                if (this.open) this.open = false;
            }
        },
        init() {
            this.$watch('open', (value) => {
                document.body.style.overflow = value ? 'hidden' : 'auto';

                if (value === false) {
                    this.$dispatch('closed.' + this.ref, this.payload);
                } else if (value === true) {
                    this.$dispatch('opened.' + this.ref, this.payload); // agora vai com dados
                }

                if (value === true) {
                    this.$root.style.zIndex = this.maxZIndex + 1;
                }
            });
        },
        get maxZIndex() {
            let maxZ = 0;
            document.querySelectorAll('*').forEach(el => {
                const zIndex = parseInt(window.getComputedStyle(el).zIndex, 10);
                if (!isNaN(zIndex) && zIndex > maxZ) {
                    maxZ = zIndex;
                }
            });
            return maxZ;
        }
    }));

    Alpine.data('limitInput', (limit, model, stop = false) => ({
        limit,
        stop,
        length: 0,
        init() {
            const selector = `
                input[x-model*='${model}'], 
                input[wire\\:model*='${model}'], 
                textarea[x-model*='${model}'], 
                textarea[wire\\:model*='${model}']
            `;

            // 👉 pega TODOS os inputs que casam
            const inputs = document.querySelectorAll(selector.trim());

            if (!inputs.length) return;

            inputs.forEach((input) => {

                const updateLength = () => {
                    const value = input.value;

                    if (value?.length > this.limit && this.stop) {
                        input.value = value.substring(0, this.limit);
                        input.dispatchEvent(new Event('input'));
                        this.length = this.limit;
                    } else {
                        this.length = value?.length || 0;
                    }
                };

                updateLength();

                const events = ['input', 'change', 'paste', 'cut', 'keyup', 'blur'];
                events.forEach(event => {
                    input.addEventListener(event, updateLength);
                });

                Livewire.hook('commit', ({ succeed }) => {
                    succeed(() => {
                        queueMicrotask(updateLength);
                    });
                });

                const observer = new MutationObserver(updateLength);
                observer.observe(input, {
                    attributes: true,
                    attributeFilter: ['value']
                });
            });
        }
    }));

    // <a href="#" x-data="confirmModal('Excluir usuário', 'Deseja realmente excluir este usuário?', 'removeUser', '{{ $user->ulid }}' )">
    //     <i class="las la-trash"></i>Excluir
    // </a>
    Alpine.data('confirmModal', (title = 'Confirmar ação', message = 'Tem certeza que deseja continuar?', callback = null, ...params) => ({
        init() {
            this.$el.addEventListener('click', (e) => {
                e.preventDefault();

                window.confirmModal({
                    title,
                    message,
                    onConfirm: () => {
                        if (callback && typeof this[callback] === 'function') {
                            this[callback](...params);
                        }
                    }
                });
            });
        }
    }));

    Alpine.data('passwordSecurity', (password) => ({
        password: password,
        get hasMin() { return this.password.length >= 8 },
        get hasLower() { return /[a-z]/.test(this.password) },
        get hasUpper() { return /[A-Z]/.test(this.password) },
        get hasNumber() { return /[0-9]/.test(this.password) },
        get hasSpecial() { return /[@$!%*#?&]/.test(this.password) },
        get valid() { return this.hasMin && this.hasLower && this.hasUpper && this.hasNumber && this.hasSpecial }
    }));
});

// END IMPLEMENTATION