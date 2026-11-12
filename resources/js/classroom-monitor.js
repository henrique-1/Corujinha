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

                tr.innerHTML = `
                    <td class="py-4 px-6 font-bold text-cloud-burst-950">
                        ${escapeHtml(e.attempt.student_name)}
                    </td>
                    <td class="py-4 px-6 font-jetbrains font-mono font-bold text-xs text-royal-purple-950 select-all">
                        <span class="inline-flex items-center gap-1 bg-royal-purple-50 border border-royal-purple-200 px-2 py-0.5 rounded-md">
                            <span>${escapeHtml(e.attempt.access_code || '—')}</span>
                            ${e.attempt.access_code ? `
                                <button type="button" onclick="navigator.clipboard?.writeText('${escapeHtml(e.attempt.access_code)}'); this.title='Copiado!';" class="cursor-pointer text-royal-purple-600 hover:text-royal-purple-900 transition" title="Copiar código">
                                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                </button>
                            ` : ''}
                        </span>
                    </td>
                    <td class="py-4 px-6" id="status-cell-${e.attempt.id}">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-broom-100 text-broom-950 border border-broom-300">
                            Em andamento
                        </span>
                    </td>
                    <td class="py-4 px-6 text-cloud-burst-900/70 text-xs">
                        ${escapeHtml(e.attempt.started_at)}
                    </td>
                    <td class="py-4 px-6 text-cloud-burst-900/70 text-xs" id="concluded-cell-${e.attempt.id}">
                        —
                    </td>
                    <td class="py-4 px-6 text-center font-bold text-royal-purple-800" id="score-cell-${e.attempt.id}">
                        ${e.attempt.score} / ${totalQuestions}
                    </td>
                    <td class="py-4 px-6 text-right">
                        <a href="${monitorData.attemptUrlPattern.replace('__ATTEMPT_ID__', e.attempt.id)}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-900 hover:bg-royal-purple-200 transition shadow-2xs">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <span>Ver Respostas</span>
                        </a>
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

            const statusCell = document.getElementById(`status-cell-${e.attempt.id}`);
            if (statusCell) {
                if (e.attempt.status === 'reviewed') {
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

            const scoreCell = document.getElementById(`score-cell-${e.attempt.id}`);
            if (scoreCell) {
                scoreCell.textContent = `${e.attempt.score} / ${totalQuestions}`;
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
