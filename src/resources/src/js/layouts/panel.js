document.addEventListener('alpine:init', () => {

    Alpine.data('panel', () => ({
        menuOpen: localStorage.getItem('menuOpen') !== 'false',
        mobileMenu: false,
        events: {},
        init() {
            this.$watch('menuOpen', v => localStorage.setItem('menuOpen', v));
        }
    }));
});