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

                        <button type="button" onclick="document.getElementById('modal-exportar-relatorio')?.classList.remove('hidden')"
                                class="cursor-pointer text-xs font-bold font-heading px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white shadow-sm transition flex items-center gap-2">
                            <x-lucide-download class="w-4 h-4 text-emerald-200" />
                            <span>Exportar Relatório</span>
                        </button>

                        <form action="{{ route('professor.salas.relatorio.email', $classroom) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="cursor-pointer text-xs font-bold font-heading px-4 py-2.5 rounded-xl border border-royal-purple-300 bg-royal-purple-50 hover:bg-royal-purple-100 text-royal-purple-900 transition flex items-center gap-2 shadow-2xs"
                                    title="Enviar relatório completo para {{ Auth::user()->email }}">
                                <x-lucide-mail class="w-4 h-4 text-royal-purple-800" />
                                <span>Enviar por E-mail</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Top Cards: Código e Configuração --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Big Code Card --}}
            <div class="bg-gradient-to-br from-royal-purple-950 to-royal-purple-900 rounded-2xl p-5 text-white text-center shadow-md flex flex-col justify-center items-center">
                <span class="text-[11px] font-bold uppercase tracking-wider text-royal-purple-200 mb-1">
                    Código de Entrada do Aluno
                </span>
                <span class="text-3xl md:text-4xl font-jetbrains font-mono font-black tracking-widest text-broom-400 my-1 select-all tabular-nums">
                    {{ $classroom->code }}
                </span>
                <p class="text-[10px] text-royal-purple-200 mt-1">
                    Alunos acessam <b>/sala</b> com este código.
                </p>
            </div>

            {{-- Status Card --}}
            <div class="bg-white rounded-2xl p-5 border border-royal-purple-200/60 shadow-xs flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-cloud-burst-900/60 uppercase tracking-wider block mb-2">Status da Sala</span>
                    <div class="flex items-center gap-2">
                        @php
                            $availability = $classroom->accessAvailability();
                        @endphp
                        @if($availability['available'])
                            <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-base font-bold text-emerald-700 font-heading">Aberta para Respostas</span>
                        @elseif($availability['reason'] === 'not_started')
                            <span class="w-3 h-3 rounded-full bg-indigo-500"></span>
                            <span class="text-base font-bold text-indigo-700 font-heading">Agendada</span>
                        @elseif($availability['reason'] === 'expired')
                            <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                            <span class="text-base font-bold text-rose-700 font-heading">Prazo Encerrado</span>
                        @else
                            <span class="w-3 h-3 rounded-full bg-gray-400"></span>
                            <span class="text-base font-bold text-gray-600 font-heading">Fechada</span>
                        @endif
                    </div>
                    @if($classroom->starts_at || $classroom->ends_at)
                        <div class="text-[11px] text-royal-purple-800 font-medium mt-2 flex items-center gap-1 bg-royal-purple-50 p-1.5 rounded-lg border border-royal-purple-100">
                            <x-lucide-calendar-clock class="w-3.5 h-3.5 text-royal-purple-700 shrink-0" />
                            <span>{{ $classroom->scheduleDescription() }}</span>
                        </div>
                    @endif
                </div>
                <div class="pt-2 border-t border-royal-purple-100 mt-2 text-[11px] text-cloud-burst-900/60">
                    Criada em {{ $classroom->created_at->format('d/m/Y H:i') }}
                </div>
            </div>

            {{-- Game Mode Card --}}
            <div class="bg-white rounded-2xl p-5 border border-royal-purple-200/60 shadow-xs flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-cloud-burst-900/60 uppercase tracking-wider block mb-2">Modo de Jogo</span>
                    @if($classroom->isCorujao())
                        <div class="flex items-center gap-2.5">
                            <picture class="shrink-0">
                                <source srcset="{{ asset('images/logo/CORUJAO_ICONE.avif') }}" type="image/avif">
                                <source srcset="{{ asset('images/logo/CORUJAO_ICONE.webp') }}" type="image/webp">
                                <img src="{{ asset('images/logo/CORUJAO_300_DPI.png') }}" alt="Modo Corujão" class="w-8 h-8 object-contain drop-shadow-xs">
                            </picture>
                            <div>
                                <span class="text-sm font-extrabold text-shocking-pink-900 font-heading block">Modo Corujão</span>
                                <span class="text-[10px] font-bold text-shocking-pink-700">Hardcore · Cronômetro Rígido</span>
                            </div>
                        </div>
                        <p class="text-[11px] text-cloud-burst-700 mt-2 leading-relaxed">
                            Avanço automático ao zerar o timer por questão.
                        </p>
                    @else
                        <div class="flex items-center gap-2.5">
                            <picture class="shrink-0">
                                <source srcset="{{ asset('images/logo/corujinha-icone.avif') }}" type="image/avif">
                                <source srcset="{{ asset('images/logo/corujinha-icone.webp') }}" type="image/webp">
                                <img src="{{ asset('images/logo/corujinha-icone.png') }}" alt="Modo Corujinha" class="w-8 h-8 object-contain drop-shadow-xs">
                            </picture>
                            <div>
                                <span class="text-sm font-extrabold text-royal-purple-950 font-heading block">Modo Corujinha</span>
                                <span class="text-[10px] font-bold text-broom-950 bg-broom-100 px-1.5 py-0.5 rounded border border-broom-300">Padrão · Fácil</span>
                            </div>
                        </div>
                        <p class="text-[11px] text-cloud-burst-700 mt-2 leading-relaxed">
                            Cronômetro orientativo e ritmo individual.
                        </p>
                    @endif
                </div>
                <div class="pt-2 border-t border-royal-purple-100 mt-2 text-[11px]">
                    <a href="{{ route('professor.salas.edit', $classroom) }}" class="text-royal-purple-800 hover:underline font-bold inline-flex items-center gap-1">
                        <x-lucide-settings-2 class="w-3 h-3" />
                        <span>Alterar Modo</span>
                    </a>
                </div>
            </div>

            {{-- Review Mode Card --}}
            <div class="bg-white rounded-2xl p-5 border border-royal-purple-200/60 shadow-xs flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-cloud-burst-900/60 uppercase tracking-wider block mb-2">Revisão Coletiva</span>
                    @if($classroom->collective_review_enabled)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            <x-lucide-check class="w-3.5 h-3.5" />
                            <span>Ativa (até {{ $classroom->effectiveMaxReviewChanges() }} alt.)</span>
                        </span>
                        <p class="text-[11px] text-cloud-burst-900/70 mt-2">
                            Alunos podem reavaliar alternativas antes do resultado.
                        </p>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700 border border-gray-300">
                            <x-lucide-x class="w-3.5 h-3.5 text-gray-500" />
                            <span>Desativada</span>
                        </span>
                        <p class="text-[11px] text-cloud-burst-900/60 mt-2">
                            Direto ao resultado e gabarito final.
                        </p>
                    @endif
                </div>
                <div class="pt-2 border-t border-royal-purple-100 mt-2 text-[11px]">
                    <a href="{{ route('professor.salas.edit', $classroom) }}" class="text-royal-purple-800 hover:underline font-bold inline-flex items-center gap-1">
                        <x-lucide-settings-2 class="w-3 h-3" />
                        <span>Configurar</span>
                    </a>
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
                            <th class="py-3.5 px-6 text-center">Trocas de Aba</th>
                            <th class="py-3.5 px-6 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="attempts-table-body" class="divide-y divide-royal-purple-100/60">
                        <tr id="no-filter-results-row" class="hidden">
                            <td colspan="8" class="py-8 text-center text-cloud-burst-900/60">
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
                                    @if($att->status === 'waiting_authorization' || !$att->is_device_authorized)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300" title="Dispositivo compartilhado aguardando autorização do professor">
                                            <x-lucide-shield-alert class="w-3.5 h-3.5 text-amber-700" />
                                            <span>Aguardando autorização</span>
                                        </span>
                                    @elseif($att->status === 'reviewed')
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
                                    {{ $att->score ?? 0 }} / {{ $totalQuestions }}
                                </td>
                                <td id="tab-switches-cell-{{ $att->uuid }}" class="py-4 px-6 text-center">
                                    @if(($att->tab_switches_count ?? 0) > 0)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-amber-100 text-amber-900 border border-amber-300" title="{{ $att->tab_switches_count }} saída(s) de aba detectada(s)">
                                            <x-lucide-alert-triangle class="w-3.5 h-3.5 text-amber-700" />
                                            <span>{{ $att->tab_switches_count }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-gray-100 text-gray-600 border border-gray-200" title="Nenhuma saída de aba">
                                            <x-lucide-check class="w-3 h-3 text-emerald-600" />
                                            <span>0</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        @if($att->status === 'waiting_authorization' || !$att->is_device_authorized)
                                            <form action="{{ route('professor.salas.tentativa.authorize_device', [$classroom, $att]) }}" method="POST" class="inline" id="auth-form-{{ $att->uuid }}">
                                                @csrf
                                                <button type="submit" class="cursor-pointer inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold font-heading bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-2xs" title="Autorizar este aluno no dispositivo compartilhado">
                                                    <x-lucide-check-circle class="w-3.5 h-3.5" />
                                                    <span>Autorizar</span>
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('professor.salas.tentativa', [$classroom, $att]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-900 hover:bg-royal-purple-200 transition shadow-2xs">
                                            <x-lucide-eye class="w-3.5 h-3.5" />
                                            <span>Ver Respostas</span>
                                        </a>
                                        <form action="{{ route('professor.salas.tentativa.destroy', [$classroom, $att]) }}" method="POST" class="inline"
                                              data-confirm="Tem certeza de que deseja deletar a resposta de {{ $att->student_name }}? Todas as respostas deste estudante para esta avaliação serão apagadas."
                                              data-confirm-title="Deletar Resposta do Estudante"
                                              data-confirm-ok="Deletar Resposta"
                                              data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="cursor-pointer p-1.5 rounded-xl text-cloud-burst-500 hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition" title="Deletar Resposta do Estudante">
                                                <x-lucide-trash-2 class="w-4 h-4" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-attempts-row">
                                <td colspan="8" class="py-8 text-center text-cloud-burst-900/60">
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

    {{-- Modal de Exportação de Relatórios da Sala --}}
    <div id="modal-exportar-relatorio" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-cloud-burst-950/60 backdrop-blur-xs transition-opacity" onclick="document.getElementById('modal-exportar-relatorio')?.classList.add('hidden')"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white rounded-3xl p-6 md:p-8 shadow-2xl border border-magnolia-200 space-y-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <x-lucide-file-text class="w-3.5 h-3.5" />
                            <span>Exportar Relatório</span>
                        </span>
                        <h3 class="text-xl font-extrabold font-heading text-cloud-burst-950">
                            Relatórios da Sala {{ $classroom->code }}
                        </h3>
                        <p class="text-xs text-cloud-burst-700">Baixe os dados consolidados da turma ou receba tudo diretamente em seu e-mail.</p>
                    </div>
                    <button type="button" onclick="document.getElementById('modal-exportar-relatorio')?.classList.add('hidden')"
                            class="cursor-pointer p-1.5 text-cloud-burst-400 hover:text-cloud-burst-900 rounded-lg hover:bg-gray-100 transition">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                {{-- Seção de Downloads Imediatos --}}
                <div class="space-y-2.5">
                    <span class="block text-xs font-bold font-heading uppercase text-cloud-burst-800 tracking-wider">
                        Opções de Download Imediato:
                    </span>

                    <a href="{{ route('professor.salas.relatorio.download', ['sala' => $classroom, 'type' => 'zip']) }}"
                       class="flex items-center justify-between p-3.5 rounded-2xl border-2 border-emerald-600 bg-emerald-50/60 hover:bg-emerald-100/60 transition group shadow-2xs">
                        <div class="flex items-center gap-3">
                            <span class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-xs">
                                <x-lucide-archive class="w-5 h-5" />
                            </span>
                            <div>
                                <span class="text-xs font-bold font-heading text-emerald-950 block">Pacote Completo (.ZIP)</span>
                                <span class="text-[11px] text-emerald-800">Contém o PDF executivo, a planilha XLSX e os cards PNG</span>
                            </div>
                        </div>
                        <x-lucide-download class="w-4 h-4 text-emerald-700 group-hover:translate-y-0.5 transition" />
                    </a>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <a href="{{ route('professor.salas.relatorio.download', ['sala' => $classroom, 'type' => 'pdf']) }}"
                           class="flex flex-col items-center justify-center p-3 rounded-xl border border-magnolia-300 hover:border-royal-purple-600 hover:bg-royal-purple-50/50 transition text-center group">
                            <x-lucide-file-text class="w-5 h-5 text-shocking-pink-600 mb-1" />
                            <span class="text-xs font-bold font-heading text-cloud-burst-950">Relatório PDF</span>
                            <span class="text-[10px] text-cloud-burst-600">Visual executivo</span>
                        </a>

                        <a href="{{ route('professor.salas.relatorio.download', ['sala' => $classroom, 'type' => 'xlsx']) }}"
                           class="flex flex-col items-center justify-center p-3 rounded-xl border border-magnolia-300 hover:border-emerald-600 hover:bg-emerald-50/50 transition text-center group">
                            <x-lucide-file-spreadsheet class="w-5 h-5 text-emerald-600 mb-1" />
                            <span class="text-xs font-bold font-heading text-cloud-burst-950">Planilha XLSX</span>
                            <span class="text-[10px] text-cloud-burst-600">Respostas e tempos</span>
                        </a>

                        <a href="{{ route('professor.salas.relatorio.download', ['sala' => $classroom, 'type' => 'images']) }}"
                           class="flex flex-col items-center justify-center p-3 rounded-xl border border-magnolia-300 hover:border-sky-blue-600 hover:bg-sky-blue-50/50 transition text-center group">
                            <x-lucide-image class="w-5 h-5 text-sky-blue-600 mb-1" />
                            <span class="text-xs font-bold font-heading text-cloud-burst-950">Cards PNG</span>
                            <span class="text-[10px] text-cloud-burst-600">Imagens dos alunos</span>
                        </a>
                    </div>
                </div>

                {{-- Seção de Envio por E-mail --}}
                <div class="pt-4 border-t border-magnolia-200">
                    <form action="{{ route('professor.salas.relatorio.email', $classroom) }}" method="POST" class="space-y-2.5">
                        @csrf
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider flex items-center gap-1.5">
                                <x-lucide-mail class="w-3.5 h-3.5 text-royal-purple-800" />
                                <span>Receber Relatório por E-mail</span>
                            </span>
                            <span class="text-[11px] text-cloud-burst-600 font-medium">
                                Para: <strong class="text-cloud-burst-950">{{ Auth::user()->email }}</strong>
                            </span>
                        </div>
                        <p class="text-[11px] text-cloud-burst-700 leading-relaxed">
                            Os arquivos <code>.pdf</code> e <code>.xlsx</code> serão anexados diretamente fora do ZIP, e os cards individuais de imagens irão anexados em arquivo <code>.zip</code>.
                        </p>
                        <button type="submit" class="cursor-pointer w-full py-2.5 px-4 bg-royal-purple-800 hover:bg-royal-purple-900 text-white rounded-xl text-xs font-bold font-heading shadow-xs transition flex items-center justify-center gap-2">
                            <x-lucide-send class="w-3.5 h-3.5" />
                            <span>Enviar por E-mail ({{ Auth::user()->email }})</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.__CLASSROOM_MONITOR__ = {
                classroomId: @json($classroom->uuid),
                totalAttempts: {{ $attempts->total() }},
                totalQuestions: {{ $totalQuestions }},
                attemptUrlPattern: "{{ route('professor.salas.tentativa', [$classroom, '__ATTEMPT_ID__']) }}",
                authorizeDeviceUrlPattern: "{{ route('professor.salas.tentativa.authorize_device', [$classroom, '__ATTEMPT_ID__']) }}",
                destroyAttemptUrlPattern: "{{ route('professor.salas.tentativa.destroy', [$classroom, '__ATTEMPT_ID__']) }}",
                csrfToken: "{{ csrf_token() }}"
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
