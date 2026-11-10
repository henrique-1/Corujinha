/**
 * Atividade Interativa PREPARA AVALIA+ · 7º ano
 * Flow:
 * 1. Identificação obrigatória (Nome + Turma) -> Iniciar Teste
 * 2. Cronômetro contínuo por questão (3 minutos) sem pausa
 * 3. Concluir e Registrar Tempo -> salva e avança automaticamente para a próxima questão
 * 4. Pós-finalização: Modo de Revisão liberado para o item "4. Depois da correção coletiva"
 * 5. Faixas coloridas totalmente funcionais, persistidas e salvas para avaliação
 */

export function initAtividade() {
    const rawData = window.__ATIVIDADE_DATA__;
    if (!Array.isArray(rawData) || rawData.length === 0) {
        return;
    }

    const DATA = rawData;
    const LIMIT = 180000; // 3 minutos por questão (180.000 ms)
    const STORAGE_KEY = 'prepara_avalia_7ano_v2';

    const fresh = () => ({
        remaining: LIMIT,
        deadline: null,
        started: false,
        done: false,
        timeUsed: 0,
        answer: '',
        keywords: '',
        evidence: '',
        reason: '',
        reflection: '',
        markers: []
    });

    let model = {
        name: '',
        group: '',
        started: false,
        finished: false,
        inReview: false,
        current: 0,
        items: DATA.map(fresh)
    };

    let selectedMarker = null;
    let history = DATA.map(() => []);

    // Recupera progresso da sessão atual se houver
    try {
        const saved = sessionStorage.getItem(STORAGE_KEY);
        if (saved) {
            const parsed = JSON.parse(saved);
            if (parsed && Array.isArray(parsed.items) && parsed.items.length === DATA.length) {
                model = {
                    ...model,
                    ...parsed,
                    items: DATA.map((_, i) => ({
                        ...fresh(),
                        ...(parsed.items[i] || {})
                    }))
                };
            }
        }
    } catch {
        // Fallback silencioso para ambientes sem storage
    }

    let idx = Math.min(DATA.length - 1, Math.max(0, Number(model.current) || 0));

    const $ = (id) => document.getElementById(id);
    const state = () => model.items[idx];

    function esc(x) {
        return String(x ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[c]));
    }

    function save() {
        model.current = idx;
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(model));
        } catch {
            // Ignora limites de cota
        }
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
        }, 4500);
    }

    function remaining(s = state()) {
        if (!s.started) return s.remaining;
        if (s.done) return s.remaining;
        return s.deadline === null ? s.remaining : Math.max(0, s.deadline - Date.now());
    }

    function format(ms, roundUp = true) {
        const t = (roundUp ? Math.ceil : Math.floor)(Math.max(0, ms) / 1000);
        const mins = String(Math.floor(t / 60)).padStart(2, '0');
        const secs = String(t % 60).padStart(2, '0');
        return `${mins}:${secs}`;
    }

    function tick() {
        if (!model.started) {
            updateClockDisplay(LIMIT, false);
            return;
        }

        const s = state();
        const r = remaining(s);

        // Se o tempo da questão atual correndo atingiu zero
        if (s.started && !s.done && s.deadline !== null && r === 0) {
            s.remaining = 0;
            s.deadline = null;
            s.timeUsed = LIMIT;
            save();
            toast('Os 3 minutos terminaram! Finalize seu registro nesta questão.');
        }

        updateClockDisplay(r, s.started && !s.done);
    }

    function updateClockDisplay(r, isRunning) {
        const clockEl = $('clock');
        if (clockEl) {
            clockEl.textContent = format(r);
            if (r < 30000 && r > 0 && isRunning) {
                clockEl.classList.add('text-rose-600');
            } else {
                clockEl.classList.remove('text-rose-600');
            }
        }

        const s = state();
        const timerBadge = $('timer-badge');
        const timerStatus = $('timer-status');
        const elapsedEl = $('elapsed');

        const timeUsed = s.done ? s.timeUsed : (LIMIT - r);

        if (elapsedEl) {
            elapsedEl.textContent = 'Tempo utilizado: ' + format(timeUsed, false);
        }

        if (timerBadge && timerStatus) {
            if (!model.started) {
                timerBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700 border border-gray-200 mb-3';
                timerBadge.textContent = '⏸️ Não iniciado';
                timerStatus.textContent = 'Identifique-se para começar';
            } else if (s.done) {
                timerBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 mb-3';
                timerBadge.textContent = `✓ Tempo Registrado (${format(timeUsed, false)})`;
                timerStatus.textContent = 'Questão concluída nesta tentativa';
            } else if (r === 0) {
                timerBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200 mb-3';
                timerBadge.textContent = '⚠️ Tempo Esgotado (03:00)';
                timerStatus.textContent = 'Conclua seu registro e avance';
            } else {
                timerBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 mb-3';
                timerBadge.textContent = '⏱️ Cronômetro Contínuo';
                timerStatus.textContent = 'Tempo correndo (não pausável)';
            }
        }

        const recordStatus = $('record-status');
        if (recordStatus) {
            recordStatus.textContent = (s.done ? 'Questão concluída. ' : '') +
                (s.started ? `Tempo utilizado: ${format(timeUsed, false)}. ` : '') +
                'Respostas e faixas salvas continuamente nesta sessão.';
        }
    }

    // Gerenciador de Faixas Coloridas (Markers)
    function checkpoint() {
        history[idx].push(JSON.stringify(state().markers));
        if (history[idx].length > 25) {
            history[idx].shift();
        }
    }

    function selectMarker(id) {
        selectedMarker = id;
        const board = $('board');
        if (!board) return;
        board.querySelectorAll('.marker').forEach((elem) => {
            if (elem.dataset.id === id) {
                elem.classList.add('selected');
            } else {
                elem.classList.remove('selected');
            }
        });
    }

    function addMarker(kind) {
        if (!model.started) {
            toast('Inicie o teste antes de criar faixas.');
            openStartDialog();
            return;
        }

        checkpoint();
        const id = 'm_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 5);
        const currentCount = state().markers.length;
        const newMarker = {
            id,
            kind,
            x: 0.04,
            y: Math.min(0.85, 0.06 + (currentCount * 0.05)),
            w: 0.40,
            h: 24
        };

        state().markers.push(newMarker);
        selectedMarker = id;
        save();
        renderMarkers();
        toast('Faixa adicionada. Arraste sobre o texto e puxe a borda para redimensionar.');
    }

    function removeMarker() {
        if (!selectedMarker) {
            toast('Clique em uma faixa para selecioná-la antes de excluir.');
            return;
        }
        checkpoint();
        state().markers = state().markers.filter((m) => m.id !== selectedMarker);
        selectedMarker = null;
        save();
        renderMarkers();
    }

    function duplicateMarker() {
        const m = state().markers.find((item) => item.id === selectedMarker);
        if (!m) {
            toast('Selecione uma faixa primeiro.');
            return;
        }
        checkpoint();
        const copy = {
            ...m,
            id: 'm_' + Date.now().toString(36),
            x: Math.min(1 - m.w, m.x + 0.02),
            y: Math.min(0.92, m.y + 0.025)
        };
        state().markers.push(copy);
        selectedMarker = copy.id;
        save();
        renderMarkers();
    }

    function undoMarker() {
        if (history[idx].length) {
            state().markers = JSON.parse(history[idx].pop());
            selectedMarker = null;
            save();
            renderMarkers();
        } else {
            toast('Nenhuma alteração de faixa para desfazer nesta questão.');
        }
    }

    function renderMarkers() {
        const board = $('board');
        if (!board) return;

        board.querySelectorAll('.marker').forEach((el) => el.remove());

        for (const m of state().markers) {
            const el = document.createElement('div');
            el.className = 'marker' + (selectedMarker === m.id ? ' selected' : '');
            el.dataset.kind = m.kind;
            el.dataset.id = m.id;
            el.tabIndex = 0;
            el.role = 'button';

            const kindName = { command: 'do comando', evidence: 'de pistas', answer: 'da resposta' }[m.kind] || '';
            el.setAttribute('aria-label', `Faixa ${kindName}. Use as setas para mover e Delete para excluir.`);
            el.style.cssText = `left:${m.x * 100}%;top:${m.y * 100}%;width:${m.w * 100}%;height:${m.h}px`;

            const handle = document.createElement('span');
            handle.className = 'handle';
            el.appendChild(handle);
            board.appendChild(el);

            el.onfocus = () => selectMarker(m.id);

            el.onpointerdown = (e) => {
                e.preventDefault();
                selectMarker(m.id);
                el.focus({ preventScroll: true });
                checkpoint();

                const r = board.getBoundingClientRect();
                const start = {
                    x: e.clientX,
                    y: e.clientY,
                    mx: m.x,
                    my: m.y,
                    mw: m.w
                };
                const resize = e.target === handle;

                try {
                    el.setPointerCapture(e.pointerId);
                } catch {
                    // Ignora se o navegador não puder capturar o ponteiro
                }
                el.style.cursor = 'grabbing';

                el.onpointermove = (ev) => {
                    if (resize) {
                        m.w = Math.max(25 / r.width, Math.min(1 - m.x, start.mw + (ev.clientX - start.x) / r.width));
                        el.style.width = (m.w * 100) + '%';
                    } else {
                        m.x = Math.max(0, Math.min(1 - m.w, start.mx + (ev.clientX - start.x) / r.width));
                        m.y = Math.max(0, Math.min(1 - (m.h / r.height), start.my + (ev.clientY - start.y) / r.height));
                        el.style.left = (m.x * 100) + '%';
                        el.style.top = (m.y * 100) + '%';
                    }
                };

                const end = (ev) => {
                    el.onpointermove = null;
                    el.onpointerup = null;
                    el.onpointercancel = null;
                    el.style.cursor = 'grab';
                    try {
                        if (ev && typeof ev.pointerId === 'number' && el.hasPointerCapture(ev.pointerId)) {
                            el.releasePointerCapture(ev.pointerId);
                        }
                    } catch {
                        // Ignora
                    }
                    save();
                };

                el.onpointerup = end;
                el.onpointercancel = end;
            };

            el.onkeydown = (e) => {
                if (e.key === 'Delete' || e.key === 'Backspace') {
                    e.preventDefault();
                    removeMarker();
                    return;
                }
                if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) return;
                e.preventDefault();
                checkpoint();
                const step = e.shiftKey ? 15 : 3;
                const r = board.getBoundingClientRect();

                m.x = Math.max(0, Math.min(1 - m.w, m.x + (e.key === 'ArrowRight' ? step : e.key === 'ArrowLeft' ? -step : 0) / r.width));
                m.y = Math.max(0, Math.min(1 - (m.h / r.height), m.y + (e.key === 'ArrowDown' ? step : e.key === 'ArrowUp' ? -step : 0) / r.height));
                el.style.left = (m.x * 100) + '%';
                el.style.top = (m.y * 100) + '%';
                save();
            };
        }
    }

    function renderNav() {
        const nav = $('nav');
        if (!nav) return;

        nav.innerHTML = DATA.map((_, i) => {
            const isCurrent = i === idx;
            const isDone = model.items[i].done;
            const activeClasses = isCurrent
                ? 'bg-brand-purple text-white border-brand-purple shadow-sm'
                : 'bg-white text-gray-800 border-purple-200 hover:border-brand-purple';
            const doneClasses = isDone ? 'nav-btn-done' : '';

            return `<button type="button" data-index="${i}" aria-label="Questão ${i + 1}${isDone ? ', concluída' : ''}" aria-current="${isCurrent}" class="w-11 h-11 flex items-center justify-center font-bold text-sm rounded-xl border transition cursor-pointer ${activeClasses} ${doneClasses}">${i + 1}</button>`;
        }).join('');

        nav.querySelectorAll('button').forEach((b) => {
            b.onclick = () => {
                if (!model.started) {
                    openStartDialog();
                    return;
                }
                navigate(Number(b.dataset.index));
            };
        });
    }

    function navigate(n) {
        if (n < 0 || n >= DATA.length) return;
        idx = n;
        selectedMarker = null;

        // Se o teste já começou e a questão de destino ainda não foi iniciada nem concluída, inicia o cronômetro dela
        const s = state();
        if (model.started && !s.done && !s.started) {
            s.started = true;
            s.deadline = Date.now() + s.remaining;
        }

        save();
        render();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function render() {
        const q = DATA[idx];
        const s = state();

        $('title').textContent = `Desafio ${String(idx + 1).padStart(2, '0')}: ${q.focus}`;
        $('meta').textContent = '7º ano / PREPARA AVALIA+ 2026' + (idx === 0 ? ' / Questão adaptada' : '');

        const sourceTitle = $('source-title');
        if (sourceTitle) {
            sourceTitle.textContent = q.source_title || '';
            sourceTitle.style.display = q.source_title ? 'block' : 'none';
        }

        const sourceText = $('source-text');
        const sourceImg = $('source-img');

        if (sourceImg) {
            sourceImg.hidden = !q.image;
            if (q.image) {
                sourceImg.src = q.image;
            }
        }

        if (sourceText) {
            sourceText.hidden = Boolean(q.image);
            if (idx === 7) {
                sourceText.innerHTML = esc(q.stimulus || '').replace(
                    'Também… você sabe, não é?',
                    '<strong><u>Também… você sabe, não é?</u></strong>'
                );
            } else {
                sourceText.textContent = q.stimulus || '';
            }
        }

        $('prompt').textContent = q.prompt;

        const optionsContainer = $('options');
        if (optionsContainer) {
            optionsContainer.innerHTML = q.options.map((opt, i) => {
                const letter = String.fromCharCode(65 + i);
                const checked = s.answer === letter;
                return `
                    <label class="option-item flex items-start gap-3 p-3.5 rounded-xl border-2 transition cursor-pointer text-base leading-relaxed ${checked ? 'border-pink-600 bg-pink-50' : 'border-transparent hover:bg-purple-50/50'}">
                        <input type="radio" name="answer" value="${letter}" ${checked ? 'checked' : ''} class="mt-1 h-4 w-4 accent-pink-600 cursor-pointer">
                        <span class="font-bold min-w-[24px] text-gray-900">${letter})</span>
                        <span class="text-gray-800">${esc(opt)}</span>
                    </label>
                `;
            }).join('');

            optionsContainer.querySelectorAll('input').forEach((radio) => {
                radio.onchange = () => {
                    if (!model.started) {
                        openStartDialog();
                        return;
                    }
                    s.answer = radio.value;
                    save();
                    renderNav();
                };
            });
        }

        // Preenche campos de resposta
        for (const k of ['keywords', 'evidence', 'reason', 'reflection']) {
            const inputEl = $(k);
            if (inputEl) {
                inputEl.value = s[k] || '';
            }
        }

        // Controle do campo 4: liberado apenas no modo de revisão
        const reflectionEl = $('reflection');
        const reflectionBadge = $('reflection-badge');
        if (reflectionEl && reflectionBadge) {
            if (model.finished || model.inReview) {
                reflectionEl.disabled = false;
                reflectionBadge.className = 'text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300';
                reflectionBadge.textContent = '🔓 Liberado para correção coletiva';
            } else {
                reflectionEl.disabled = true;
                reflectionBadge.className = 'text-[11px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200';
                reflectionBadge.textContent = '🔒 Preencher na revisão pós-teste';
            }
        }

        // Texto do botão de conclusão
        const finishText = $('finish-text');
        if (finishText) {
            if (model.finished || model.inReview) {
                finishText.textContent = idx === DATA.length - 1 ? 'Revisão Concluída' : 'Salvar e Ir para a Próxima';
            } else {
                finishText.textContent = idx === DATA.length - 1 ? 'Concluir e Finalizar Teste' : 'Concluir e Ir para a Próxima Questão';
            }
        }

        // Banners e status
        const reviewBanner = $('review-banner');
        if (reviewBanner) {
            if (model.finished || model.inReview) {
                reviewBanner.classList.remove('hidden');
            } else {
                reviewBanner.classList.add('hidden');
            }
        }

        const testStatusPill = $('test-status-pill');
        if (testStatusPill) {
            if (!model.started) {
                testStatusPill.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200';
                testStatusPill.textContent = 'Aguardando início';
            } else if (model.finished || model.inReview) {
                testStatusPill.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200';
                testStatusPill.textContent = 'Modo de Revisão Ativo';
            } else {
                testStatusPill.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-brand-purple border border-purple-200';
                testStatusPill.textContent = `Em andamento (Desafio ${idx + 1}/10)`;
            }
        }

        const prevBtn = $('previous');
        if (prevBtn) prevBtn.disabled = idx === 0;

        const nextBtn = $('next');
        if (nextBtn) nextBtn.disabled = idx === (DATA.length - 1);

        renderNav();
        renderMarkers();
        tick();
    }

    // Input listeners dos campos do estudante
    for (const k of ['keywords', 'evidence', 'reason', 'reflection']) {
        const inputEl = $(k);
        if (inputEl) {
            inputEl.addEventListener('input', () => {
                state()[k] = inputEl.value;
                save();
            });
        }
    }

    // Modal de Inicialização Obrigatória (Gatekeeper)
    const startDialog = $('start-dialog');
    const startForm = $('start-form');
    const initStudentInput = $('init-student');
    const initGroupInput = $('init-group');

    function openStartDialog() {
        if (!startDialog) return;
        if (initStudentInput) initStudentInput.value = model.name;
        if (initGroupInput) initGroupInput.value = model.group;
        try {
            startDialog.showModal();
        } catch {
            startDialog.setAttribute('open', '');
        }
    }

    if (startForm) {
        startForm.onsubmit = (e) => {
            e.preventDefault();
            const name = (initStudentInput?.value || '').trim();
            const group = (initGroupInput?.value || '').trim();

            if (!name || !group) {
                toast('Por favor, preencha seu nome e turma para iniciar.');
                return;
            }

            model.name = name;
            model.group = group;
            model.started = true;

            const studentBadge = $('student');
            const groupBadge = $('group');
            if (studentBadge) studentBadge.value = name;
            if (groupBadge) groupBadge.value = group;

            // Inicia o cronômetro da Questão 1 imediatamente
            const s = state();
            s.started = true;
            s.deadline = Date.now() + s.remaining;

            try {
                startDialog.close();
            } catch {
                startDialog.removeAttribute('open');
            }

            save();
            render();
            toast(`Teste iniciado! Você tem 3 minutos para a Questão 1. Bom trabalho!`);
        };
    }

    // Inicialização da tela: se o teste não começou, abre a identificação imediatamente
    if (!model.started) {
        openStartDialog();
    } else {
        const studentBadge = $('student');
        const groupBadge = $('group');
        if (studentBadge) studentBadge.value = model.name;
        if (groupBadge) groupBadge.value = model.group;

        // Se já estava em andamento mas com deadline expirado ao recarregar
        const s = state();
        if (s.started && !s.done && s.deadline === null && s.remaining > 0) {
            s.deadline = Date.now() + s.remaining;
        }
    }

    const boardContainer = $('board');
    if (boardContainer) {
        boardContainer.addEventListener('pointerdown', (e) => {
            if (!e.target.closest('.marker')) {
                selectMarker(null);
            }
        });
    }

    // Botões da paleta
    document.querySelectorAll('[data-add]').forEach((b) => {
        b.onclick = () => addMarker(b.dataset.add);
    });

    const duplicateBtn = $('duplicate');
    if (duplicateBtn) duplicateBtn.onclick = duplicateMarker;

    const removeBtn = $('remove');
    if (removeBtn) removeBtn.onclick = removeMarker;

    const undoBtn = $('undo');
    if (undoBtn) undoBtn.onclick = undoMarker;

    // Navegação Anterior e Próxima
    const prevBtn = $('previous');
    if (prevBtn) prevBtn.onclick = () => navigate(idx - 1);

    const nextBtn = $('next');
    if (nextBtn) nextBtn.onclick = () => navigate(idx + 1);

    // Botão Concluir e Registrar Tempo -> Para o cronômetro, salva e avança automaticamente
    const finishBtn = $('finish');
    if (finishBtn) {
        finishBtn.onclick = () => {
            if (!model.started) {
                openStartDialog();
                return;
            }

            const s = state();

            // Validação dos campos obrigatórios
            if (!s.answer) {
                toast('Selecione uma das alternativas (A, B, C, D ou E).');
                return;
            }
            if (!s.keywords.trim()) {
                toast('Preencha o campo "1. Palavras-chave do comando".');
                $('keywords')?.focus();
                return;
            }
            if (!s.evidence.trim()) {
                toast('Preencha o campo "2. Pista encontrada e localização".');
                $('evidence')?.focus();
                return;
            }
            if (!s.reason.trim()) {
                toast('Preencha o campo "3. Justificativa da alternativa escolhida".');
                $('reason')?.focus();
                return;
            }

            // Para o cronômetro da questão atual e salva o tempo utilizado
            if (!s.done) {
                const r = remaining(s);
                s.timeUsed = LIMIT - r;
                s.remaining = r;
                s.deadline = null;
                s.done = true;
            }

            save();
            renderNav();
            tick();

            // Se for da Questão 1 a 9 -> avança automaticamente e inicia o timer da próxima
            if (idx < DATA.length - 1) {
                const nextIdx = idx + 1;
                toast(`Questão ${idx + 1} concluída! Iniciando Desafio ${nextIdx + 1}...`);

                // Avança e inicia cronômetro da nova questão
                idx = nextIdx;
                const nextState = state();
                if (!nextState.done) {
                    nextState.started = true;
                    nextState.deadline = Date.now() + nextState.remaining;
                }

                save();
                render();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                // Última questão (10) finalizada: ativa o Modo de Revisão!
                model.finished = true;
                model.inReview = true;
                save();
                render();

                const finishDialog = $('finish-dialog');
                if (finishDialog) {
                    const finishSubtitle = $('finish-dialog-subtitle');
                    if (finishSubtitle) {
                        finishSubtitle.textContent = `Parabéns, ${model.name}! Você concluiu todos os 10 desafios do PREPARA AVALIA+.`;
                    }
                    finishDialog.showModal();
                } else {
                    toast('Parabéns! Todas as questões foram concluídas. O Modo de Revisão está liberado!');
                }
            }
        };
    }

    // Modal de Conclusão / Revisão
    const closeFinishDialog = $('close-finish-dialog');
    if (closeFinishDialog) {
        closeFinishDialog.onclick = () => {
            $('finish-dialog')?.close();
            toast('Modo de Revisão ativado. Agora você pode preencher a etapa 4 durante a correção coletiva.');
        };
    }

    // Nova tentativa
    const newAttemptBtn = $('new-attempt');
    if (newAttemptBtn) {
        newAttemptBtn.onclick = () => {
            if (!confirm('Começar uma nova tentativa? As respostas, tempos e faixas coloridas serão apagados. Baixe seus registros antes de continuar se desejar mantê-los.')) {
                return;
            }
            try {
                sessionStorage.removeItem(STORAGE_KEY);
            } catch {}

            model = {
                name: '',
                group: '',
                started: false,
                finished: false,
                inReview: false,
                current: 0,
                items: DATA.map(fresh)
            };
            idx = 0;
            selectedMarker = null;
            history = DATA.map(() => []);

            const studentBadge = $('student');
            const groupBadge = $('group');
            if (studentBadge) studentBadge.value = '';
            if (groupBadge) groupBadge.value = '';

            save();
            render();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            openStartDialog();
        };
    }

    // Instruções (Help dialog)
    const helpBtn = $('help');
    const instructionsDialog = $('instructions');
    const closeHelpBtn = $('close-help');

    if (helpBtn && instructionsDialog) {
        helpBtn.onclick = () => instructionsDialog.showModal();
    }
    if (closeHelpBtn && instructionsDialog) {
        closeHelpBtn.onclick = () => instructionsDialog.close();
    }

    // Geração de Relatório de Avaliação (Inclui respostas, tempos e TODAS as faixas coloridas)
    function generateReport() {
        return `PREPARA AVALIA+ 2026 — 7º ANO\n` +
            `Nome do Estudante: ${model.name || 'Não informado'}\n` +
            `Turma: ${model.group || 'Não informada'}\n` +
            `Data e Hora do Registro: ${new Date().toLocaleString('pt-BR')}\n` +
            `Status do Teste: ${model.finished ? 'Completo (10/10 questões)' : 'Em andamento'}\n\n` +
            `==================================================\n\n` +
            DATA.map((q, i) => {
                const s = model.items[i];
                const markerCount = s.markers ? s.markers.length : 0;
                const cmdCount = (s.markers || []).filter((m) => m.kind === 'command').length;
                const eviCount = (s.markers || []).filter((m) => m.kind === 'evidence').length;
                const ansCount = (s.markers || []).filter((m) => m.kind === 'answer').length;

                const markerDetails = markerCount > 0
                    ? s.markers.map((m, mIdx) => {
                        const name = { command: 'Comando (Amarelo)', evidence: 'Pistas (Azul)', answer: 'Resposta (Rosa)' }[m.kind] || m.kind;
                        return `     ${mIdx + 1}. [${name}] Pos: (X: ${Math.round(m.x * 100)}%, Y: ${Math.round(m.y * 100)}%), Largura: ${Math.round(m.w * 100)}%`;
                    }).join('\n')
                    : '     Nenhuma faixa colorida utilizada nesta questão.';

                return `DESAFIO ${String(i + 1).padStart(2, '0')} — ${q.focus}\n` +
                    `Situação: ${s.done ? 'Concluído' : 'Não concluído'}\n` +
                    `Tempo de Resolução: ${s.done || s.timeUsed > 0 ? format(s.timeUsed, false) : 'Não finalizado'}\n` +
                    `Alternativa Marcada: ${s.answer ? s.answer + ')' : 'Não marcada'}\n` +
                    `Faixas Coloridas Utilizadas (${markerCount}): [Comando: ${cmdCount} | Pistas: ${eviCount} | Resposta: ${ansCount}]\n` +
                    `${markerDetails}\n\n` +
                    `1. Palavras-chave do comando:\n   ${s.keywords || '—'}\n` +
                    `2. Pista encontrada e localização:\n   ${s.evidence || '—'}\n` +
                    `3. Justificativa da escolha:\n   ${s.reason || '—'}\n` +
                    `4. Depois da correção coletiva:\n   ${s.reflection || '—'}\n`;
            }).join('\n--------------------------------------------------\n\n');
    }

    // Exportar registros (.txt)
    function exportFile() {
        save();
        const blob = new Blob(['\uFEFF' + generateReport()], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        const filenameSafe = (model.name || 'Estudante').replace(/[^a-z0-9]/gi, '_');
        a.href = url;
        a.download = `Registro_AVALIA_7ano_${filenameSafe}.txt`;
        a.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
        toast('Relatório completo preparado para download com todas as respostas e faixas!');
    }

    const exportBtn = $('export');
    if (exportBtn) exportBtn.onclick = exportFile;

    const exportTopBtn = $('btn-export-top');
    if (exportTopBtn) exportTopBtn.onclick = exportFile;

    // Imprimir / Salvar em PDF
    function printFile() {
        const w = window.open('', '_blank');
        if (!w) {
            toast('Permita a abertura de janelas pop-up no navegador para imprimir.');
            return;
        }
        w.document.write(`<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Registro AVALIA 7º ano — ${esc(model.name)}</title><style>body{font:11pt Arial,sans-serif;max-width:850px;margin:25px auto;padding:20px;color:#1c1926}pre{white-space:pre-wrap;line-height:1.55;font:inherit}h1{font-size:18pt;margin-bottom:4px;color:#4a2882}button{background:#4a2882;color:white;border:none;border-radius:8px;padding:10px 18px;font-weight:bold;cursor:pointer;margin-bottom:18px}@media print{button{display:none}}</style><h1>PREPARA AVALIA+ · 7º ano</h1><button onclick="window.print()">Imprimir / Salvar em PDF</button><pre>${esc(generateReport())}</pre></html>`);
        w.document.close();
        w.focus();
    }

    const printBtn = $('print');
    if (printBtn) printBtn.onclick = printFile;

    const printTopBtn = $('btn-print-top');
    if (printTopBtn) printTopBtn.onclick = printFile;

    // Inicialização da renderização e loop do timer
    render();
    setInterval(tick, 250);
    window.addEventListener('beforeunload', save);
}
