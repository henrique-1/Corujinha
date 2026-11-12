/**
 * dialog-modal.js
 * Sistema centralizado de modais acessíveis usando o elemento nativo <dialog> e showModal(),
 * substituindo confirm(), alert() e prompt() do navegador.
 */

let dialogQueue = Promise.resolve();
let dialogInstance = null;

function getOrCreateDialog() {
    if (dialogInstance && document.body.contains(dialogInstance)) {
        return dialogInstance;
    }

    const dialog = document.createElement('dialog');
    dialog.id = 'corujinha-app-dialog';
    dialog.setAttribute('aria-labelledby', 'corujinha-dialog-title');
    dialog.setAttribute('aria-describedby', 'corujinha-dialog-message');
    dialog.setAttribute('closedby', 'any');
    dialog.className = 'm-auto w-[calc(100%-2rem)] max-w-md rounded-3xl border border-magnolia-200 bg-white p-0 text-cloud-burst-950 shadow-2xl open:flex open:flex-col';

    const form = document.createElement('form');
    form.method = 'dialog';
    form.className = 'p-6 md:p-7 space-y-5';

    const header = document.createElement('div');
    header.className = 'flex items-start gap-3.5';

    const badge = document.createElement('div');
    badge.id = 'corujinha-dialog-badge';
    badge.className = 'w-11 h-11 rounded-2xl flex items-center justify-center shrink-0';

    const textWrap = document.createElement('div');
    textWrap.className = 'space-y-1.5 min-w-0 flex-1';

    const title = document.createElement('h2');
    title.id = 'corujinha-dialog-title';
    title.className = 'text-lg font-extrabold font-heading text-cloud-burst-950 leading-snug';

    const message = document.createElement('p');
    message.id = 'corujinha-dialog-message';
    message.className = 'text-sm text-cloud-burst-700 leading-relaxed whitespace-pre-line';

    textWrap.append(title, message);
    header.append(badge, textWrap);

    const promptWrap = document.createElement('div');
    promptWrap.id = 'corujinha-dialog-prompt-wrap';
    promptWrap.className = 'hidden';

    const promptInput = document.createElement('input');
    promptInput.id = 'corujinha-dialog-input';
    promptInput.type = 'text';
    promptInput.className = 'w-full px-3.5 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none focus:ring-2 focus:ring-royal-purple-800/20';
    promptWrap.appendChild(promptInput);

    const footer = document.createElement('div');
    footer.className = 'flex flex-wrap-reverse sm:flex-nowrap items-center justify-end gap-2.5 pt-2';

    const cancelBtn = document.createElement('button');
    cancelBtn.id = 'corujinha-dialog-cancel';
    cancelBtn.type = 'submit';
    cancelBtn.value = 'cancel';
    cancelBtn.formNoValidate = true;
    cancelBtn.className = 'cursor-pointer w-full sm:w-auto px-4 py-2.5 rounded-xl border border-magnolia-300 bg-white hover:bg-magnolia-50 text-cloud-burst-900 text-xs font-bold font-heading transition';

    const confirmBtn = document.createElement('button');
    confirmBtn.id = 'corujinha-dialog-confirm';
    confirmBtn.type = 'submit';
    confirmBtn.value = 'confirm';
    confirmBtn.className = 'cursor-pointer w-full sm:w-auto px-5 py-2.5 rounded-xl text-white text-xs font-bold font-heading shadow-xs transition';

    footer.append(cancelBtn, confirmBtn);
    form.append(header, promptWrap, footer);
    dialog.appendChild(form);
    document.body.appendChild(dialog);

    // Fallback progressivo para navegadores sem suporte a closedby="any" (ex: Safari)
    if (!('closedBy' in HTMLDialogElement.prototype)) {
        const isOutside = (e) => {
            const rect = dialog.getBoundingClientRect();
            return e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom;
        };
        let pressedOutside = false;
        dialog.addEventListener('pointerdown', (e) => {
            pressedOutside = isOutside(e);
        });
        dialog.addEventListener('pointerup', (e) => {
            if (pressedOutside && isOutside(e)) {
                dialog.close('');
            }
        });
    }

    dialogInstance = dialog;
    return dialog;
}

