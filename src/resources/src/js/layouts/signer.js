document.addEventListener('alpine:init', () => {

    Alpine.data('signer', () => ({
        events: {
            ['@open-modal-process-rejected.window']() {
                this.$dispatch('open-modal', {
                    ref: 'modal-process-rejected',
                    payload: {
                        processSignerId: this.$event.detail.processSignerId
                    }
                });
            },
        }
    }));

});