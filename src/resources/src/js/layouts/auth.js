document.addEventListener('alpine:init', () => {

    Alpine.data('auth', () => ({
        events: {},
        init() { },
    }));
});