function applyToneStyles(tone, badgeEl, confirmBtnEl) {
    const toneMap = {
        danger: {
            badge: 'bg-rose-100 text-rose-700 border border-rose-200',
            button: 'bg-rose-600 hover:bg-rose-700 focus-visible:outline-2 focus-visible:outline-rose-600',
            svgPath: '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        },
        warning: {
            badge: 'bg-broom-100 text-broom-900 border border-broom-300',
            button: 'bg-amber-600 hover:bg-amber-700 focus-visible:outline-2 focus-visible:outline-amber-600',
            svgPath: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
        },
        primary: {
            badge: 'bg-royal-purple-100 text-royal-purple-800 border border-royal-purple-200',
            button: 'bg-royal-purple-800 hover:bg-royal-purple-900 focus-visible:outline-2 focus-visible:outline-royal-purple-800',
            svgPath: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        },
    };

    const selected = toneMap[tone] || toneMap.primary;
    badgeEl.className = `w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 ${selected.badge}`;
    badgeEl.innerHTML = `<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${selected.svgPath}</svg>`;
    confirmBtnEl.className = `cursor-pointer w-full sm:w-auto px-5 py-2.5 rounded-xl text-white text-xs font-bold font-heading shadow-xs transition ${selected.button}`;
}

function enqueueDialog({
    mode = 'confirm',
    title = 'Confirmar ação',
    message = '',
    confirmText = 'Confirmar',
    cancelText = 'Cancelar',
    tone = 'danger',
    defaultValue = '',
    placeholder = '',
}) {
    const run = () => new Promise((resolve) => {
        const dialog = getOrCreateDialog();
        const titleEl = dialog.querySelector('#corujinha-dialog-title');
        const messageEl = dialog.querySelector('#corujinha-dialog-message');
        const badgeEl = dialog.querySelector('#corujinha-dialog-badge');
        const promptWrapEl = dialog.querySelector('#corujinha-dialog-prompt-wrap');
        const inputEl = dialog.querySelector('#corujinha-dialog-input');
        const cancelBtnEl = dialog.querySelector('#corujinha-dialog-cancel');
        const confirmBtnEl = dialog.querySelector('#corujinha-dialog-confirm');

        titleEl.textContent = title;
        messageEl.textContent = message;
        cancelBtnEl.textContent = cancelText;
        confirmBtnEl.textContent = confirmText;

        applyToneStyles(tone, badgeEl, confirmBtnEl);

        cancelBtnEl.classList.toggle('hidden', mode === 'alert');
        promptWrapEl.classList.toggle('hidden', mode !== 'prompt');
        inputEl.required = mode === 'prompt';
        inputEl.value = defaultValue;
        inputEl.placeholder = placeholder;

        // Em confirmações destrutivas, priorizar foco na ação segura (Cancelar)
        cancelBtnEl.autofocus = mode === 'confirm';
        confirmBtnEl.autofocus = mode === 'alert';
        inputEl.autofocus = mode === 'prompt';

        dialog.returnValue = '';

        const onClose = () => {
            dialog.removeEventListener('close', onClose);
            const result = dialog.returnValue;
            dialog.returnValue = '';

            if (mode === 'alert') {
                resolve();
            } else if (mode === 'prompt') {
                resolve(result === 'confirm' ? inputEl.value : null);
            } else {
                resolve(result === 'confirm');
            }
        };

        dialog.addEventListener('close', onClose, { once: true });
        dialog.showModal();

        // Garante foco no controle principal apropriado mesmo em aberturas subsequentes
        requestAnimationFrame(() => {
            if (mode === 'prompt') {
                inputEl.focus();
                inputEl.select();
            } else if (mode === 'confirm') {
                cancelBtnEl.focus();
            } else {
                confirmBtnEl.focus();
            }
        });
    });

    const next = dialogQueue.then(run, run);
    dialogQueue = next.catch(() => {});
    return next;
}

export function openConfirmDialog(options) {
    return enqueueDialog({ mode: 'confirm', ...options });
}

export function openNoticeDialog({ title = 'Aviso', message = '', okText = 'Entendi', tone = 'primary' }) {
    return enqueueDialog({
        mode: 'alert',
        title,
        message,
        confirmText: okText,
        tone,
    });
}

export function openPromptDialog(options) {
    return enqueueDialog({ mode: 'prompt', tone: 'primary', ...options });
}

export function initDialogSystem() {
    if (document.documentElement.dataset.corujinhaDialogsReady === 'true') {
        return;
    }
    document.documentElement.dataset.corujinhaDialogsReady = 'true';

    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) {
            return;
        }

        if (form.dataset.confirmBypass === 'true') {
            delete form.dataset.confirmBypass;
            return;
        }

        e.preventDefault();

        const confirmed = await openConfirmDialog({
            title: form.dataset.confirmTitle || 'Confirmar ação',
            message: form.dataset.confirm,
            confirmText: form.dataset.confirmOk || 'Confirmar',
            cancelText: form.dataset.confirmCancel || 'Cancelar',
            tone: form.dataset.confirmTone || 'danger',
        });

        if (confirmed) {
            form.dataset.confirmBypass = 'true';
            form.submit();
        }
    });
}
