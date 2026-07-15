import { Sortable, Swappable, Plugins } from '@shopify/draggable';

window.Draggable = { Sortable, Swappable, Plugins };

Alpine.data('sortable', (componentName) => ({
    componentName: componentName,
    init() {
        const sortable = new Draggable.Sortable(this.$root, {
            draggable: '[data-sortable-item]',
            handle: '[data-sortable-handle]',
            mirror: {
                constrainDimensions: true
            },
            plugins: [Draggable.Plugins.SortAnimation],
            swapAnimation: {
                duration: 200,
                easingFunction: 'ease-in-out',
            },
        });

        sortable.on('sortable:start', (event) => {
            event.startContainer.setAttribute('x-ignore', '');

            const id = event.dragEvent.source.closest('[data-sortable-item]')?.dataset.sortableItem;

            this.$dispatch('sortable:start', {
                componentName: this.componentName,
                id
            });
        });

        sortable.on('sortable:stop', (event) => {
            event.newContainer.removeAttribute('x-ignore');

            requestAnimationFrame(() => {
                const seen = new Set();
                const ids = [...event.newContainer.children].map(el => el.dataset.sortableItem).filter(id => id && !seen.has(id) && seen.add(id));

                this.$dispatch('sortable:stop', {
                    componentName: this.componentName,
                    ids
                });
            });
        });
    }
}));