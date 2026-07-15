class ToastGyn {
    #container;

    constructor() {
        this.#container = document.createElement('div');
        this.#container.className = 'flex flex-col gap-3 fixed top-5 right-5';
        document.body.appendChild(this.#container);
    }

    #ensureContainer() {
        if (!this.#container.isConnected) {
            document.body.appendChild(this.#container);
        }
    }

    #createToastElement({ type, message, zIndex }) {
        const toast = document.createElement('div');

        toast.className = `
        group relative flex items-start justify-between gap-3 p-4 text-sm rounded-xl shadow-2xl border backdrop-blur-xl
        transition-all duration-300 ease-out opacity-0 translate-y-5
        ${this.#getTypeClass(type)}
    `;

        if (zIndex) toast.style.zIndex = zIndex;

        toast.innerHTML = `
        <div class="flex-1 pr-2">
            <p class="leading-relaxed text-sm font-medium ${this.#getTextColor(type)}">${message}</p>
        </div>

        <button class="close-toast flex items-center justify-center w-7 h-7 rounded-md transition ${this.#getCloseButtonClass(type)}">✕</button>

        <div class="absolute bottom-0 left-0 w-full h-1 overflow-hidden rounded-b-xl">
            <span class="progress-bar absolute left-0 top-0 h-full ${this.#getProgressBarClass(type)}"></span>
        </div>
    `;

        return toast;
    }

    #getTypeClass(type) {
        return {
            success: 'bg-emerald-500 border-emerald-400/30',
            info: 'bg-sky-500 border-sky-400/30',
            warning: 'bg-amber-500 border-amber-400/30',
            error: 'bg-red-500 border-red-400/30'
        }[type] || 'bg-white/5 border-white';
    }

    #getTextColor(type) {
        return {
            success: 'text-emerald-100',
            info: 'text-sky-100',
            warning: 'text-amber-100',
            error: 'text-red-100'
        }[type] || 'text-[#ffd6a3]';
    }

    #getProgressBarClass(type) {
        return {
            success: 'bg-emerald-400',
            info: 'bg-sky-400',
            warning: 'bg-amber-400',
            error: 'bg-red-400'
        }[type] || 'bg-[#ffd6a3]';
    }

    #getCloseButtonClass(type) {
        return {
            success: 'text-emerald-400 hover:bg-emerald-200',
            info: 'text-sky-400 hover:bg-sky-200',
            warning: 'text-amber-400 hover:bg-amber-200',
            error: 'text-red-400 hover:bg-red-200'
        }[type] || 'text-[#ffd6a3]/60 hover:bg-white';
    }

    #animateToast(toast, action = 'show') {
        if (action === 'show') {
            requestAnimationFrame(() => {
                toast.classList.remove('opacity-0', 'translate-y-5');
                toast.classList.add('opacity-100', 'translate-y-0');
            });
        } else {
            toast.classList.remove('opacity-100', 'translate-y-0');
            toast.classList.add('opacity-0', 'translate-y-5');
        }
    }

    show(type, message, timer = 4000) {
        this.#ensureContainer();

        const z = Date.now();
        this.#container.style.zIndex = z;

        const toast = this.#createToastElement({ type, message });
        const progressBar = toast.querySelector('.progress-bar');
        const closeBtn = toast.querySelector('.close-toast');

        toast.style.zIndex = z + 1;

        let duration = timer;
        let start = null;
        let elapsed = 0;
        let paused = false;
        let animationFrame;

        const step = (timestamp) => {
            if (!start) start = timestamp;

            const progress = timestamp - start + elapsed;
            const percent = Math.max(0, 100 - (progress / duration) * 100);

            progressBar.style.width = percent + '%';

            if (progress < duration && !paused) {
                animationFrame = requestAnimationFrame(step);
            } else if (!paused) {
                this.#removeToast(toast);
            }
        };

        const pause = () => {
            paused = true;
            cancelAnimationFrame(animationFrame);
            elapsed += performance.now() - start;
        };

        const resume = () => {
            if (!paused) return;

            paused = false;
            start = null;
            animationFrame = requestAnimationFrame(step);
        };

        toast.addEventListener('mouseenter', pause);
        toast.addEventListener('mouseleave', resume);
        closeBtn.addEventListener('click', () => this.#removeToast(toast));

        this.#container.appendChild(toast);
        this.#animateToast(toast, 'show');

        animationFrame = requestAnimationFrame(step);
    }

    #removeToast(toast) {
        this.#animateToast(toast, 'hide');

        setTimeout(() => {
            toast.remove();
        }, 300);
    }

}

export default new ToastGyn();