/**
 * question-highlighter.js
 * Gerenciador interativo de Marca-Texto para o formulário de cadastro/edição de perguntas do Professor.
 */

export function initQuestionHighlighter() {
    const textSupportEl = document.getElementById('support_text');
    const promptEl = document.getElementById('prompt');
    const previewSupportEl = document.getElementById('preview-support-text');
    const previewPromptEl = document.getElementById('preview-prompt-text');

    const expectedCommandsInput = document.getElementById('expected_commands');
    const expectedCluesInput = document.getElementById('expected_clues');

    if (!textSupportEl || !previewSupportEl || !previewPromptEl) {
        return;
    }

    // Estrutura de dados para marcadores: [{ id, target: 'support'|'prompt', kind: 'command'|'clue', text, start, end }]
    let markers = [];

    // Carrega trechos iniciais se já existirem no input
    function loadInitialFromInputs() {
        const cmdVal = expectedCommandsInput?.value || '';
        const clueVal = expectedCluesInput?.value || '';

        const supportText = textSupportEl.value || '';
        const promptText = promptEl.value || '';

        function matchAndAdd(rawSnippet, kind) {
            const trimmed = rawSnippet.trim();
            if (!trimmed) return;

            // Tenta achar no prompt
            let pIdx = promptText.indexOf(trimmed);
            if (pIdx !== -1) {
                markers.push({
                    id: 'm_' + Math.random().toString(36).substring(2, 9),
                    target: 'prompt',
                    kind: kind,
                    text: trimmed,
                    start: pIdx,
                    end: pIdx + trimmed.length
                });
                return;
            }

            // Tenta achar no supportText
            let sIdx = supportText.indexOf(trimmed);
            if (sIdx !== -1) {
                markers.push({
                    id: 'm_' + Math.random().toString(36).substring(2, 9),
                    target: 'support',
                    kind: kind,
                    text: trimmed,
                    start: sIdx,
                    end: sIdx + trimmed.length
                });
            }
        }

        if (cmdVal) {
            cmdVal.split(';').forEach(s => matchAndAdd(s, 'command'));
        }
        if (clueVal) {
            clueVal.split(';').forEach(s => matchAndAdd(s, 'clue'));
        }
    }

    function syncInputsFromMarkers() {
        const commands = markers.filter(m => m.kind === 'command').map(m => m.text);
        const clues = markers.filter(m => m.kind === 'clue').map(m => m.text);

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
            const cmdMarkers = markers.filter(m => m.kind === 'command');
            if (cmdMarkers.length === 0) {
                cmdContainer.innerHTML = '<span class="text-xs text-cloud-burst-600 italic">Nenhum trecho de comando demarcado ainda.</span>';
            } else {
                cmdMarkers.forEach(m => {
                    const tag = document.createElement('span');
                    tag.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold font-heading bg-broom-200 text-broom-950 border border-broom-300 shadow-2xs';
                    tag.innerHTML = `<span>"${escapeHtml(m.text)}"</span><button type="button" data-remove-id="${m.id}" class="cursor-pointer hover:text-rose-700 ml-1 font-bold">×</button>`;
                    cmdContainer.appendChild(tag);
                });
            }
        }

        if (clueContainer) {
            clueContainer.innerHTML = '';
            const clueMarkers = markers.filter(m => m.kind === 'clue');
            if (clueMarkers.length === 0) {
                clueContainer.innerHTML = '<span class="text-xs text-cloud-burst-600 italic">Nenhum trecho de pista demarcado ainda.</span>';
            } else {
                clueMarkers.forEach(m => {
                    const tag = document.createElement('span');
                    tag.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold font-heading bg-sky-blue-200 text-sky-blue-950 border border-sky-blue-300 shadow-2xs';
                    tag.innerHTML = `<span>"${escapeHtml(m.text)}"</span><button type="button" data-remove-id="${m.id}" class="cursor-pointer hover:text-rose-700 ml-1 font-bold">×</button>`;
                    clueContainer.appendChild(tag);
                });
            }
        }

        // Adiciona listeners para os botões de remover
        document.querySelectorAll('[data-remove-id]').forEach(btn => {
            btn.onclick = (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-remove-id');
                markers = markers.filter(m => m.id !== id);
                updatePreviews();
                syncInputsFromMarkers();
            };
        });
    }

    function renderHighlighted(rawText, targetMarkers) {
        if (!rawText) return '<span class="text-gray-400 italic">Digite o texto acima para poder marcar com o Marca-Texto...</span>';

        // Ordena marcadores por início crescente
        const sorted = [...targetMarkers].sort((a, b) => a.start - b.start);

        let html = '';
        let lastIdx = 0;

        for (const m of sorted) {
            if (m.start < lastIdx) continue; // evita sobreposição corrompida

            // Texto antes da marca
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

    function updatePreviews() {
        const supportText = textSupportEl.value || '';
        const promptText = promptEl.value || '';

        const supportMarkers = markers.filter(m => m.target === 'support');
        const promptMarkers = markers.filter(m => m.target === 'prompt');

        previewSupportEl.innerHTML = renderHighlighted(supportText, supportMarkers);
        previewPromptEl.innerHTML = renderHighlighted(promptText, promptMarkers);

        // Adiciona click nos marks para remoção rápida
        [previewSupportEl, previewPromptEl].forEach(container => {
            container.querySelectorAll('mark[data-mid]').forEach(markEl => {
                markEl.onclick = (e) => {
                    e.stopPropagation();
                    const mid = markEl.getAttribute('data-mid');
                    markers = markers.filter(m => m.id !== mid);
                    updatePreviews();
                    syncInputsFromMarkers();
                };
            });
        });
    }

    function handleSelectionApply(kind) {
        const sel = window.getSelection();
        if (!sel || sel.isCollapsed || sel.rangeCount === 0) {
            alert('Por favor, selecione primeiro com o cursor do mouse um trecho do texto na área interativa abaixo.');
            return;
        }

        const range = sel.getRangeAt(0);
        const selectedText = sel.toString().trim();
        if (!selectedText) return;

        // Identifica em qual pré-visualização a seleção ocorreu
        let target = null;
        let baseRawText = '';

        if (previewSupportEl.contains(range.commonAncestorContainer)) {
            target = 'support';
            baseRawText = textSupportEl.value || '';
        } else if (previewPromptEl.contains(range.commonAncestorContainer)) {
            target = 'prompt';
            baseRawText = promptEl.value || '';
        }

        if (!target) {
            alert('A seleção deve ser feita dentro da área de texto interativa (Texto de Apoio ou Enunciado).');
            return;
        }

        // Encontra o offset do trecho dentro do texto puro
        const startPos = baseRawText.indexOf(selectedText);
        if (startPos === -1) {
            alert('Não foi possível sincronizar o trecho selecionado com o texto original.');
            return;
        }

        const endPos = startPos + selectedText.length;

        // Adiciona o novo marcador
        markers.push({
            id: 'm_' + Math.random().toString(36).substring(2, 9),
            target: target,
            kind: kind,
            text: selectedText,
            start: startPos,
            end: endPos
        });

        sel.removeAllRanges();
        updatePreviews();
        syncInputsFromMarkers();
    }

    function escapeHtml(str) {
        return (str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
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

    // Sincroniza ao digitar nos textareas originais
    textSupportEl.addEventListener('input', () => {
        updatePreviews();
    });
    promptEl.addEventListener('input', () => {
        updatePreviews();
    });

    // Inicialização
    loadInitialFromInputs();
    updatePreviews();
    renderBadgesList();
}
