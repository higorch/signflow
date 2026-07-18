document.addEventListener('alpine:init', () => {

    Alpine.data('panel', () => ({
        menuOpen: localStorage.getItem('menuOpen') !== 'false',
        mobileMenu: false,
        events: {
            ['@open-modal-create.window']() {
                this.$dispatch('open-modal', {
                    ref: 'modal-create',
                    payload: {}
                });
            },
            ['@open-modal-files-upload.window']() {
                this.$dispatch('open-modal', {
                    ref: 'modal-files-upload',
                    payload: {
                        processId: this.$event.detail.processId
                    }
                });
            }
        },
        init() {
            this.$watch('menuOpen', v => localStorage.setItem('menuOpen', v));
        }
    }));
});