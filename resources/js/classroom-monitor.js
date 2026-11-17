/**
 * classroom-monitor.js
 * Monitora em tempo real a entrada de estudantes e o envio de respostas via Laravel Reverb e Echo.
 */

export function initClassroomMonitor() {
    const monitorData = window.__CLASSROOM_MONITOR__;
    if (!monitorData || !window.Echo) {
        return;
    }

    const tbody = document.getElementById('attempts-table-body');
    const emptyRow = document.getElementById('empty-attempts-row');
    const totalCountEl = document.getElementById('attempts-total-count');

    let currentTotal = monitorData.totalAttempts || 0;
    const totalQuestions = monitorData.totalQuestions || 0;

    // Conecta ao canal privado de monitoramento da sala
    window.Echo.private(`classroom.monitor.${monitorData.classroomId}`)
        .listen('.student.joined', (e) => {
            if (!e.attempt) return;

            // Remove a linha de "nenhum aluno" se ela existir
            if (emptyRow) {
                emptyRow.remove();
            }

            // Verifica se o aluno já tem uma linha
            let existingRow = document.getElementById(`attempt-row-${e.attempt.id}`);
            if (!existingRow) {
                currentTotal++;
                if (totalCountEl) {
                    totalCountEl.textContent = `Respostas dos Estudantes (${currentTotal})`;
                }

                const tr = document.createElement('tr');
                tr.id = `attempt-row-${e.attempt.id}`;
                tr.setAttribute('data-student-row', '');
                tr.setAttribute('data-student-name', (e.attempt.student_name || '').toLowerCase());
                tr.className = 'hover:bg-royal-purple-50/30 transition bg-emerald-50/40 animate-pulse';

                const searchInput = document.getElementById('student-search-input');
                if (searchInput && searchInput.value.trim()) {
                    const normSearch = searchInput.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
                    const normName = (e.attempt.student_name || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
                    if (!normName.includes(normSearch)) {
                        tr.style.display = 'none';
                    }
                }

                const isWaitingAuth = e.attempt.status === 'waiting_authorization' || e.attempt.is_device_authorized === false;

                tr.innerHTML = `
                    <td class="py-4 px-6 font-bold text-cloud-burst-950">
                        ${escapeHtml(e.attempt.student_name)}
                    </td>
                    <td class="py-4 px-6 font-jetbrains font-mono font-bold text-xs text-royal-purple-950 select-all">
                        <span class="inline-flex items-center gap-1 bg-royal-purple-50 border border-royal-purple-200 px-2 py-0.5 rounded-md">
                            <span>${escapeHtml(e.attempt.access_code || '—')}</span>
                            ${e.attempt.access_code ? `
                                <button type="button" onclick="navigator.clipboard?.writeText('${escapeHtml(e.attempt.access_code)}'); this.title='Copiado!';" class="cursor-pointer text-royal-purple-600 hover:text-royal-purple-900 transition ml-1" title="Copiar código">
                                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                </button>
                            ` : ''}
                        </span>
                    </td>
                    <td class="py-4 px-6" id="status-cell-${e.attempt.id}">
                        ${isWaitingAuth ? `
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300" title="Dispositivo compartilhado aguardando autorização">
                                <svg class="w-3.5 h-3.5 text-amber-700" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                                <span>Aguardando autorização</span>
                            </span>
                        ` : `
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-broom-100 text-broom-950 border border-broom-300">
                                Em andamento
                            </span>
                        `}
                    </td>
                    <td class="py-4 px-6 text-cloud-burst-900/70 text-xs">
                        ${escapeHtml(e.attempt.started_at)}
                    </td>
                    <td class="py-4 px-6 text-cloud-burst-900/70 text-xs" id="concluded-cell-${e.attempt.id}">
                        —
                    </td>
                    <td class="py-4 px-6 text-center font-bold text-royal-purple-800" id="score-cell-${e.attempt.id}">
                        ${e.attempt.score ?? 0} / ${totalQuestions}
                    </td>
                    <td class="py-4 px-6 text-center" id="tab-switches-cell-${e.attempt.id}">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-gray-100 text-gray-600 border border-gray-200">
                            <span>0</span>
                        </span>
                    </td>
                    <td class="py-4 px-6 text-right">
                        <div class="inline-flex items-center gap-1.5 justify-end" id="actions-cell-${e.attempt.id}">
                            ${isWaitingAuth && monitorData.authorizeDeviceUrlPattern ? `
                                <form action="${monitorData.authorizeDeviceUrlPattern.replace('__ATTEMPT_ID__', e.attempt.id)}" method="POST" class="inline" id="auth-form-${e.attempt.id}">
                                    <input type="hidden" name="_token" value="${monitorData.csrfToken || ''}">
                                    <button type="submit" class="cursor-pointer inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold font-heading bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-2xs" title="Autorizar este aluno no dispositivo compartilhado">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                                        <span>Autorizar</span>
                                    </button>
                                </form>
                            ` : ''}
                            <a href="${monitorData.attemptUrlPattern.replace('__ATTEMPT_ID__', e.attempt.id)}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-900 hover:bg-royal-purple-200 transition shadow-2xs">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <span>Ver Respostas</span>
                            </a>
                            ${monitorData.destroyAttemptUrlPattern ? `
                                <form action="${monitorData.destroyAttemptUrlPattern.replace('__ATTEMPT_ID__', e.attempt.id)}" method="POST" class="inline"
                                      data-confirm="Tem certeza de que deseja deletar a resposta de ${escapeHtml(e.attempt.student_name)}? Todas as respostas deste estudante para esta avaliação serão apagadas."
                                      data-confirm-title="Deletar Resposta do Estudante"
                                      data-confirm-ok="Deletar Resposta"
                                      data-confirm-tone="danger">
                                    <input type="hidden" name="_token" value="${monitorData.csrfToken || ''}">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="cursor-pointer p-1.5 rounded-xl text-cloud-burst-500 hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition" title="Deletar Resposta do Estudante">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                </form>
                            ` : ''}
                        </div>
                    </td>
                `;

                if (tbody) {
                    tbody.insertBefore(tr, tbody.firstChild);
                    setTimeout(() => {
                        tr.classList.remove('bg-emerald-50/40', 'animate-pulse');
                    }, 2000);
                }
            }
        })
        .listen('.student.updated', (e) => {
            if (!e.attempt) return;

            const row = document.getElementById(`attempt-row-${e.attempt.id}`);
            if (row) {
                row.classList.add('bg-royal-purple-50/50');
                setTimeout(() => row.classList.remove('bg-royal-purple-50/50'), 1500);
            }

            const isWaitingAuth = e.attempt.status === 'waiting_authorization' || e.attempt.is_device_authorized === false;

            const statusCell = document.getElementById(`status-cell-${e.attempt.id}`);
            if (statusCell) {
                if (isWaitingAuth) {
                    statusCell.innerHTML = `
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300" title="Dispositivo compartilhado aguardando autorização">
                            <svg class="w-3.5 h-3.5 text-amber-700" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                            <span>Aguardando autorização</span>
                        </span>
                    `;
                } else if (e.attempt.status === 'reviewed') {
                    statusCell.innerHTML = `
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            Revisão Concluída
                        </span>
                    `;
                } else if (e.attempt.status === 'submitted') {
                    statusCell.innerHTML = `
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-royal-purple-100 text-royal-purple-800 border border-royal-purple-200">
                            Submetido
                        </span>
                    `;
                } else {
                    statusCell.innerHTML = `
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-broom-100 text-broom-950 border border-broom-300">
                            Em andamento
                        </span>
                    `;
                }
            }

            // Se não está mais aguardando autorização, remove o formulário de autorizar da coluna de ações
            if (!isWaitingAuth) {
                const authForm = document.getElementById(`auth-form-${e.attempt.id}`);
                if (authForm) {
                    authForm.remove();
                }
            }

            const scoreCell = document.getElementById(`score-cell-${e.attempt.id}`);
            if (scoreCell) {
                scoreCell.textContent = `${e.attempt.score ?? 0} / ${totalQuestions}`;
            }

            const tabSwitchesCell = document.getElementById(`tab-switches-cell-${e.attempt.id}`);
            if (tabSwitchesCell && typeof e.attempt.tab_switches_count === 'number') {
                if (e.attempt.tab_switches_count > 0) {
                    tabSwitchesCell.innerHTML = `
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-amber-100 text-amber-900 border border-amber-300 animate-pulse" title="${e.attempt.tab_switches_count} saída(s) de aba detectada(s)">
                            <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>${e.attempt.tab_switches_count}</span>
                        </span>
                    `;
                } else {
                    tabSwitchesCell.innerHTML = `
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-gray-100 text-gray-600 border border-gray-200">
                            <span>0</span>
                        </span>
                    `;
                }
            }

            const concludedCell = document.getElementById(`concluded-cell-${e.attempt.id}`);
            if (concludedCell && (e.attempt.reviewed_at || e.attempt.completed_at)) {
                concludedCell.textContent = e.attempt.reviewed_at || e.attempt.completed_at;
            }
        });
}

function escapeHtml(str) {
    return (str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
