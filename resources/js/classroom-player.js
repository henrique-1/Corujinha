/**
 * Player Dinâmico de Avaliação para Estudantes (Corujinha)
 * Integrado ao Backend Laravel, com suporte híbrido a texto rico e imagem de apoio,
 * validação estrita configurável de marca-texto por questão e cronômetro contínuo.
 */

import { getSelectionOffsets, renderHtmlWithHighlights } from './html-highlight';

export function initClassroomPlayer() {
    const config = window.__CLASSROOM_DATA__;
    if (!config || !Array.isArray(config.questions) || config.questions.length === 0) {
        return;
    }

    const DATA = config.questions;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const fresh = (q) => {
        const timeLimit = (q.time_limit_seconds || 180) * 1000;
        return {
            timeLimit,
            remaining: q.saved ? timeLimit - (q.saved.timeUsed || 0) : timeLimit,
            deadline: null,
            started: false,
            done: Boolean(q.saved),
            timeUsed: q.saved?.timeUsed || 0,
            answer: q.saved?.answer || '',
            reason: q.saved?.reason || '',
            markers: q.saved?.markers || []
        };
    };

    let items = DATA.map(fresh);
    let idx = 0;
    let selectedHighlightId = null;
    let timerId = null;
    let currentSelection = null;

    const $ = (id) => document.getElementById(id);
    const state = () => items[idx];

    function esc(x) {
        return String(x ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[c]));
    }

    let toastTimer = null;
    function toast(message) {
        const toastEl = $('toast');
        if (!toastEl) return;
        toastEl.textContent = message;
        toastEl.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toastEl.classList.add('hidden');
        }, 4000);
    }

    function remaining(s) {
        if (!s.started || s.done) return s.remaining;
        if (!s.deadline) return s.remaining;
        return s.deadline - Date.now();
    }

    function formatTime(ms) {
        const isNegative = ms <= -1000;
        const total = ms >= 0
            ? Math.ceil(ms / 1000)
            : Math.floor(Math.abs(ms) / 1000);
        const m = Math.floor(total / 60);
        const sec = total % 60;
        const formatted = `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
        return isNegative ? `-${formatted}` : formatted;
    }

    function formatElapsed(ms) {
        const total = Math.max(0, Math.floor(ms / 1000));
        const m = Math.floor(total / 60);
        const sec = total % 60;
        return `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
    }

    function tick() {
        const s = state();
        const r = remaining(s);
        const clock = $('clock');
        const elapsed = $('elapsed');
        const timerStatus = $('timer-status');

        if (clock) {
            clock.textContent = formatTime(r);
            if (r <= 30000) {
                clock.className = 'text-5xl font-bold font-mono tracking-tight text-rose-600 leading-none my-2 transition-colors animate-pulse';
            } else if (r <= 60000) {
                clock.className = 'text-5xl font-bold font-mono tracking-tight text-amber-600 leading-none my-2 transition-colors';
            } else {
                clock.className = 'text-5xl font-bold font-mono tracking-tight text-[#38215f] leading-none my-2 transition-colors';
            }
        }

        if (timerStatus) {
            if (s.done) {
                timerStatus.textContent = 'Tempo registrado ao concluir a questão';
                timerStatus.className = 'block text-xs text-emerald-700 font-semibold mb-2';
            } else if (r <= -1000) {
                timerStatus.textContent = 'Tempo limite excedido (contagem extra ativa)';
                timerStatus.className = 'block text-xs text-rose-600 font-semibold mb-2';
            } else {
                timerStatus.textContent = 'Tempo correndo automaticamente';
                timerStatus.className = 'block text-xs text-cloud-burst-600 mb-2';
            }
        }

        if (elapsed) {
            const used = s.done ? s.timeUsed : Math.max(0, (s.timeLimit || 180000) - r);
            elapsed.textContent = `Tempo utilizado: ${formatElapsed(used)}`;
        }
    }

    function renderNav() {
        const nav = $('nav');
        if (!nav) return;
        nav.innerHTML = '';

        DATA.forEach((q, i) => {
            const s = items[i];
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'cursor-pointer w-9 h-9 rounded-xl text-xs font-bold transition flex items-center justify-center ';

            if (i === idx) {
                btn.className += 'bg-brand-purple text-white shadow-md ring-2 ring-purple-300';
            } else if (s.done) {
                btn.className += 'bg-emerald-100 text-emerald-900 border border-emerald-300';
            } else {
                btn.className += 'bg-white text-gray-700 border border-gray-200 hover:border-brand-purple';
            }

            btn.textContent = String(i + 1);
            btn.onclick = () => navigate(i);
            nav.appendChild(btn);
        });
    }

    function navigate(newIdx) {
        if (newIdx < 0 || newIdx >= DATA.length) return;
        idx = newIdx;
        const s = state();
        if (!s.started && !s.done) {
            s.started = true;
            s.deadline = Date.now() + s.remaining;
        }
        render();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Marca-Texto e Destaques
    function getTargetRawText(target) {
        const q = DATA[idx];
        if (target === 'source') return q.support_text || '';
        if (target === 'prompt') return q.prompt || '';
        if (target === 'title') return q.support_title || '';
        return '';
    }

    function getTargetElement(target) {
        if (target === 'source') return $('source-text');
        if (target === 'prompt') return $('prompt');
        if (target === 'title') return $('source-title');
        return null;
    }

    function renderHighlightedText(rawText, highlights = []) {
        if (!rawText) return '';
        const n = rawText.length;
        const charMap = new Array(n).fill(null);

        for (const h of highlights) {
            let s = typeof h.start === 'number' ? Math.max(0, Math.min(n, h.start)) : -1;
            let e = typeof h.end === 'number' ? Math.max(0, Math.min(n, h.end)) : -1;

            if (h.text && (s < 0 || e <= s || rawText.slice(s, e) !== h.text)) {
                const foundIdx = rawText.indexOf(h.text);
                if (foundIdx === -1) {
                    continue;
                }
                s = foundIdx;
                e = foundIdx + h.text.length;
            }

            for (let i = s; i < e; i++) {
                charMap[i] = h;
            }
        }

        let html = '';
        let i = 0;
        while (i < n) {
            const curH = charMap[i];
            let j = i + 1;
            while (j < n && charMap[j] === curH) j++;

            const segmentText = rawText.slice(i, j);
            const escaped = esc(segmentText);

            if (curH) {
                const isCommand = curH.kind === 'command';
                const kindLabel = isCommand ? 'Comando (Amarelo)' : 'Pista (Azul)';
                const isSelected = selectedHighlightId === curH.id;
                const selectedClass = isSelected ? ' ring-2 ring-purple-900 ring-offset-1 font-semibold' : '';

                html += `<mark data-highlight-id="${curH.id}" data-kind="${curH.kind}" class="highlight ${selectedClass}" title="${kindLabel}">${escaped}</mark>`;
            } else {
                html += escaped;
            }

            i = j;
        }

        return html;
    }

    function buildPlayerMarkElement(m, sliceText) {
        const mark = document.createElement('mark');
        const isCommand = m.kind === 'command';
        const kindLabel = isCommand ? 'Comando (Amarelo)' : 'Pista (Azul)';
        const isSelected = selectedHighlightId === m.id;
        const selectedClass = isSelected ? ' ring-2 ring-purple-900 ring-offset-1 font-semibold' : '';

        mark.dataset.highlightId = m.id;
        mark.dataset.kind = m.kind;
        mark.className = `highlight${selectedClass}`;
        mark.title = kindLabel;
        mark.textContent = sliceText;
        return mark;
    }

    async function renderTargetText(target) {
        const q = DATA[idx];
        const s = state();
        const highlights = (s.markers || []).filter((m) => m.target === target);
        const raw = getTargetRawText(target);
        const el = getTargetElement(target);
        if (!el) return;

        if (target === 'source') {
            const imgEl = $('source-img');
            if (imgEl) {
                if (q.support_image_url) {
                    imgEl.src = q.support_image_url;
                    imgEl.hidden = false;
                } else {
                    imgEl.hidden = true;
                    imgEl.removeAttribute('src');
                }
            }

            if (!raw || !raw.trim()) {
                el.innerHTML = '';
                el.hidden = true;
                return;
            }

            el.hidden = false;
            await renderHtmlWithHighlights(el, raw, highlights, buildPlayerMarkElement);
            attachHighlightListeners(el);
            return;
        }

        if (!raw) {
            el.textContent = '';
            if (target === 'title') el.style.display = 'none';
            return;
        }

        if (target === 'title') el.style.display = 'block';

        el.innerHTML = renderHighlightedText(raw, highlights);
        attachHighlightListeners(el);
    }

    function renderAllTargets() {
        renderTargetText('title');
        renderTargetText('source');
        renderTargetText('prompt');
    }

    function attachHighlightListeners(container) {
        container.querySelectorAll('mark.highlight').forEach((markEl) => {
            markEl.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = markEl.dataset.highlightId;
                selectHighlight(id, markEl);
            });
        });
    }

    function selectHighlight(id, markEl) {
        selectedHighlightId = id;
        hideFloatingToolbar();

        const m = (state().markers || []).find((item) => item.id === id);
        if (!m) {
            hideHighlightPopover();
            return;
        }

        const popover = $('highlight-popover');
        const popoverLabel = $('popover-label');
        if (!popover) return;

        if (popoverLabel) {
            popoverLabel.textContent = m.kind === 'command' ? 'Comando' : 'Pista';
        }

        const rect = markEl.getBoundingClientRect();
        const popoverWidth = 270;
        let top = rect.top - 46;
        if (top < 10) top = rect.bottom + 8;
        let left = rect.left + (rect.width / 2) - (popoverWidth / 2);
        left = Math.max(10, Math.min(window.innerWidth - popoverWidth - 10, left));

        popover.style.top = `${top}px`;
        popover.style.left = `${left}px`;
        popover.classList.remove('hidden');

        renderAllTargets();
    }

    function hideHighlightPopover() {
        selectedHighlightId = null;
        const popover = $('highlight-popover');
        if (popover) popover.classList.add('hidden');
    }

    function hideFloatingToolbar() {
        currentSelection = null;
        const tb = $('selection-toolbar');
        if (tb) tb.classList.add('hidden');
    }

    function applyHighlightToTarget(target, kind, start, end) {
        const targetEl = getTargetElement(target);
        const raw = target === 'source' ? (targetEl?.textContent || '') : getTargetRawText(target);
        if (!raw || start >= end) return false;

        const s = state();
        s.markers = s.markers || [];

        // Remove sobreposições parciais
        s.markers = s.markers.filter((m) => {
            if (m.target !== target) return true;
            return m.end <= start || m.start >= end;
        });

        const id = 'hl_' + Date.now() + '_' + Math.random().toString(36).slice(2, 7);
        s.markers.push({
            id,
            target,
            kind,
            start,
            end,
            text: raw.slice(start, end)
        });

        renderAllTargets();
        hideFloatingToolbar();
        hideHighlightPopover();
        toast(kind === 'command' ? 'Comando destacado em amarelo!' : 'Pista destacada em azul!');
        return true;
    }

    function handleTextSelection() {
        const sel = window.getSelection();
        if (!sel || sel.isCollapsed || sel.rangeCount === 0) {
            hideFloatingToolbar();
            return;
        }

        const range = sel.getRangeAt(0);
        let target = null;
        let container = range.commonAncestorContainer;
        if (container.nodeType === 3) container = container.parentNode;

        if ($('source-text')?.contains(container)) target = 'source';
        else if ($('prompt')?.contains(container)) target = 'prompt';
        else if ($('source-title')?.contains(container)) target = 'title';

        if (!target) {
            hideFloatingToolbar();
            return;
        }

        const raw = getTargetRawText(target);
        const targetEl = getTargetElement(target);
        if (!raw || !targetEl) return;

        let start;
        let end;

        if (target === 'source') {
            const offsets = getSelectionOffsets(targetEl, range);
            if (!offsets) return;
            start = offsets.start;
            end = offsets.end;
        } else {
            const preRange = document.createRange();
            preRange.selectNodeContents(targetEl);
            preRange.setEnd(range.startContainer, range.startOffset);
            start = preRange.toString().length;
            const length = range.toString().length;
            end = start + length;
            if (length === 0 || start >= end) return;
        }

        currentSelection = { target, start, end };

        const rect = range.getBoundingClientRect();
        const tb = $('selection-toolbar');
        if (!tb) return;

        const tbWidth = 220;
        let top = rect.top - 46;
        if (top < 10) top = rect.bottom + 8;
        let left = rect.left + (rect.width / 2) - (tbWidth / 2);
        left = Math.max(10, Math.min(window.innerWidth - tbWidth - 10, left));

        tb.style.top = `${top}px`;
        tb.style.left = `${left}px`;
        tb.classList.remove('hidden');
    }

    // Renderização da Questão
    function render() {
        const q = DATA[idx];
        const s = state();

        // Título e Meta
        const titleEl = $('title');
        if (titleEl) titleEl.textContent = `Desafio ${idx + 1} de ${DATA.length}`;

        const metaEl = $('meta');
        if (metaEl) {
            metaEl.textContent = q.focus ? `Foco pedagógico: ${q.focus}` : `Questão ${idx + 1}`;
        }

        // Badges de Requisitos
        const reqBadges = $('req-badges');
        if (reqBadges) {
            reqBadges.innerHTML = '';
            if (q.require_command_highlight) {
                reqBadges.innerHTML += '<span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300">Comando Obrigatório</span>';
            }
            if (q.require_clue_highlight) {
                reqBadges.innerHTML += '<span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-cyan-100 text-cyan-900 border border-cyan-300">Pistas Obrigatórias</span>';
            }
        }

        // Textos e Imagem
        renderAllTargets();

        // Alternativas
        const optionsEl = $('options');
        if (optionsEl) {
            optionsEl.innerHTML = '';
            (q.options || []).forEach((opt) => {
                const isSelected = s.answer === opt.letter;
                const label = document.createElement('label');
                label.className = `flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition select-text ${
                    isSelected ? 'border-brand-purple bg-purple-50/60 shadow-xs' : 'border-gray-200 hover:border-purple-200 hover:bg-gray-50/50'
                }`;

                label.innerHTML = `
                    <input type="radio" name="opt_choice" value="${opt.letter}" ${isSelected ? 'checked' : ''} class="mt-1 text-brand-purple focus:ring-brand-purple w-4 h-4">
                    <div class="grow">
                        <span class="font-extrabold text-sm text-gray-900 mr-1">${opt.letter})</span>
                        <span class="text-sm text-gray-800">${esc(opt.text)}</span>
                    </div>
                `;

                label.querySelector('input').onchange = () => {
                    s.answer = opt.letter;
                    render();
                };

                optionsEl.appendChild(label);
            });
        }

        // Justificativa
        const reasonEl = $('reason');
        if (reasonEl) {
            if (reasonEl.value !== s.reason) {
                reasonEl.value = s.reason;
            }
            reasonEl.oninput = (e) => {
                s.reason = e.target.value;
            };
        }

        // Botão Concluir
        const finishText = $('finish-text');
        if (finishText) {
            finishText.textContent = (idx === DATA.length - 1) ? 'Concluir Avaliação' : 'Concluir e Ir para a Próxima Questão';
        }

        const prevBtn = $('previous');
        if (prevBtn) prevBtn.disabled = (idx === 0);

        const nextBtn = $('next');
        if (nextBtn) nextBtn.disabled = (idx === DATA.length - 1);

        renderNav();
        tick();
    }

    // Submissão da Questão ao Backend via Fetch API
    async function submitCurrentQuestion() {
        const q = DATA[idx];
        const s = state();

        // 1. Validação da Alternativa
        if (!s.answer) {
            toast('Selecione uma das alternativas (A, B, C, D ou E).');
            return false;
        }

        // 2. Validação da Justificativa
        if (!s.reason.trim()) {
            toast('Preencha a justificativa da alternativa escolhida.');
            $('reason')?.focus();
            return false;
        }

        // 3. Validação de Marca-Texto de Comando
        const markers = s.markers || [];
        const hasCommandMarker = markers.some((m) => m.kind === 'command');
        if (q.require_command_highlight && !hasCommandMarker) {
            toast('Atenção: Você deve destacar o comando da questão com o Marca-Texto Amarelo antes de avançar.');
            return false;
        }

        // 4. Validação de Marca-Texto de Pistas
        const hasClueMarker = markers.some((m) => m.kind === 'evidence');
        if (q.require_clue_highlight && !hasClueMarker) {
            toast('Atenção: Você deve destacar as pistas no texto de apoio com o Marca-Texto Azul antes de avançar.');
            return false;
        }

        // Para o cronômetro desta questão
        if (!s.done) {
            const r = remaining(s);
            s.timeUsed = Math.max(0, (s.timeLimit || 180000) - r);
            s.remaining = r;
            s.deadline = null;
            s.done = true;
        }

        // Envia resposta para o backend
        try {
            const res = await fetch(config.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    question_id: q.id,
                    answer: s.answer,
                    reason: s.reason,
                    markers: s.markers,
                    time_spent: s.timeUsed
                })
            });

            if (!res.ok) {
                const errData = await res.json();
                toast(errData.error || 'Erro ao registrar resposta.');
                return false;
            }
        } catch (err) {
            toast('Erro de conexão ao salvar resposta.');
            return false;
        }

        return true;
    }

    // Listeners de Eventos
    document.addEventListener('mouseup', handleTextSelection);
    document.addEventListener('selectionchange', () => {
        if (!window.getSelection()?.toString().trim()) {
            hideFloatingToolbar();
        }
    });

    $('toolbar-cmd')?.addEventListener('click', (e) => {
        e.stopPropagation();
        if (currentSelection) {
            applyHighlightToTarget(currentSelection.target, 'command', currentSelection.start, currentSelection.end);
        }
    });

    $('toolbar-evi')?.addEventListener('click', (e) => {
        e.stopPropagation();
        if (currentSelection) {
            applyHighlightToTarget(currentSelection.target, 'evidence', currentSelection.start, currentSelection.end);
        }
    });

    $('toolbar-cancel')?.addEventListener('click', (e) => {
        e.stopPropagation();
        hideFloatingToolbar();
    });

    // Botões da Paleta Lateral
    document.querySelectorAll('[data-add]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const kind = btn.dataset.add;
            if (currentSelection) {
                applyHighlightToTarget(currentSelection.target, kind, currentSelection.start, currentSelection.end);
            } else {
                toast(`Selecione primeiro com o mouse um trecho do texto e depois clique em ${kind === 'command' ? 'Comando' : 'Pistas'}.`);
            }
        });
    });

    $('clear-all')?.addEventListener('click', () => {
        const s = state();
        if (confirm('Deseja remover todos os destaques desta questão?')) {
            s.markers = [];
            renderAllTargets();
            hideHighlightPopover();
            toast('Destaques removidos.');
        }
    });

    // Popover de Destaque
    $('popover-change-cmd')?.addEventListener('click', () => {
        if (!selectedHighlightId) return;
        const m = (state().markers || []).find((item) => item.id === selectedHighlightId);
        if (m) {
            m.kind = 'command';
            renderAllTargets();
            hideHighlightPopover();
        }
    });

    $('popover-change-evi')?.addEventListener('click', () => {
        if (!selectedHighlightId) return;
        const m = (state().markers || []).find((item) => item.id === selectedHighlightId);
        if (m) {
            m.kind = 'evidence';
            renderAllTargets();
            hideHighlightPopover();
        }
    });

    $('popover-remove')?.addEventListener('click', () => {
        if (!selectedHighlightId) return;
        const s = state();
        s.markers = (s.markers || []).filter((m) => m.id !== selectedHighlightId);
        renderAllTargets();
        hideHighlightPopover();
        toast('Destaque removido.');
    });

    $('popover-close')?.addEventListener('click', hideHighlightPopover);

    // Botões de Avanço / Navegação
    $('previous')?.addEventListener('click', () => navigate(idx - 1));
    $('next')?.addEventListener('click', () => navigate(idx + 1));

    $('finish')?.addEventListener('click', async () => {
        const ok = await submitCurrentQuestion();
        if (!ok) return;

        if (idx < DATA.length - 1) {
            navigate(idx + 1);
            toast(`Questão ${idx} salva! Iniciando próxima questão...`);
        } else {
            // Última questão concluída: envia submissão geral e redireciona
            try {
                const res = await fetch(config.finishUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await res.json();
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            } catch {
                toast('Erro ao concluir teste.');
            }
        }
    });

    // Atualização em tempo real de questões editadas pelo professor via Laravel Reverb
    if (window.Echo && config.classroomId) {
        window.Echo.channel(`classroom.${config.classroomId}`)
            .listen('.question.updated', (e) => {
                const updatedQ = e?.question;
                if (!updatedQ || !updatedQ.id) return;

                const qIndex = DATA.findIndex((item) => item.id === updatedQ.id);
                if (qIndex === -1) return;

                const currentQ = DATA[qIndex];
                const qState = items[qIndex];
                const oldTimeLimit = qState.timeLimit || 180000;
                const newTimeLimit = (updatedQ.time_limit_seconds || 180) * 1000;

                DATA[qIndex] = {
                    ...currentQ,
                    ...updatedQ,
                    index: currentQ.index,
                    points: currentQ.points,
                    saved: currentQ.saved,
                };

                qState.timeLimit = newTimeLimit;
                if (!qState.done) {
                    if (qState.started && qState.deadline) {
                        const elapsedSoFar = Math.max(0, oldTimeLimit - remaining(qState));
                        qState.remaining = newTimeLimit - elapsedSoFar;
                        qState.deadline = Date.now() + qState.remaining;
                    } else {
                        qState.remaining = newTimeLimit;
                    }
                }

                if (qIndex === idx) {
                    hideFloatingToolbar();
                    hideHighlightPopover();
                    render();
                    toast('Esta questão foi atualizada pelo professor.');
                } else {
                    toast(`A Questão ${qIndex + 1} foi atualizada pelo professor.`);
                }
            });
    }

    // Inicia cronômetro contínuo
    const firstState = state();
    if (!firstState.started && !firstState.done) {
        firstState.started = true;
        firstState.deadline = Date.now() + firstState.remaining;
    }

    timerId = setInterval(tick, 250);
    render();
}
