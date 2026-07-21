document.addEventListener('alpine:init', () => {

    Alpine.data('panel', () => ({
        menuOpen: localStorage.getItem('menuOpen') !== 'false',
        mobileMenu: false,
        events: {
            ['@open-modal-process-create.window']() {
                this.$dispatch('open-modal', {
                    ref: 'modal-process-create',
                    payload: {}
                });
            },
            ['@open-modal-process-files-upload.window']() {
                this.$dispatch('open-modal', {
                    ref: 'modal-process-files-upload',
                    payload: {
                        processId: this.$event.detail.processId
                    }
                });
            },
            ['@open-modal-process-signer.window']() {
                this.$dispatch('open-modal', {
                    ref: 'modal-process-signer',
                    payload: {
                        processId: this.$event.detail.processId
                    }
                });
            },
            ['@open-modal-singer-processes.window']() {
                this.$dispatch('open-modal', {
                    ref: 'modal-singer-processes',
                    payload: {
                        signerId: this.$event.detail.signerId
                    }
                });
            },
            ['@open-modal-user-filter.window']() {
                this.$dispatch('open-modal', {
                    ref: 'modal-user-filter',
                    payload: {
                        fields: this.$event.detail.fields
                    }
                });
            },
            ['@open-modal-signer-filter.window']() {
                this.$dispatch('open-modal', {
                    ref: 'modal-signer-filter',
                    payload: {
                        fields: this.$event.detail.fields
                    }
                });
            },
            ['@open-modal-process-filter.window']() {
                this.$dispatch('open-modal', {
                    ref: 'modal-process-filter',
                    payload: {
                        fields: this.$event.detail.fields
                    }
                });
            },
            ['@sortable:stop.window']() {
                const {
                    ids,
                    componentName
                } = this.$event.detail;

                if ('process-files' === componentName) {
                    this.$dispatch('sort-process-files', { ids: ids });
                }

                if ('process-signers' === componentName) {
                    this.$dispatch('sort-process-signers', { ids: ids });
                }
            }
        },
        init() {
            this.$watch('menuOpen', v => localStorage.setItem('menuOpen', v));
        },
        async flatpickrPeriod(el, input) {
            if (window.flatpickr === undefined) await import('@vendors/flatpickr.js');           

            window.flatpickr(el, {
                locale: 'pt',
                dateFormat: 'd/m/Y',
                mode: 'range',
                defaultDate: this.$wire[input]?.from && this.$wire[input]?.to ? [this.$wire[input].from, this.$wire[input].to] : null,
                onChange: (selectedDates) => {
                    if (selectedDates.length !== 2) return;

                    this.$wire.set(`${input}.from`, selectedDates[0].toLocaleDateString('pt-BR'));
                    this.$wire.set(`${input}.to`, selectedDates[1].toLocaleDateString('pt-BR'));
                }
            });
        }
    }));
});