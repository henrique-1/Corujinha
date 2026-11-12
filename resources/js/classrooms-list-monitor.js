/**
 * classrooms-list-monitor.js
 * Monitora em tempo real a listagem de salas de aula do professor via Reverb e Echo.
 */

export function initClassroomsListMonitor() {
    const config = window.__CLASSROOMS_LIST_CONFIG__;
    if (!config || !window.Echo) {
        return;
    }

    const tbody = document.getElementById('classrooms-table-body');
    const emptyRow = document.getElementById('empty-classrooms-row');

    function buildRowInnerHtml(c) {
        const limit = c.effective_max_review_changes ?? c.max_review_changes ?? 1;
        return `
            <td class="py-4 px-6 font-mono font-extrabold text-base text-royal-purple-800">
                <span class="px-2.5 py-1 rounded-lg bg-royal-purple-100 border border-royal-purple-200">
                    ${escapeHtml(c.code)}
                </span>
            </td>
            <td class="py-4 px-6 font-bold font-heading text-cloud-burst-950">
                <div class="flex items-center gap-2 flex-wrap">
                    <span>${escapeHtml(c.name)}</span>
                    ${!c.is_active ? `
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold font-heading bg-gray-100 text-gray-700 border border-gray-300">
                            <span>Fechada</span>
                        </span>
                    ` : ''}
                </div>
            </td>
            <td class="py-4 px-6 text-cloud-burst-800">
                ${escapeHtml(c.assessment_title || '—')}
            </td>
            <td class="py-4 px-6">
                ${c.collective_review_enabled ? `
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-200">
                        <svg class="w-3.5 h-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>Ativa (até ${limit} alt.)</span>
                    </span>
                ` : `
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-gray-100 text-gray-600 border border-gray-200">
                        Desativada (Direto ao Resultado)
                    </span>
                `}
            </td>
            <td class="py-4 px-6">
                <span class="text-xs font-bold font-heading text-cloud-burst-800 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-royal-purple-800" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span id="classroom-students-count-${c.id}">${c.student_attempts_count} aluno(s)</span>
                </span>
            </td>
            <td class="py-4 px-6">
                <div class="flex items-center justify-end gap-2 flex-wrap">
                    <a href="/professor/salas/${c.id}" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-royal-purple-800 hover:underline bg-royal-purple-50 hover:bg-royal-purple-100 px-3 py-1.5 rounded-lg border border-royal-purple-200 transition">
                        <span>Painel</span>
                    </a>
                    <a href="/professor/salas/${c.id}/edit" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-cloud-burst-800 hover:bg-magnolia-100 bg-white px-3 py-1.5 rounded-lg border border-magnolia-300 transition">
                        <span>Editar</span>
                    </a>
                </div>
            </td>
        `;
    }

    window.Echo.channel(`professor.${config.userId}`)
        .listen('.student.joined', (e) => {
            if (!e.attempt || !e.attempt.classroom_id) return;

            const countEl = document.getElementById(`classroom-students-count-${e.attempt.classroom_id}`);
            if (countEl) {
                const currentText = countEl.textContent || '0';
                const match = currentText.match(/\d+/);
                const currentCount = match ? parseInt(match[0], 10) : 0;
                const newCount = currentCount + 1;
                countEl.textContent = `${newCount} aluno(s)`;

                countEl.classList.add('text-emerald-600', 'scale-110', 'transition-transform');
                setTimeout(() => {
                    countEl.classList.remove('text-emerald-600', 'scale-110', 'transition-transform');
                }, 2000);
            }
        })
        .listen('.salas.updated', (e) => {
            if (!e.classroom || !tbody) return;

            const rowId = `classroom-row-${e.classroom.id}`;
            const existingRow = document.getElementById(rowId);

            if (e.action === 'deleted') {
                if (existingRow) {
                    existingRow.classList.add('opacity-0', 'transition-opacity', 'duration-500');
                    setTimeout(() => existingRow.remove(), 500);
                }
                return;
            }

            if (e.action === 'updated' && existingRow) {
                existingRow.innerHTML = buildRowInnerHtml(e.classroom);
                existingRow.classList.add('bg-emerald-50/40');
                setTimeout(() => existingRow.classList.remove('bg-emerald-50/40'), 2000);
                return;
            }

            if (e.action === 'created' && !existingRow) {
                if (emptyRow) {
                    emptyRow.remove();
                }

                const tr = document.createElement('tr');
                tr.id = rowId;
                tr.className = 'hover:bg-royal-purple-50/30 transition bg-emerald-50/40 animate-pulse';
                tr.innerHTML = buildRowInnerHtml(e.classroom);

                tbody.insertBefore(tr, tbody.firstChild);
                setTimeout(() => tr.classList.remove('bg-emerald-50/40', 'animate-pulse'), 2500);
            }
        });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
