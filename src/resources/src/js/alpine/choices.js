import Choices from 'choices.js';

window.Choices = Choices;

Alpine.data('choices', (value, placeholder = 'N/A', customClass = '', position = 'auto', removeItemButton = true) => ({
    value: value,
    placeholder: placeholder,
    customClass: customClass,
    position: position,
    removeItemButton: removeItemButton,
    choicesInstance: null,
    init() {
        this.run();
    },
    run() {
        this.removeActiveItems();

        const classNames = {
            containerOuter: ['app-choices']
        };

        if (this.customClass) {
            classNames.containerOuter.push(this.customClass);
        }

        this.choicesInstance = new Choices(this.$el, {
            removeItemButton: this.removeItemButton,
            placeholderValue: this.placeholder,
            shouldSort: false,
            position: this.position,
            itemSelectText: false,
            classNames: classNames,
            loadingText: "Carregando...",
            noResultsText: "Nenhum resultado encontrado",
            noChoicesText: "Nenhuma opção disponível",
            addItemText: (value) => `Pressione Enter para adicionar <b>"${value}"</b>`,
            removeItemLabelText: (value) => `Remover item: ${value}`,
            maxItemText: (max) => `Somente ${max} valores podem ser adicionados`,
        });

        // Define o valor inicial
        if (this.value) this.choicesInstance.setChoiceByValue(this.value.toString());

        // Evento acionado quando houver mudança
        this.$el.addEventListener('change', () => {
            this.value = this.choicesInstance.getValue(true) || "";
        });
    },
    destroy() {
        if (this.choicesInstance) {
            this.choicesInstance.destroy();
            this.choicesInstance = null;
        }
    },
    removeActiveItems() {
        if (this.choicesInstance) {
            this.choicesInstance.removeActiveItems();
            this.value = '';
        }
    },
    disable() {
        if (this.choicesInstance) {
            this.choicesInstance.disable();
        }
    },
    enable() {
        if (this.choicesInstance) {
            this.choicesInstance.enable();
        }
    }
}));
