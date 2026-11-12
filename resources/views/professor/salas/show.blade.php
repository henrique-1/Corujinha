<x-layouts.admin title="Painel da Sala · Corujinha">
    <div class="space-y-6">
        <div>
            <a href="{{ route('professor.salas.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-royal-purple-800 hover:text-royal-purple-900 hover:underline mb-2 transition">
                <x-lucide-arrow-left class="w-4 h-4" />
                <span>Voltar para Minhas Salas</span>
            </a>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-cloud-burst-950 font-heading tracking-tight">
                        {{ $classroom->name }}
                    </h1>
                    <p class="text-sm text-cloud-burst-900/70 mt-1">
                        Avaliação: <span class="font-bold text-cloud-burst-950">{{ $classroom->assessment->title }}</span> ({{ $totalQuestions }} questões)
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="{{ route('professor.salas.preview', $classroom) }}"
                       class="cursor-pointer text-xs font-bold font-heading px-4 py-2.5 rounded-xl border border-sky-blue-300 bg-sky-blue-50 hover:bg-sky-blue-100 text-sky-blue-950 transition flex items-center gap-2 shadow-2xs"
                       title="Visualizar a prova como o aluno vê, com navegação livre">
                        <x-lucide-eye class="w-4 h-4 text-sky-blue-700" />
                        <span>Visualizar Prova</span>
                    </a>

                    <a href="{{ route('professor.salas.edit', $classroom) }}"
                       class="cursor-pointer text-xs font-bold font-heading px-4 py-2.5 rounded-xl border border-magnolia-300 bg-white hover:border-royal-purple-800 text-cloud-burst-950 transition flex items-center gap-2 shadow-2xs">
                        <x-lucide-edit class="w-4 h-4 text-royal-purple-800" />
                        <span>Editar Sala</span>
                    </a>

                    <form action="{{ route('professor.salas.devolutiva.enter', $classroom) }}" method="POST"
                          @if($classroom->is_active)
                              data-confirm="Ao entrar na Devolutiva, a sala será fechada definitivamente para novas respostas e não poderá mais ser reaberta. Deseja continuar?"
                              data-confirm-title="Entrar na Devolutiva"
                              data-confirm-ok="Fechar Sala e Abrir Devolutiva"
                              data-confirm-tone="primary"
                          @endif>
                        @csrf
                        <button type="submit" class="cursor-pointer text-xs font-bold font-heading px-4 py-2.5 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white shadow-sm transition flex items-center gap-2">
                            <x-lucide-presentation class="w-4 h-4" />
                            <span>Devolutiva</span>
                        </button>
                    </form>

                    @if($classroom->is_active)
                        <form action="{{ route('professor.salas.toggle_status', $classroom) }}" method="POST"
                              data-confirm="Fechar a sala impede novas respostas definitivamente e não pode ser desfeito. Deseja fechar agora?"
                              data-confirm-title="Fechar Sala de Aula"
                              data-confirm-ok="Fechar Definitivamente"
                              data-confirm-tone="warning">
                            @csrf
                            <button type="submit" class="cursor-pointer text-xs font-bold font-heading px-4 py-2.5 rounded-xl border border-broom-400 text-broom-950 bg-broom-50 hover:bg-broom-100 transition flex items-center gap-2">
                                <x-lucide-lock class="w-4 h-4 text-broom-800" />
                                <span>Fechar Sala para Respostas</span>
                            </button>
                        </form>
                    @else
                        <span class="text-xs font-bold font-heading px-4 py-2.5 rounded-xl border border-gray-300 bg-gray-100 text-gray-700 inline-flex items-center gap-2">
                            <x-lucide-lock class="w-4 h-4 text-gray-500" />
                            <span>Sala Encerrada</span>
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Top Cards: Código e Configuração --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Big Code Card --}}
            <div class="bg-gradient-to-br from-royal-purple-950 to-royal-purple-900 rounded-2xl p-6 text-white text-center shadow-md flex flex-col justify-center items-center">
                <span class="text-xs font-bold uppercase tracking-wider text-royal-purple-200 mb-1">
                    Código de Entrada do Aluno
                </span>
                <span class="text-4xl md:text-5xl font-jetbrains font-mono font-black tracking-widest text-broom-400 my-2 select-all tabular-nums">
                    {{ $classroom->code }}
                </span>
                <p class="text-[11px] text-royal-purple-200 mt-1">
                    Peça aos estudantes para acessarem <b>/sala</b> e digitarem este código.
                </p>
            </div>

            {{-- Status Card --}}
            <div class="bg-white rounded-2xl p-6 border border-royal-purple-200/60 shadow-xs flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-cloud-burst-900/60 uppercase tracking-wider block mb-2">Status da Sala</span>
                    <div class="flex items-center gap-2">
                        @if($classroom->is_active)
                            <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-lg font-bold text-emerald-700 font-heading">Aberta para Respostas</span>
                        @else
                            <span class="w-3 h-3 rounded-full bg-gray-400"></span>
                            <span class="text-lg font-bold text-gray-600 font-heading">Fechada</span>
                        @endif
                    </div>
                </div>
                <div class="pt-4 border-t border-royal-purple-100 mt-4 text-xs text-cloud-burst-900/60">
                    Criada em {{ $classroom->created_at->format('d/m/Y H:i') }}
                </div>
            </div>

            {{-- Review Mode Card --}}
            <div class="bg-white rounded-2xl p-6 border border-royal-purple-200/60 shadow-xs flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-cloud-burst-900/60 uppercase tracking-wider block mb-2">Revisão e Correção Coletiva</span>
                    @if($classroom->collective_review_enabled)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            <x-lucide-check class="w-3.5 h-3.5" />
                            <span>Ativa na Turma (até {{ $classroom->effectiveMaxReviewChanges() }} alteração(ões))</span>
                        </span>
                        <p class="text-xs text-cloud-burst-900/70 mt-2">
                            Alunos podem alterar até <b>{{ $classroom->effectiveMaxReviewChanges() }}</b> questão(ões) e justificar mudanças antes de ver o gabarito.
                        </p>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700 border border-gray-300">
                            <x-lucide-x class="w-3.5 h-3.5" />
                            <span>Desativada</span>
                        </span>
                        <p class="text-xs text-cloud-burst-900/70 mt-2">
                            Alunos veem o gabarito e devolutivas imediatamente após a última questão.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Students Attempts Table --}}
        <div class="bg-white rounded-2xl border border-royal-purple-200/60 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-royal-purple-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 id="attempts-total-count" class="text-base font-bold text-cloud-burst-950 font-heading">
                        Respostas dos Estudantes ({{ $attempts->total() }})
                    </h2>
                    <p class="text-xs text-cloud-burst-900/60">Acompanhamento das tentativas registradas nesta sala</p>
                </div>

                {{-- Filtro por Nome de Aluno e Ordenação --}}
                <form id="student-search-form" method="GET" action="{{ route('professor.salas.show', $classroom) }}" class="flex items-center flex-wrap gap-2">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="direction" value="{{ $direction }}">

                    <div class="relative flex items-center">
                        <x-lucide-search class="w-4 h-4 absolute left-3 text-cloud-burst-400 pointer-events-none" />
                        <input id="student-search-input" type="text" name="search" value="{{ $search }}" placeholder="Filtrar por nome de aluno..." autocomplete="off"
                               class="pl-9 pr-8 py-2 text-xs border border-royal-purple-200 rounded-xl focus:border-royal-purple-800 focus:outline-none w-56 sm:w-64 bg-royal-purple-50/30 text-cloud-burst-950 placeholder:text-cloud-burst-400 transition">
                        <button id="clear-search-btn" type="button" class="cursor-pointer absolute right-2 text-cloud-burst-400 hover:text-cloud-burst-700 p-0.5 rounded-md transition {{ $search ? '' : 'hidden' }}" title="Limpar filtro">
                            <x-lucide-x class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <noscript>
                        <button type="submit" class="cursor-pointer px-3.5 py-2 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white text-xs font-bold font-heading transition shadow-2xs">
                            Filtrar
                        </button>
                    </noscript>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-royal-purple-50/60 border-b border-royal-purple-100 text-cloud-burst-900/70 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-6">
                                <a href="{{ route('professor.salas.show', array_merge([$classroom], array_filter(['search' => $search, 'sort' => 'nome', 'direction' => ($sort === 'nome' && $direction === 'asc') ? 'desc' : 'asc']))) }}"
                                   class="inline-flex items-center gap-1.5 hover:text-royal-purple-900 transition">
                                    <span>Estudante</span>
                                    @if($sort === 'nome')
                                        @if($direction === 'asc')
                                            <x-lucide-arrow-up class="w-3.5 h-3.5 text-royal-purple-700" />
                                        @else
                                            <x-lucide-arrow-down class="w-3.5 h-3.5 text-royal-purple-700" />
                                        @endif
                                    @else
                                        <x-lucide-arrow-up-down class="w-3 h-3 text-cloud-burst-300" />
                                    @endif
                                </a>
                            </th>
                            <th class="py-3.5 px-6">Código do Aluno</th>
                            <th class="py-3.5 px-6">
                                <a href="{{ route('professor.salas.show', array_merge([$classroom], array_filter(['search' => $search, 'sort' => 'status', 'direction' => ($sort === 'status' && $direction === 'asc') ? 'desc' : 'asc']))) }}"
                                   class="inline-flex items-center gap-1.5 hover:text-royal-purple-900 transition">
                                    <span>Status</span>
                                    @if($sort === 'status')
                                        @if($direction === 'asc')
                                            <x-lucide-arrow-up class="w-3.5 h-3.5 text-royal-purple-700" />
                                        @else
                                            <x-lucide-arrow-down class="w-3.5 h-3.5 text-royal-purple-700" />
                                        @endif
                                    @else
                                        <x-lucide-arrow-up-down class="w-3 h-3 text-cloud-burst-300" />
                                    @endif
                                </a>
                            </th>
                            <th class="py-3.5 px-6">
                                <a href="{{ route('professor.salas.show', array_merge([$classroom], array_filter(['search' => $search, 'sort' => 'inicio', 'direction' => ($sort === 'inicio' && $direction === 'asc') ? 'desc' : 'asc']))) }}"
                                   class="inline-flex items-center gap-1.5 hover:text-royal-purple-900 transition"
                                   title="Padrão: Mais recente primeiro">
                                    <span>Início</span>
                                    @if($sort === 'inicio')
                                        @if($direction === 'asc')
                                            <x-lucide-arrow-up class="w-3.5 h-3.5 text-royal-purple-700" />
                                        @else
                                            <x-lucide-arrow-down class="w-3.5 h-3.5 text-royal-purple-700" />
                                        @endif
                                    @else
                                        <x-lucide-arrow-up-down class="w-3 h-3 text-cloud-burst-300" />
                                    @endif
                                </a>
                            </th>
                            <th class="py-3.5 px-6">
                                <a href="{{ route('professor.salas.show', array_merge([$classroom], array_filter(['search' => $search, 'sort' => 'conclusao', 'direction' => ($sort === 'conclusao' && $direction === 'asc') ? 'desc' : 'asc']))) }}"
                                   class="inline-flex items-center gap-1.5 hover:text-royal-purple-900 transition">
                                    <span>Conclusão</span>
                                    @if($sort === 'conclusao')
                                        @if($direction === 'asc')
                                            <x-lucide-arrow-up class="w-3.5 h-3.5 text-royal-purple-700" />
                                        @else
                                            <x-lucide-arrow-down class="w-3.5 h-3.5 text-royal-purple-700" />
                                        @endif
                                    @else
                                        <x-lucide-arrow-up-down class="w-3 h-3 text-cloud-burst-300" />
                                    @endif
                                </a>
                            </th>
                            <th class="py-3.5 px-6 text-center">
                                <a href="{{ route('professor.salas.show', array_merge([$classroom], array_filter(['search' => $search, 'sort' => 'acertos', 'direction' => ($sort === 'acertos' && $direction === 'asc') ? 'desc' : 'asc']))) }}"
                                   class="inline-flex items-center justify-center gap-1.5 hover:text-royal-purple-900 transition">
                                    <span>Acertos</span>
                                    @if($sort === 'acertos')
                                        @if($direction === 'asc')
                                            <x-lucide-arrow-up class="w-3.5 h-3.5 text-royal-purple-700" />
                                        @else
                                            <x-lucide-arrow-down class="w-3.5 h-3.5 text-royal-purple-700" />
                                        @endif
                                    @else
                                        <x-lucide-arrow-up-down class="w-3 h-3 text-cloud-burst-300" />
                                    @endif
                                </a>
                            </th>
                            <th class="py-3.5 px-6 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="attempts-table-body" class="divide-y divide-royal-purple-100/60">
                        <tr id="no-filter-results-row" class="hidden">
                            <td colspan="7" class="py-8 text-center text-cloud-burst-900/60">
                                Nenhum aluno encontrado para "<b id="no-results-term"></b>" nesta lista.
                            </td>
                        </tr>
                        @forelse($attempts as $att)
                            <tr id="attempt-row-{{ $att->uuid }}" data-student-row data-student-name="{{ mb_strtolower($att->student_name) }}" class="hover:bg-royal-purple-50/30 transition">
                                <td class="py-4 px-6 font-bold text-cloud-burst-950">
                                    {{ $att->student_name }}
                                </td>
                                <td class="py-4 px-6 font-jetbrains font-mono font-bold text-xs text-royal-purple-950 select-all">
                                    <span class="inline-flex items-center gap-1 bg-royal-purple-50 border border-royal-purple-200 px-2.5 py-1 rounded-lg">
                                        <span>{{ $att->access_code ?? '—' }}</span>
                                        @if($att->access_code)
                                            <button type="button" onclick="navigator.clipboard?.writeText('{{ $att->access_code }}'); this.title='Copiado!'; setTimeout(() => this.title='Copiar', 2000)" class="cursor-pointer text-royal-purple-600 hover:text-royal-purple-900 transition ml-1" title="Copiar código">
                                                <x-lucide-copy class="w-3 h-3" />
                                            </button>
                                        @endif
                                    </span>
                                </td>
                                <td id="status-cell-{{ $att->uuid }}" class="py-4 px-6">
                                    @if($att->status === 'reviewed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Revisão Concluída
                                        </span>
                                    @elseif($att->status === 'submitted')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-royal-purple-100 text-royal-purple-800 border border-royal-purple-200">
                                            Submetido
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-broom-100 text-broom-950 border border-broom-300">
                                            Em andamento
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-cloud-burst-900/70 text-xs">
                                    {{ $att->started_at->format('d/m/Y H:i') }}
                                </td>
                                <td id="concluded-cell-{{ $att->uuid }}" class="py-4 px-6 text-cloud-burst-900/70 text-xs">
                                    {{ $att->reviewed_at ? $att->reviewed_at->format('d/m/Y H:i') : ($att->completed_at ? $att->completed_at->format('d/m/Y H:i') : '—') }}
                                </td>
                                <td id="score-cell-{{ $att->uuid }}" class="py-4 px-6 text-center font-bold text-royal-purple-800">
                                    {{ $att->score }} / {{ $totalQuestions }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('professor.salas.tentativa', [$classroom, $att]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-900 hover:bg-royal-purple-200 transition shadow-2xs">
                                        <x-lucide-eye class="w-3.5 h-3.5" />
                                        <span>Ver Respostas</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-attempts-row">
                                <td colspan="7" class="py-8 text-center text-cloud-burst-900/60">
                                    @if($search)
                                        Nenhum aluno encontrado para "<b>{{ $search }}</b>". <a href="{{ route('professor.salas.show', $classroom) }}" class="text-royal-purple-800 underline font-semibold ml-1">Limpar busca</a>
                                    @else
                                        Nenhum aluno entrou nesta sala ainda. Projete o código <b class="font-jetbrains font-mono font-bold text-royal-purple-800 tracking-wider">{{ $classroom->code }}</b> para iniciar.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($attempts->hasPages())
                <div class="p-4 border-t border-royal-purple-100">
                    {{ $attempts->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            window.__CLASSROOM_MONITOR__ = {
                classroomId: @json($classroom->uuid),
                totalAttempts: {{ $attempts->total() }},
                totalQuestions: {{ $totalQuestions }},
                attemptUrlPattern: "{{ route('professor.salas.tentativa', [$classroom, '__ATTEMPT_ID__']) }}"
            };

            // Filtro Dinâmico em Tempo Real por Nome de Aluno (Instantâneo)
            document.addEventListener('DOMContentLoaded', () => {
                const searchInput = document.getElementById('student-search-input');
                const clearBtn = document.getElementById('clear-search-btn');
                const tbody = document.getElementById('attempts-table-body');
                const noResultsRow = document.getElementById('no-filter-results-row');
                const noResultsTerm = document.getElementById('no-results-term');
                const countEl = document.getElementById('attempts-total-count');
                const initialTotal = {{ $attempts->total() }};
                let debounceTimer = null;

                if (!searchInput || !tbody) return;

                function normalize(str) {
                    return (str || '')
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .trim();
                }

                function filterRowsLocally() {
                    const query = normalize(searchInput.value);
                    const rows = tbody.querySelectorAll('[data-student-row]');
                    let matchedCount = 0;

                    if (clearBtn) {
                        clearBtn.classList.toggle('hidden', !searchInput.value.trim());
                    }

                    rows.forEach((row) => {
                        const name = normalize(row.getAttribute('data-student-name') || '');
                        const matches = name.includes(query);
                        row.style.display = matches ? '' : 'none';
                        if (matches) matchedCount++;
                    });

                    if (noResultsRow) {
                        if (query && matchedCount === 0 && rows.length > 0) {
                            noResultsRow.classList.remove('hidden');
                            if (noResultsTerm) noResultsTerm.textContent = searchInput.value;
                        } else {
                            noResultsRow.classList.add('hidden');
                        }
                    }

                    if (countEl) {
                        if (query) {
                            countEl.textContent = `Respostas dos Estudantes (${matchedCount} encontrados)`;
                        } else {
                            countEl.textContent = `Respostas dos Estudantes (${initialTotal})`;
                        }
                    }
                }

                // Dispara filtragem instantânea a cada caractere digitado
                searchInput.addEventListener('input', () => {
                    filterRowsLocally();

                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        const url = new URL(window.location.href);
                        if (searchInput.value.trim()) {
                            url.searchParams.set('search', searchInput.value.trim());
                        } else {
                            url.searchParams.delete('search');
                        }
                        url.searchParams.delete('page');
                        window.history.replaceState({}, '', url.toString());

                        @if($attempts->hasPages() || $attempts->total() > 20)
                            fetch(url.toString(), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                .then(res => res.text())
                                .then(html => {
                                    const doc = new DOMParser().parseFromString(html, 'text/html');
                                    const newTbody = doc.getElementById('attempts-table-body');
                                    if (newTbody) {
                                        tbody.innerHTML = newTbody.innerHTML;
                                        filterRowsLocally();
                                    }
                                })
                                .catch(() => {});
                        @endif
                    }, 350);
                });

                if (clearBtn) {
                    clearBtn.addEventListener('click', () => {
                        searchInput.value = '';
                        filterRowsLocally();
                        searchInput.focus();

                        const url = new URL(window.location.href);
                        url.searchParams.delete('search');
                        window.history.replaceState({}, '', url.toString());
                    });
                }

                if (searchInput.value) {
                    filterRowsLocally();
                }
            });
        </script>
    @endpush
</x-layouts.admin>
