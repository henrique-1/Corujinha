/**
 * professors-monitor.js
 * Monitora em tempo real a listagem de professores cadastrados via Reverb e Echo.
 */

export function initProfessorsMonitor() {
    if (!window.Echo || !document.getElementById('professores-table-body')) {
        return;
    }

    const tbody = document.getElementById('professores-table-body');
    const emptyRow = document.getElementById('empty-professores-row');

    window.Echo.private('admin.professores')
        .listen('.professores.updated', (e) => {
            if (!e.professor) return;

            const rowId = `professor-row-${e.professor.id}`;
            const existingRow = document.getElementById(rowId);

            if (e.action === 'deleted') {
                if (existingRow) {
                    existingRow.classList.add('opacity-0', 'transition-opacity', 'duration-500');
                    setTimeout(() => existingRow.remove(), 500);
                }
                return;
            }

            if (e.action === 'created' && !existingRow) {
                if (emptyRow) {
                    emptyRow.remove();
                }

                const tr = document.createElement('tr');
                tr.id = rowId;
                tr.className = 'hover:bg-royal-purple-50/30 transition bg-emerald-50/40 animate-pulse';

                tr.innerHTML = `
                    <td class="py-4 px-6 font-bold font-heading text-cloud-burst-950">
                        ${escapeHtml(e.professor.name)}
                    </td>
                    <td class="py-4 px-6 text-cloud-burst-800">
                        ${escapeHtml(e.professor.email)}
                    </td>
                    <td class="py-4 px-6" id="prof-areas-${e.professor.id}">
                        ${renderTeachingAreas(e.professor.teaching_areas)}
                    </td>
                    <td class="py-4 px-6" id="prof-status-${e.professor.id}">
                        ${e.professor.is_active ? `
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-200">
                                <svg class="w-3.5 h-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                <span>Ativo</span>
                            </span>
                        ` : `
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-amber-100 text-amber-800 border border-amber-200">
                                <svg class="w-3.5 h-3.5 text-amber-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>Convite Pendente</span>
                            </span>
                        `}
                    </td>
                    <td class="py-4 px-6" id="prof-totp-${e.professor.id}">
                        ${e.professor.is_totp_enabled ? `
                            <span class="inline-flex items-center gap-1 text-xs font-bold font-heading text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                <svg class="w-3.5 h-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                                <span>Ativo</span>
                            </span>
                        ` : '<span class="text-xs text-gray-400">Inativo</span>'}
                    </td>
                    <td class="py-4 px-6 text-center">
                        <div class="flex items-center justify-center gap-2 flex-wrap">
                            <span class="text-xs text-royal-purple-700 italic">Atualizado em tempo real</span>
                        </div>
                    </td>
                `;

                tbody.insertBefore(tr, tbody.firstChild);
                setTimeout(() => tr.classList.remove('bg-emerald-50/40', 'animate-pulse'), 2500);
            } else if (existingRow) {
                // Atualiza a linha existente
                existingRow.classList.add('bg-royal-purple-50/50');
                setTimeout(() => existingRow.classList.remove('bg-royal-purple-50/50'), 1500);

                const areasEl = document.getElementById(`prof-areas-${e.professor.id}`);
                if (areasEl && e.professor.teaching_areas !== undefined) {
                    areasEl.innerHTML = renderTeachingAreas(e.professor.teaching_areas);
                }

                const statusEl = document.getElementById(`prof-status-${e.professor.id}`);
                if (statusEl) {
                    statusEl.innerHTML = e.professor.is_active ? `
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <svg class="w-3.5 h-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            <span>Ativo</span>
                        </span>
                    ` : `
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-amber-100 text-amber-800 border border-amber-200">
                            <svg class="w-3.5 h-3.5 text-amber-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span>Convite Pendente</span>
                        </span>
                    `;
                }

                const totpEl = document.getElementById(`prof-totp-${e.professor.id}`);
                if (totpEl) {
                    totpEl.innerHTML = e.professor.is_totp_enabled ? `
                        <span class="inline-flex items-center gap-1 text-xs font-bold font-heading text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                            <svg class="w-3.5 h-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                            <span>Ativo</span>
                        </span>
                    ` : '<span class="text-xs text-gray-400">Inativo</span>';
                }
            }
        });
}

function renderTeachingAreas(areas) {
    if (!areas || areas.length === 0) {
        return '<span class="text-xs text-cloud-burst-400 font-medium">—</span>';
    }
    const badges = areas.map(area => `
        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-royal-purple-50 text-royal-purple-800 border border-royal-purple-200">
            ${escapeHtml(area)}
        </span>
    `).join('');
    return `<div class="flex flex-wrap gap-1.5 max-w-xs">${badges}</div>`;
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
