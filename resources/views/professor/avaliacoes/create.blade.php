<x-layouts.admin title="Nova Avaliação · Corujinha">
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <a href="{{ route('professor.avaliacoes.index') }}" class="text-xs font-bold font-heading text-royal-purple-800 hover:underline mb-2 inline-flex items-center gap-1">
                <x-lucide-arrow-left class="w-3.5 h-3.5" />
                <span>Voltar para Minhas Avaliações</span>
            </a>
            <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                Criar Nova Avaliação
            </h1>
            <p class="text-sm text-cloud-burst-700 mt-1">
                Dê um título para a avaliação e selecione as perguntas do seu banco que irão compô-la
            </p>
        </div>

        <form action="{{ route('professor.avaliacoes.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-white rounded-2xl p-6 border border-magnolia-200 shadow-xs space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label for="title" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Título da Avaliação <span class="text-shocking-pink-600">*</span>
                        </label>
                        <input id="title" name="title" type="text" required value="{{ old('title') }}" placeholder="Ex.: Avaliação Diagnóstica · 7º Ano · Língua Portuguesa"
                               class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                    </div>
                    <div>
                        <label for="academic_period" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Período Letivo (opcional)
                        </label>
                        <input id="academic_period" name="academic_period" type="text" value="{{ old('academic_period') }}" placeholder="Ex.: 1º Bimestre ou 1ª Etapa"
                               class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Instruções ou Descrição Geral (opcional)
                    </label>
                    <textarea id="description" name="description" rows="2" placeholder="Orientações aos estudantes antes de iniciarem o teste..."
                              class="w-full px-4 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-magnolia-200 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold font-heading text-cloud-burst-950 flex items-center gap-2">
                            <span>Perguntas Disponíveis</span>
                        </h2>
                        <span class="text-xs text-cloud-burst-700">Selecione as perguntas e defina os pontos de cada questão</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="document.getElementById('modal-auto-select').classList.remove('hidden')"
                                class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-broom-400 hover:bg-broom-500 text-broom-950 border border-broom-500 shadow-2xs transition">
                            <x-lucide-sparkles class="w-3.5 h-3.5 text-broom-950" />
                            <span>Gerar Avaliação Automática</span>
                        </button>
                        <span id="selected-questions-count" class="px-3 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-900 border border-royal-purple-200">
                            0 selecionada(s)
                        </span>
                        <span id="total-assessment-points" class="px-3 py-1 rounded-full text-xs font-bold font-heading bg-broom-200 text-broom-950 border border-broom-300">
                            Total: 0.00 pts
                        </span>
                    </div>
                </div>

                {{-- Filtros Rápidos de Seleção --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3 bg-magnolia-50/70 rounded-xl border border-magnolia-200">
                    <div>
                        <input type="text" id="filter-search" placeholder="Filtrar por texto..."
                               class="w-full px-3 py-1.5 text-xs border border-magnolia-300 rounded-lg bg-white focus:outline-none focus:border-royal-purple-800">
                    </div>
                    <div>
                        <select id="filter-area" class="w-full px-3 py-1.5 text-xs border border-magnolia-300 rounded-lg bg-white focus:outline-none focus:border-royal-purple-800">
                            <option value="">Todas as áreas</option>
                            @foreach($areas as $area)
                                <option value="{{ $area }}">{{ $area }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select id="filter-grade" class="w-full px-3 py-1.5 text-xs border border-magnolia-300 rounded-lg bg-white focus:outline-none focus:border-royal-purple-800">
                            <option value="">Todas as turmas</option>
                            @foreach($gradeLevels as $grade)
                                <option value="{{ $grade }}">{{ $grade }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if($questions->isEmpty())
                    <div class="p-6 rounded-xl bg-royal-purple-50/50 border border-royal-purple-200 text-center text-xs text-royal-purple-900 space-y-2">
                        <p class="font-bold font-heading">Você ainda não tem perguntas cadastradas no seu banco.</p>
                        <a href="{{ route('professor.perguntas.create') }}" class="inline-block text-royal-purple-800 font-bold font-heading underline">
                            Clique aqui para cadastrar perguntas primeiro →
                        </a>
                    </div>
                @else
                    <div id="questions-list" class="divide-y divide-magnolia-100 border border-magnolia-200 rounded-xl max-h-96 overflow-y-auto">
                        @foreach($questions as $q)
                            <div class="question-item flex items-center justify-between gap-3 p-4 hover:bg-royal-purple-50/30 transition"
                                 data-area="{{ $q->area }}" data-grade="{{ $q->grade_level }}" data-prompt="{{ strtolower($q->prompt . ' ' . $q->support_title . ' ' . $q->focus) }}">
                                <label class="flex items-start gap-3 cursor-pointer grow">
                                    <input type="checkbox" name="question_ids[]" value="{{ $q->uuid }}" {{ is_array(old('question_ids')) && in_array($q->uuid, old('question_ids')) ? 'checked' : '' }}
                                           class="question-checkbox mt-1 rounded text-royal-purple-800 focus:ring-royal-purple-800 w-4 h-4">
                                    <div class="grow">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            @if($q->hasTextSupport())
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-royal-purple-100 text-royal-purple-900">
                                                    Texto
                                                </span>
                                            @endif
                                            @if($q->hasImageSupport())
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-sky-blue-100 text-sky-blue-950">
                                                    Imagem
                                                </span>
                                            @endif
                                            @if($q->area)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-gray-100 text-gray-800 border border-gray-200">{{ $q->area }}</span>
                                            @endif
                                            @if($q->grade_level)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-broom-100 text-broom-950 border border-broom-200">{{ $q->grade_level }}</span>
                                            @endif
                                            <span class="text-[10px] text-cloud-burst-600 font-mono flex items-center gap-0.5">
                                                <x-lucide-clock class="w-3 h-3 text-cloud-burst-600 inline" />
                                                <span>{{ ceil($q->time_limit_seconds / 60) }} min</span>
                                            </span>
                                        </div>
                                        <p class="text-xs text-cloud-burst-950 font-medium line-clamp-2">{{ $q->prompt }}</p>
                                        @if($q->support_title)
                                            <p class="text-[11px] text-royal-purple-700 mt-0.5 truncate italic">Fonte: {{ $q->support_title }}</p>
                                        @endif
                                    </div>
                                </label>

                                <div class="flex items-center justify-center shrink-0 pl-3 border-l border-magnolia-200">
                                    <span class="text-[11px] text-cloud-burst-600 whitespace-nowrap">Gabarito: <b class="text-cloud-burst-950">{{ $q->correct_option }}</b></span>
                                </div>

                                <div class="flex items-center gap-1.5 shrink-0 pl-3 border-l border-magnolia-200">
                                    <label class="text-[11px] font-bold font-heading text-cloud-burst-800">Pontos:</label>
                                    <input type="number" step="0.01" min="0.01" max="99.99" name="points[{{ $q->uuid }}]" value="{{ number_format((float) old('points.'.$q->uuid, '1.00'), 2, '.', '') }}"
                                           class="question-point-input w-20 px-2 py-1 text-xs border border-magnolia-300 rounded-lg text-center font-mono font-bold focus:border-royal-purple-800 focus:outline-none">
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('professor.avaliacoes.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-300 text-cloud-burst-800 text-xs font-bold font-heading hover:bg-gray-50 transition">
                    Cancelar
                </a>
                <button type="submit" class="cursor-pointer px-6 py-3 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white text-sm font-bold font-heading shadow-md transition flex items-center gap-2">
                    <span>Salvar Avaliação</span>
                    <x-lucide-arrow-right class="w-4 h-4" />
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const checkboxes = document.querySelectorAll('.question-checkbox');
                const pointInputs = document.querySelectorAll('.question-point-input');
                const countBadge = document.getElementById('selected-questions-count');
                const totalPointsBadge = document.getElementById('total-assessment-points');

                function recalcTotal() {
                    let count = 0;
                    let total = 0.0;
                    checkboxes.forEach(cb => {
                        if (cb.checked) {
                            count++;
                            const parent = cb.closest('.question-item');
                            const ptInput = parent ? parent.querySelector('.question-point-input') : null;
                            const val = ptInput ? parseFloat(ptInput.value) : 1.0;
                            total += isNaN(val) ? 0 : val;
                        }
                    });
                    if (countBadge) countBadge.textContent = `${count} selecionada(s)`;
                    if (totalPointsBadge) totalPointsBadge.textContent = `Total: ${total.toFixed(2)} pts`;
                }

                checkboxes.forEach(cb => cb.addEventListener('change', recalcTotal));
                pointInputs.forEach(pi => {
                    pi.addEventListener('input', () => {
                        const valStr = pi.value;
                        if (valStr.includes('.')) {
                            const parts = valStr.split('.');
                            if (parts[1].length > 2) {
                                pi.value = `${parts[0]}.${parts[1].slice(0, 2)}`;
                            }
                        }
                        recalcTotal();
                    });
                    pi.addEventListener('blur', () => {
                        const val = parseFloat(pi.value);
                        if (!isNaN(val)) {
                            const clamped = Math.min(99.99, Math.max(0.01, val));
                            pi.value = clamped.toFixed(2);
                            recalcTotal();
                        }
                    });
                });
                recalcTotal();

                // Filtros de perguntas
                const filterSearch = document.getElementById('filter-search');
                const filterArea = document.getElementById('filter-area');
                const filterGrade = document.getElementById('filter-grade');
                const questionItems = document.querySelectorAll('.question-item');

                function applyFilters() {
                    const qText = (filterSearch?.value || '').toLowerCase();
                    const qArea = filterArea?.value || '';
                    const qGrade = filterGrade?.value || '';

                    questionItems.forEach(item => {
                        const itemArea = item.dataset.area || '';
                        const itemGrade = item.dataset.grade || '';
                        const itemPrompt = item.dataset.prompt || '';

                        const matchText = !qText || itemPrompt.includes(qText);
                        const matchArea = !qArea || itemArea === qArea;
                        const matchGrade = !qGrade || itemGrade === qGrade;

                        item.style.display = (matchText && matchArea && matchGrade) ? 'flex' : 'none';
                    });
                }

                filterSearch?.addEventListener('input', applyFilters);
                filterArea?.addEventListener('change', applyFilters);
                filterGrade?.addEventListener('change', applyFilters);

                // Geração Automática de Avaliação
                const btnAutoSelect = document.getElementById('btn-run-auto-select');
                btnAutoSelect?.addEventListener('click', async () => {
                    const area = document.getElementById('auto_area').value;
                    const grade_level = document.getElementById('auto_grade').value;
                    const focus = document.getElementById('auto_focus').value;
                    const quantity = parseInt(document.getElementById('auto_quantity').value, 10) || 10;
                    const mode = document.getElementById('auto_mode').value;
                    const feedback = document.getElementById('auto-select-feedback');

                    btnAutoSelect.disabled = true;
                    btnAutoSelect.classList.add('opacity-50');

                    try {
                        const response = await fetch('{{ route('professor.avaliacoes.auto_select') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: JSON.stringify({ area, grade_level, focus, quantity, mode }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            const uuids = new Set(data.question_uuids || []);

                            checkboxes.forEach(cb => {
                                cb.checked = uuids.has(cb.value);
                            });

                            recalcTotal();

                            if (uuids.size === 0) {
                                feedback.className = 'p-3 rounded-xl text-xs font-medium bg-amber-50 text-amber-900 border border-amber-200 block';
                                feedback.textContent = 'Nenhuma questão encontrada com os critérios informados. Tente ampliar os filtros.';
                            } else {
                                feedback.className = 'p-3 rounded-xl text-xs font-medium bg-emerald-50 text-emerald-900 border border-emerald-200 block';
                                if (uuids.size < quantity) {
                                    feedback.textContent = `Encontradas apenas ${uuids.size} questão(ões) com esses critérios. Todas foram selecionadas!`;
                                } else {
                                    feedback.textContent = `${uuids.size} questão(ões) selecionada(s) automaticamente com sucesso!`;
                                }

                                setTimeout(() => {
                                    document.getElementById('modal-auto-select').classList.add('hidden');
                                    feedback.className = 'hidden';
                                }, 1200);
                            }
                        }
                    } catch (err) {
                        alert('Erro ao realizar a seleção automática. Tente novamente.');
                    } finally {
                        btnAutoSelect.disabled = false;
                        btnAutoSelect.classList.remove('opacity-50');
                    }
                });
            });
        </script>
    @endpush

    {{-- Modal de Geração Automática de Avaliação --}}
    <div id="modal-auto-select" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-cloud-burst-950/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-magnolia-200 space-y-4 animate-scale-in">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-lucide-sparkles class="w-5 h-5 text-broom-600" />
                    <h3 class="text-base font-extrabold font-heading text-cloud-burst-950">Gerar Avaliação Automática</h3>
                </div>
                <button type="button" onclick="document.getElementById('modal-auto-select').classList.add('hidden')"
                        class="cursor-pointer text-cloud-burst-400 hover:text-cloud-burst-700 p-1">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>

            <p class="text-xs text-cloud-burst-700 leading-relaxed">
                O sistema selecionará automaticamente as questões do seu banco com base na área do conhecimento, turma, descritor pedagógico e quantidade desejada.
            </p>

            <div class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="auto_area" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                            Área do Conhecimento
                        </label>
                        <select id="auto_area" class="w-full px-3 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                            <option value="">Todas as áreas</option>
                            @foreach($areas as $area)
                                <option value="{{ $area }}">{{ $area }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="auto_grade" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                            Turma / Ano Escolar
                        </label>
                        <select id="auto_grade" class="w-full px-3 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                            <option value="">Todas as turmas</option>
                            @foreach($gradeLevels as $grade)
                                <option value="{{ $grade }}">{{ $grade }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="auto_focus" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                        Descritor / Foco Pedagógico / Habilidade (opcional)
                    </label>
                    <input id="auto_focus" type="text" placeholder="Ex.: Efeito do adjetivo, D1, Frações..."
                           class="w-full px-3 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="auto_quantity" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                            Quantidade de Questões (N) <span class="text-shocking-pink-600">*</span>
                        </label>
                        <input id="auto_quantity" type="number" min="1" max="100" value="10"
                               class="w-full px-3 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none font-bold">
                    </div>

                    <div>
                        <label for="auto_mode" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                            Critério de Seleção
                        </label>
                        <select id="auto_mode" class="w-full px-3 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                            <option value="random">Aleatório (Sorteio)</option>
                            <option value="latest">Mais Recentes</option>
                        </select>
                    </div>
                </div>

                <div id="auto-select-feedback" class="hidden p-3 rounded-xl text-xs font-medium"></div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-magnolia-100">
                <button type="button" onclick="document.getElementById('modal-auto-select').classList.add('hidden')"
                        class="cursor-pointer px-4 py-2 border border-magnolia-300 rounded-xl text-xs font-bold font-heading text-cloud-burst-800 hover:bg-magnolia-50 transition">
                    Cancelar
                </button>
                <button type="button" id="btn-run-auto-select"
                        class="cursor-pointer px-5 py-2 bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-xs rounded-xl shadow-2xs transition flex items-center gap-1.5">
                    <x-lucide-sparkles class="w-4 h-4 text-broom-400" />
                    <span>Selecionar Questões</span>
                </button>
            </div>
        </div>
    </div>
    </div>
</x-layouts.admin>
