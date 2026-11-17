import katex from 'katex';

/**
 * Renderiza delimitadores LaTeX ($...$, $$...$$, \(...\), \[...\]) ou fórmulas puras em HTML seguro usando KaTeX.
 */
export function renderMathInText(text) {
    if (!text || typeof text !== 'string') return '';

    // 1. $$ Display Mode $$
    let result = text.replace(/\$\$([\s\S]+?)\$\$/g, (match, formula) => {
        try {
            return katex.renderToString(formula.trim(), { displayMode: true, throwOnError: false });
        } catch (e) {
            return match;
        }
    });

    // 2. \[ Display Mode \]
    result = result.replace(/\\\[([\s\S]+?)\\\]/g, (match, formula) => {
        try {
            return katex.renderToString(formula.trim(), { displayMode: true, throwOnError: false });
        } catch (e) {
            return match;
        }
    });

    // 3. $ Inline Mode $
    result = result.replace(/\$([^\$\n]+?)\$/g, (match, formula) => {
        try {
            return katex.renderToString(formula.trim(), { displayMode: false, throwOnError: false });
        } catch (e) {
            return match;
        }
    });

    // 4. \( Inline Mode \)
    result = result.replace(/\\\(([\s\S]+?)\\\)/g, (match, formula) => {
        try {
            return katex.renderToString(formula.trim(), { displayMode: false, throwOnError: false });
        } catch (e) {
            return match;
        }
    });

    return result;
}

/**
 * Renderiza uma fórmula LaTeX pura em um container ou string HTML.
 */
export function renderFormula(formula, displayMode = false) {
    if (!formula || typeof formula !== 'string') return '';
    try {
        const clean = formula.trim().replace(/^[\$]+|[\$]+$/g, '');
        return katex.renderToString(clean, { displayMode, throwOnError: false });
    } catch (e) {
        return formula;
    }
}

/**
 * Renderiza texto contendo fórmulas com delimitadores, comandos LaTeX nus (ex: \frac{a}{b}, x^2) ou expressões matemáticas.
 * Preserva formatação HTML sem quebrar tags existentes.
 */
export function renderMathOrText(text) {
    if (!text || typeof text !== 'string') return '';
    let trimmed = text.trim();
    if (!trimmed) return '';

    const hasHtml = /<[a-z][\s\S]*>/i.test(trimmed);

    // Verificação de fórmula pura (sem tags HTML e sem delimitadores explícitos)
    if (!hasHtml && !trimmed.includes('$') && !trimmed.includes('\\(') && !trimmed.includes('\\[')) {
        const isBareCommand = /^\\(frac|sqrt|pm|times|div|pi|le|ge|ne|approx|infty|left)/.test(trimmed);
        const isMathExpression = /^[\w\d\s\+\-\*\/\=\^\_\(\)\{\}\\\.\,]+$/.test(trimmed) && /[\\^\_\/]/.test(trimmed) && !/[a-zA-Z]{4,}/.test(trimmed);
        if (isBareCommand || isMathExpression) {
            try {
                return katex.renderToString(trimmed, { displayMode: false, throwOnError: false });
            } catch (e) {
                // segue para substituição geral em caso de erro
            }
        }
    }

    // 1. Substituição de delimitadores tradicionais
    trimmed = renderMathInText(trimmed);

    // 2. Comandos LaTeX nus ou potências/índices fora de tags HTML
    const parts = trimmed.split(/(<[^>]+>)/g);
    for (let i = 0; i < parts.length; i++) {
        if (!parts[i].startsWith('<')) {
            const bareCommandRegex = /(\\(?:frac\{[^{}]*\}\{[^{}]*\}|sqrt(?:\[[^{}]*\])?\{[^{}]*\}|left\([\s\S]*?\\right\)|pm|times|div|pi|le|ge|ne|approx|infty)(?:[\^_]\{?[0-9a-zA-Z+\-]*\}?)?|[a-zA-Z0-9]+[\^_]\{?[0-9a-zA-Z+\-]+\}?)/g;
            parts[i] = parts[i].replace(bareCommandRegex, (match) => {
                try {
                    return katex.renderToString(match, { displayMode: false, throwOnError: false });
                } catch (e) {
                    return match;
                }
            });
        }
    }

    return parts.join('');
}

/**
 * Procura todos os elementos com data-math que não sejam previews dinâmicos e renderiza fórmulas matemáticas.
 */
export function renderAllMathElements(container = document) {
    const elements = container.querySelectorAll('[data-math]:not([id*="preview"])');
    elements.forEach((el) => {
        const raw = el.dataset.rawMath || el.innerHTML;
        if (!el.dataset.rawMath) {
            el.dataset.rawMath = raw;
        }
        el.innerHTML = renderMathOrText(el.dataset.rawMath);
    });
}

/**
 * Atualiza o preview de fórmulas do Texto de Apoio (#math-support-preview).
 */
export function updateSupportMathPreview() {
    const preview = document.getElementById('math-support-preview');
    if (!preview) return;

    let content = '';
    if (window.__SUPPORT_TEXT_EDITOR__ && !window.__SUPPORT_TEXT_EDITOR__.isEmpty) {
        content = window.__SUPPORT_TEXT_EDITOR__.getHTML();
    } else {
        const textarea = document.getElementById('support_text');
        if (textarea && textarea.value.trim()) {
            content = textarea.value.trim();
        }
    }

    if (!content || content === '<p></p>') {
        preview.innerHTML = '<span class="text-xs text-cloud-burst-400 italic">Pré-visualização das fórmulas do texto de apoio aparecerá aqui...</span>';
        return;
    }

    const rendered = renderMathOrText(content);
    preview.innerHTML = `
        <div class="flex items-center gap-1.5 text-xs font-bold font-heading text-royal-purple-900 mb-1.5">
            <svg class="w-3.5 h-3.5 text-royal-purple-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            <span>Pré-visualização do Texto de Apoio (KaTeX):</span>
        </div>
        <div class="math-preview-rendered-body text-cloud-burst-950 prose max-w-none text-sm">${rendered}</div>
    `;
}

