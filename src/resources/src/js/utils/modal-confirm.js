window.confirmModal = function (options = {}) {

    const {
        title = 'Confirmar ação',
        message = 'Tem certeza que deseja continuar?',
        onConfirm = () => { },
        onCancel = () => { }
    } = options;

    const modal = document.createElement('div');

    modal.className = 'fixed inset-0 overflow-y-auto bg-black/60 z-[9999]';

    modal.innerHTML = `
        <div class="flex items-center justify-center min-h-dvh p-6">
            <div class="relative w-full max-w-lg rounded-md shadow-lg bg-card">
                <span class="close absolute top-4 right-4 text-lg cursor-pointer text-gray-400 hover:text-red-500">
                    <i class="las la-times"></i>
                </span>
                <div class="flex items-center w-full p-4 border-b border-[#fada82]/5">
                    <p class="font-semibold text-lg text-text-soft">${title}</p>
                </div>
                <div class="flex flex-col grow p-4">
                    <p class="text-text-soft/70 leading-relaxed">${message}</p>
                </div>
                <div class="flex gap-4 w-full p-4 border-t border-[#fada82]/5">
                    <button class="cancel flex-1 btn-secuondary">
                        <i class="las la-times text-lg"></i>
                        Cancelar
                    </button>
                    <button class="confirm flex-1 btn-primary">
                        <i class="las la-check text-lg"></i>
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    window.dispatchEvent(new Event('modal:opened'));

    const destroy = () => modal.remove();

    modal.querySelector('.close').onclick = () => {
        destroy();
        onCancel();
    };

    modal.querySelector('.cancel').onclick = () => {
        destroy();
        onCancel();
    };

    modal.onclick = (e) => {
        if (e.target === modal) {
            destroy();
            onCancel();
        }
    };

    modal.querySelector('.confirm').onclick = () => {
        destroy();
        onConfirm();
    };
};