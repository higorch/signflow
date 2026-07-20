document.addEventListener('alpine:init', () => {

    Alpine.data('signer', () => ({
        events: {},
        async swiperTags(
            el,
            swiperOptions = {}
        ) {
            if (!el) return;

            const initialize = async () => {
                if (window.Swiper === undefined) await import('@vendors/swiper.js');

                try {
                    if (el._swiper) el._swiper.destroy(true, true);
                } catch (e) { }

                const defaultSwiperOptions = {
                    slidesPerView: 'auto',
                    spaceBetween: 12,
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                        dynamicBullets: true
                    },
                    centeredSlides: false,
                    watchOverflow: true
                };

                const swiperConfig = Object.assign(
                    {},
                    defaultSwiperOptions,
                    swiperOptions
                );

                el._swiper = new Swiper(el, swiperConfig);
            };

            await initialize();

            if (this.$wire && this.$wire.intercept) {
                this.$wire.intercept(({ onFinish }) => {
                    onFinish(() => {
                        this.$nextTick(() => {
                            initialize();
                        });
                    });
                });
            }
        },
        async openGallery(images, index = 0) {
            if (window.lightGallery === undefined) await import('@vendors/gallery.js');

            if (!images.length) return;

            const dynamicEl = images.map(path => ({
                src: path,
                thumb: path,
            }));

            const startIndex = dynamicEl[index] ? index : 0;

            lightGallery(this.$root, {
                dynamic: true,
                dynamicEl,
                plugins: [lgThumbnail, lgZoom, lgFullscreen],
                thumbnail: true,
                zoomFromOrigin: false,
                fullscreen: true,
                download: false,
            }).openGallery(startIndex);
        }
    }));

});