/**
 * Configura um campo de entrada com preview em tempo real.
 */
export function setupMathInput(inputId, previewId, label = null) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (!input || !preview) return;

    function updatePreview() {
        const val = input.value.trim();
        if (!val) {
            preview.innerHTML = '<span class="text-xs text-cloud-burst-400 italic">Preview da fórmula aparecerá aqui...</span>';
            return;
        }

        const rendered = renderMathOrText(val);
        if (label) {
            preview.innerHTML = `
                <div class="flex items-center gap-1.5 text-xs font-bold font-heading text-royal-purple-900 mb-1">
                    <svg class="w-3.5 h-3.5 text-royal-purple-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>Preview (${label}):</span>
                </div>
                <div class="math-preview-rendered-body text-cloud-burst-950">${rendered}</div>
            `;
        } else {
            preview.innerHTML = rendered;
        }
    }

    input.addEventListener('input', updatePreview);
    input.addEventListener('change', updatePreview);
    updatePreview();
}

/**
 * Configura preview matemático para campos de alternativas de múltipla escolha.
 */
export function setupOptionMathInputs(optionsContainerSelector = '#bloco-alternativas') {
    const container = document.querySelector(optionsContainerSelector) || document.querySelector('#bloco-multipla-escolha');
    if (!container) return;

    container.querySelectorAll('.option-text-input').forEach((input, idx) => {
        const preview = document.getElementById(`option-preview-${idx}`);
        if (!preview) return;

        function updateOptionPreview() {
            const val = input.value.trim();
            if (!val) {
                preview.classList.add('hidden');
                preview.innerHTML = '';
                return;
            }
            if (val.includes('$') || val.includes('\\') || val.includes('^') || val.includes('_') || val.includes('/')) {
                preview.classList.remove('hidden');
                preview.innerHTML = `
                    <div class="flex items-center gap-1 text-[10px] font-bold text-royal-purple-700 mb-0.5">
                        <svg class="w-3 h-3 text-royal-purple-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <span>Preview KaTeX:</span>
                    </div>
                    <div class="text-cloud-burst-950">${renderMathOrText(val)}</div>
                `;
            } else {
                preview.classList.add('hidden');
                preview.innerHTML = '';
            }
        }

        input.addEventListener('input', updateOptionPreview);
        input.addEventListener('change', updateOptionPreview);
        updateOptionPreview();
    });
}

/**
 * Insere texto na posição do cursor de um input, textarea ou editor complexo TipTap.
 */
export function insertAtCursor(input, textToInsert) {
    if (!input) return;

    if (input.id === 'support_text' && window.__SUPPORT_TEXT_EDITOR__) {
        window.__SUPPORT_TEXT_EDITOR__.chain().focus().insertContent(textToInsert).run();
        updateSupportMathPreview();
        return;
    }

    const start = input.selectionStart ?? input.value.length;
    const end = input.selectionEnd ?? input.value.length;
    const val = input.value || '';
    input.value = val.substring(0, start) + textToInsert + val.substring(end);
    const newPos = start + textToInsert.length;
    input.focus();
    input.setSelectionRange(newPos, newPos);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

/**
 * Inicializa e vincula todas as barras de símbolos matemáticos da página.
 */
export function initAllMathToolbars() {
    document.querySelectorAll('.math-symbols-toolbar').forEach((toolbar) => {
        const targetId = toolbar.dataset.mathTarget;
        if (!targetId) return;

        toolbar.querySelectorAll('[data-insert-math]').forEach((btn) => {
            if (btn.__mathHandlerAttached) return;
            btn.__mathHandlerAttached = true;

            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const snippet = btn.dataset.insertMath;

                if (targetId === 'support_text' && window.__SUPPORT_TEXT_EDITOR__) {
                    window.__SUPPORT_TEXT_EDITOR__.chain().focus().insertContent(snippet).run();
                    updateSupportMathPreview();
                    return;
                }

                const targetInput = document.getElementById(targetId);
                if (!targetInput) return;

                insertAtCursor(targetInput, snippet);
            });
        });
    });
}

/**
 * Inicializa todo o módulo matemático na página (toolbars, previews e listeners).
 */
export function initMathModule() {
    renderAllMathElements();
    initAllMathToolbars();
    setupMathInput('prompt', 'math-prompt-preview', 'Enunciado');
    setupMathInput('expected_answer', 'math-expected-preview', 'Resposta Esperada');
    setupMathInput('expected_answer_explanation', 'math-explanation-preview', 'Resolução Esperada');
    setupOptionMathInputs();
    updateSupportMathPreview();
}

// Inicializa no carregamento do DOM
if (typeof window !== 'undefined') {
    window.MathHelper = {
        renderMathInText,
        renderMathOrText,
        renderFormula,
        renderAllMathElements,
        updateSupportMathPreview,
        setupMathInput,
        setupOptionMathInputs,
        insertAtCursor,
        initAllMathToolbars,
        initMathModule,
    };
    window.updateSupportMathPreview = updateSupportMathPreview;
}

document.addEventListener('DOMContentLoaded', () => {
    initMathModule();
});
