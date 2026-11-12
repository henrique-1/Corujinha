/**
 * question-highlighter.js
 * Gerenciador interativo de Marca-Texto para o formulário de cadastro/edição de perguntas do Professor.
 * Compatível com texto rico (Bold, Itálico, Sublinhado, Taxado e Hyperlinks) via DOM TreeWalker.
 */

import { getSelectionOffsets, normalizeTextToHtml, renderHtmlWithHighlights } from './html-highlight';
import { openNoticeDialog } from './dialog-modal';

export function initQuestionHighlighter() {
    const textSupportEl = document.getElementById('support_text');
    const promptEl = document.getElementById('prompt');
    const supportTitleEl = document.getElementById('support_title');
    const previewSupportEl = document.getElementById('preview-support-text');
    const previewPromptEl = document.getElementById('preview-prompt-text');
    const previewSupportTitleEl = document.getElementById('preview-support-title');

    const expectedCommandsInput = document.getElementById('expected_commands');
    const expectedCluesInput = document.getElementById('expected_clues');

    if (!textSupportEl || !previewSupportEl || !previewPromptEl) {
        return;
    }

    // Estrutura de dados para marcadores: [{ id, target: 'title'|'support'|'prompt', kind: 'command'|'clue', text, start, end }]
    let markers = [];

    function extractPlainTextFromHtml(htmlOrText) {
        const html = normalizeTextToHtml(htmlOrText || '');
        if (!html) return '';
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        return parsed.body.textContent || '';
    }

    function escapeHtml(str) {
        return (str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Carrega trechos iniciais se já existirem no input
    function loadInitialFromInputs() {
        const cmdVal = expectedCommandsInput?.value || '';
        const clueVal = expectedCluesInput?.value || '';

        const titleText = supportTitleEl?.value || '';
        const supportPlainText = extractPlainTextFromHtml(textSupportEl.value || '');
        const promptText = promptEl.value || '';

        function matchAndAdd(rawSnippet, kind) {
            const trimmed = rawSnippet.trim();
            if (!trimmed) return;

            const tIdx = titleText.indexOf(trimmed);
            if (tIdx !== -1) {
                markers.push({
                    id: 'm_' + Math.random().toString(36).substring(2, 9),
                    target: 'title',
                    kind,
                    text: trimmed,
                    start: tIdx,
                    end: tIdx + trimmed.length,
                });
                return;
            }

            const pIdx = promptText.indexOf(trimmed);
            if (pIdx !== -1) {
                markers.push({
                    id: 'm_' + Math.random().toString(36).substring(2, 9),
                    target: 'prompt',
                    kind,
                    text: trimmed,
                    start: pIdx,
                    end: pIdx + trimmed.length,
                });
                return;
            }

            const sIdx = supportPlainText.indexOf(trimmed);
            if (sIdx !== -1) {
                markers.push({
                    id: 'm_' + Math.random().toString(36).substring(2, 9),
                    target: 'support',
                    kind,
                    text: trimmed,
                    start: sIdx,
                    end: sIdx + trimmed.length,
                });
            }
        }

        if (cmdVal) {
            cmdVal.split(';').forEach((s) => matchAndAdd(s, 'command'));
        }
        if (clueVal) {
            clueVal.split(';').forEach((s) => matchAndAdd(s, 'clue'));
        }
    }

    function syncInputsFromMarkers() {
        const commands = markers.filter((m) => m.kind === 'command').map((m) => m.text);
        const clues = markers.filter((m) => m.kind === 'clue').map((m) => m.text);

        if (expectedCommandsInput) {
            expectedCommandsInput.value = [...new Set(commands)].join('; ');
        }
        if (expectedCluesInput) {
            expectedCluesInput.value = [...new Set(clues)].join('; ');
        }

        renderBadgesList();
    }

    function renderBadgesList() {
        const cmdContainer = document.getElementById('marked-commands-list');
        const clueContainer = document.getElementById('marked-clues-list');

        if (cmdContainer) {
            cmdContainer.innerHTML = '';
            const cmdMarkers = markers.filter((m) => m.kind === 'command');
            if (cmdMarkers.length === 0) {
                cmdContainer.innerHTML = '<span class="text-xs text-cloud-burst-600 italic">Nenhum trecho de comando demarcado ainda.</span>';
            } else {
                cmdMarkers.forEach((m) => {
                    const tag = document.createElement('span');
                    tag.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold font-heading bg-broom-200 text-broom-950 border border-broom-300 shadow-2xs';
                    tag.innerHTML = `<span>"${escapeHtml(m.text)}"</span><button type="button" data-remove-id="${m.id}" class="cursor-pointer hover:text-rose-700 ml-1 font-bold">×</button>`;
                    cmdContainer.appendChild(tag);
                });
            }
        }

        if (clueContainer) {
            clueContainer.innerHTML = '';
            const clueMarkers = markers.filter((m) => m.kind === 'clue');
            if (clueMarkers.length === 0) {
                clueContainer.innerHTML = '<span class="text-xs text-cloud-burst-600 italic">Nenhum trecho de pista demarcado ainda.</span>';
            } else {
                clueMarkers.forEach((m) => {
                    const tag = document.createElement('span');
                    tag.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold font-heading bg-sky-blue-200 text-sky-blue-950 border border-sky-blue-300 shadow-2xs';
                    tag.innerHTML = `<span>"${escapeHtml(m.text)}"</span><button type="button" data-remove-id="${m.id}" class="cursor-pointer hover:text-rose-700 ml-1 font-bold">×</button>`;
                    clueContainer.appendChild(tag);
                });
            }
        }

        document.querySelectorAll('[data-remove-id]').forEach((btn) => {
            btn.onclick = (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-remove-id');
                markers = markers.filter((m) => m.id !== id);
                updatePreviews();
                syncInputsFromMarkers();
            };
        });
    }

    function renderPlainHighlighted(rawText, targetMarkers) {
        if (!rawText) return '<span class="text-gray-400 italic">Digite o texto acima para poder marcar com o Marca-Texto...</span>';

        const sorted = [...targetMarkers].sort((a, b) => a.start - b.start);

        let html = '';
        let lastIdx = 0;

        for (const m of sorted) {
            if (m.start < lastIdx) continue;

            html += escapeHtml(rawText.substring(lastIdx, m.start));

            const markClass = m.kind === 'command'
                ? 'bg-broom-400 text-broom-950 font-bold px-1 py-0.5 rounded shadow-2xs'
                : 'bg-sky-blue-300 text-sky-blue-950 font-bold px-1 py-0.5 rounded shadow-2xs';

            const snippet = escapeHtml(rawText.substring(m.start, m.end));
            html += `<mark data-mid="${m.id}" class="${markClass} cursor-pointer hover:opacity-80 transition" title="Clique para remover">${snippet}</mark>`;
            lastIdx = m.end;
        }

        html += escapeHtml(rawText.substring(lastIdx));
        return html;
    }

    function buildSupportMarkElement(m, sliceText) {
        const mark = document.createElement('mark');
        mark.setAttribute('data-mid', m.id);
        mark.className = (m.kind === 'command'
            ? 'bg-broom-400 text-broom-950 font-bold px-1 py-0.5 rounded shadow-2xs'
            : 'bg-sky-blue-300 text-sky-blue-950 font-bold px-1 py-0.5 rounded shadow-2xs')
            + ' cursor-pointer hover:opacity-80 transition';
        mark.title = 'Clique para remover';
        mark.textContent = sliceText;
        return mark;
    }

    function bindMarkRemoval(container) {
        if (!container) return;
        container.querySelectorAll('mark[data-mid]').forEach((markEl) => {
            markEl.onclick = (e) => {
                e.stopPropagation();
                e.preventDefault();
                const mid = markEl.getAttribute('data-mid');
                markers = markers.filter((m) => m.id !== mid);
                updatePreviews();
                syncInputsFromMarkers();
            };
        });
    }

    async function updatePreviews() {
        const titleText = supportTitleEl?.value || '';
        const supportRawHtml = textSupportEl.value || '';
        const promptText = promptEl.value || '';

        const titleMarkers = markers.filter((m) => m.target === 'title');
        const supportMarkers = markers.filter((m) => m.target === 'support');
        const promptMarkers = markers.filter((m) => m.target === 'prompt');

        if (previewSupportTitleEl) {
            previewSupportTitleEl.innerHTML = renderPlainHighlighted(titleText, titleMarkers);
            bindMarkRemoval(previewSupportTitleEl);
        }

        const hasSupportContent = extractPlainTextFromHtml(supportRawHtml).trim() !== '';
        if (!hasSupportContent) {
            previewSupportEl.innerHTML = '<span class="text-gray-400 italic">Digite o texto acima para poder marcar com o Marca-Texto...</span>';
        } else {
            await renderHtmlWithHighlights(previewSupportEl, supportRawHtml, supportMarkers, buildSupportMarkElement);
            bindMarkRemoval(previewSupportEl);
            // Evita clique acidental em hyperlinks dentro da pré-visualização de marcação
            previewSupportEl.querySelectorAll('a').forEach((a) => {
                a.addEventListener('click', (e) => e.preventDefault());
            });
        }

        previewPromptEl.innerHTML = renderPlainHighlighted(promptText, promptMarkers);
        bindMarkRemoval(previewPromptEl);
    }

    function handleSelectionApply(kind) {
        const sel = window.getSelection();
        if (!sel || sel.isCollapsed || sel.rangeCount === 0) {
            openNoticeDialog({
                title: 'Selecione um trecho',
                message: 'Por favor, selecione primeiro com o cursor do mouse um trecho do texto na área interativa abaixo.',
                tone: 'warning',
            });
            return;
        }

        const range = sel.getRangeAt(0);
        const selectedText = sel.toString().trim();
        if (!selectedText) return;

        let target = null;
        let containerEl = null;
        let baseRawText = '';

        if (previewSupportTitleEl && previewSupportTitleEl.contains(range.commonAncestorContainer)) {
            target = 'title';
            containerEl = previewSupportTitleEl;
            baseRawText = supportTitleEl?.value || '';
        } else if (previewSupportEl.contains(range.commonAncestorContainer)) {
            target = 'support';
            containerEl = previewSupportEl;
        } else if (previewPromptEl.contains(range.commonAncestorContainer)) {
            target = 'prompt';
            containerEl = previewPromptEl;
            baseRawText = promptEl.value || '';
        }

        if (!target || !containerEl) {
            openNoticeDialog({
                title: 'Área inválida',
                message: 'A seleção deve ser feita dentro da área de texto interativa (Título, Texto de Apoio ou Enunciado).',
                tone: 'warning',
            });
            return;
        }

        let startPos;
        let endPos;
        let finalSnippet = selectedText;

        if (target === 'support') {
            const offsets = getSelectionOffsets(containerEl, range);
            if (!offsets) {
                openNoticeDialog({
                    title: 'Erro na seleção',
                    message: 'Não foi possível sincronizar o trecho selecionado com o texto de apoio.',
                    tone: 'warning',
                });
                return;
            }
            startPos = offsets.start;
            endPos = offsets.end;
            finalSnippet = offsets.text.trim();
        } else {
            startPos = baseRawText.indexOf(selectedText);
            if (startPos === -1) {
                openNoticeDialog({
                    title: 'Erro na seleção',
                    message: 'Não foi possível sincronizar o trecho selecionado com o texto original.',
                    tone: 'warning',
                });
                return;
            }
            endPos = startPos + selectedText.length;
        }

        markers.push({
            id: 'm_' + Math.random().toString(36).substring(2, 9),
            target,
            kind,
            text: finalSnippet,
            start: startPos,
            end: endPos,
        });

        sel.removeAllRanges();
        updatePreviews();
        syncInputsFromMarkers();
    }

    // Binds de botões de marcação
    const btnMarkCommand = document.getElementById('btn-mark-command');
    const btnMarkClue = document.getElementById('btn-mark-clue');
    const btnClearAll = document.getElementById('btn-clear-highlights');

    if (btnMarkCommand) {
        btnMarkCommand.onclick = () => handleSelectionApply('command');
    }
    if (btnMarkClue) {
        btnMarkClue.onclick = () => handleSelectionApply('clue');
    }
    if (btnClearAll) {
        btnClearAll.onclick = () => {
            markers = [];
            updatePreviews();
            syncInputsFromMarkers();
        };
    }

    if (supportTitleEl) {
        supportTitleEl.addEventListener('input', () => updatePreviews());
    }
    textSupportEl.addEventListener('input', () => updatePreviews());
    promptEl.addEventListener('input', () => updatePreviews());

    loadInitialFromInputs();
    updatePreviews();
    renderBadgesList();
}
