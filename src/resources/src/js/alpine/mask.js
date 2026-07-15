import Inputmask from "inputmask";

window.Inputmask = Inputmask;

Alpine.data('mask', () => ({
    value: '',
    init() {
        this.run();
    },
    run() {
        const inputmask = new Inputmask.default({
            oncomplete: () => this.updateValue(),
            onincomplete: () => this.updateValue(),
            oncleared: () => this.updateValue()
        });

        inputmask.mask(this.$el);

        this.updateValue();
        this.$el.addEventListener('input', () => this.updateValue());
    },
    updateValue() {
        this.value = this.$el.inputmask.unmaskedvalue();
    }
}));