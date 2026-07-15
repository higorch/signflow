import ToastGyn from '../vendors/toast-gyn';

window.ToastGyn = ToastGyn;

window.addEventListener('notify', ({ detail: { type, msg, timer = 5000 } }) => {
    const maxZIndex = [...document.querySelectorAll('*')].reduce((max, el) => Math.max(max, +getComputedStyle(el).zIndex || 0), 0);

    ToastGyn.show(type, msg, timer, maxZIndex + 10);
});