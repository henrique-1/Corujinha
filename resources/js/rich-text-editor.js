import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { normalizeTextToHtml } from './html-highlight';

/**
 * Inicializa o editor complexo (TipTap) conectado ao textarea #support_text
 * com suporte a Bold, Itálico, Sublinhado, Taxado e Hyperlink.
 */
export function initRichTextEditor() {
    const textarea = document.getElementById('support_text');
    const mountEl = document.getElementById('support-text-editor');
    const toolbarEl = document.getElementById('support-text-toolbar');

    if (!textarea || !mountEl || !toolbarEl) {
        return null;
    }

    const initialContent = normalizeTextToHtml(textarea.value || '');

    const editor = new Editor({
        element: mountEl,
        extensions: [
            StarterKit.configure({
                heading: false,
                bulletList: false,
                orderedList: false,
                listItem: false,
                listKeymap: false,
                blockquote: false,
                code: false,
                codeBlock: false,
                horizontalRule: false,
                gapcursor: false,
                link: {
                    openOnClick: false,
                    autolink: true,
                    linkOnPaste: true,
                    defaultProtocol: 'https',
                    HTMLAttributes: {
                        target: '_blank',
                        rel: 'noopener noreferrer',
                    },
                },
            }),
        ],
        content: initialContent,
        editorProps: {
            attributes: {
                class: 'rich-text-content min-h-[150px] px-4 py-3 text-sm text-cloud-burst-950 focus:outline-none',
                'aria-label': 'Corpo do Texto de Apoio',
            },
        },
        onUpdate({ editor: ed }) {
            textarea.value = ed.isEmpty ? '' : ed.getHTML();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            updateToolbarState();
        },
        onSelectionUpdate() {
            updateToolbarState();
        },
        onTransaction() {
            updateToolbarState();
        },
    });

    const buttons = toolbarEl.querySelectorAll('[data-rte-action]');

    function updateToolbarState() {
        buttons.forEach((btn) => {
            const action = btn.dataset.rteAction;
            const isActive = editor.isActive(action);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            if (isActive) {
                btn.classList.add('bg-royal-purple-100', 'text-royal-purple-900', 'border-royal-purple-400');
                btn.classList.remove('bg-white', 'text-cloud-burst-800', 'border-magnolia-300');
            } else {
                btn.classList.remove('bg-royal-purple-100', 'text-royal-purple-900', 'border-royal-purple-400');
                btn.classList.add('bg-white', 'text-cloud-burst-800', 'border-magnolia-300');
            }
        });
    }

    // Dialog nativo para Hyperlink
    const linkDialog = document.getElementById('rte-link-dialog');
    const linkUrlInput = document.getElementById('rte-link-url');
    const linkErrorEl = document.getElementById('rte-link-error');
    const linkApplyBtn = document.getElementById('rte-link-apply');
    const linkRemoveBtn = document.getElementById('rte-link-remove');
    const linkCancelBtn = document.getElementById('rte-link-cancel');

    function openLinkDialog() {
        if (!linkDialog || !linkUrlInput) return;

        const currentHref = editor.getAttributes('link').href || '';
        linkUrlInput.value = currentHref;
        if (linkErrorEl) {
            linkErrorEl.textContent = '';
            linkErrorEl.classList.add('hidden');
        }
        if (linkRemoveBtn) {
            linkRemoveBtn.classList.toggle('hidden', !currentHref);
        }
        linkDialog.showModal();
        linkUrlInput.focus();
    }

    function applyLink() {
        if (!linkUrlInput || !linkDialog) return;

        let rawUrl = linkUrlInput.value.trim();
        if (!rawUrl) {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
            linkDialog.close();
            return;
        }

        if (!/^(https?:\/\/|mailto:)/i.test(rawUrl)) {
            if (/^[a-z0-9.-]+\.[a-z]{2,}/i.test(rawUrl)) {
                rawUrl = `https://${rawUrl}`;
            } else {
                if (linkErrorEl) {
                    linkErrorEl.textContent = 'Informe uma URL válida iniciando com http://, https:// ou mailto:.';
                    linkErrorEl.classList.remove('hidden');
                }
                return;
            }
        }

        editor.chain().focus().extendMarkRange('link').setLink({ href: rawUrl }).run();
        linkDialog.close();
    }

    linkApplyBtn?.addEventListener('click', applyLink);
    linkRemoveBtn?.addEventListener('click', () => {
        editor.chain().focus().extendMarkRange('link').unsetLink().run();
        linkDialog?.close();
    });
    linkCancelBtn?.addEventListener('click', () => linkDialog?.close());
    linkUrlInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyLink();
        }
    });

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const action = btn.dataset.rteAction;
            if (action === 'bold') {
                editor.chain().focus().toggleBold().run();
            } else if (action === 'italic') {
                editor.chain().focus().toggleItalic().run();
            } else if (action === 'underline') {
                editor.chain().focus().toggleUnderline().run();
            } else if (action === 'strike') {
                editor.chain().focus().toggleStrike().run();
            } else if (action === 'link') {
                openLinkDialog();
            }
        });
    });

    updateToolbarState();
    return editor;
}
