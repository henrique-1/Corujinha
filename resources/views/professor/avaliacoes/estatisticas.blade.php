<x-layouts.admin title="Gráficos da Avaliação · Corujinha">
    <div class="space-y-8">
        {{-- Navegação e Cabeçalho --}}
        <div class="space-y-4">
            <a href="{{ route('professor.avaliacoes.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-royal-purple-800 hover:text-royal-purple-900 hover:underline transition">
                <x-lucide-arrow-left class="w-4 h-4" />
                <span>Voltar para Minhas Avaliações</span>
            </a>

            <div class="bg-white rounded-3xl p-6 md:p-8 border border-magnolia-200 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-800 border border-royal-purple-200">
                        <x-lucide-bar-chart-3 class="w-3.5 h-3.5" />
                        <span>Desempenho Consolidado</span>
                    </span>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl md:text-3xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                            {{ $assessment->title }}
                        </h1>
                        @if($assessment->academic_period)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold font-heading bg-sky-blue-100 text-sky-blue-900 border border-sky-blue-200">
                                {{ $assessment->academic_period }}
                            </span>
                        @endif
                    </div>
                    @if($assessment->description)
                        <p class="text-sm text-cloud-burst-700">{{ $assessment->description }}</p>
                    @endif
                    <div class="pt-1 flex items-center gap-2 flex-wrap">
                        <button type="button" onclick="document.getElementById('modal-exportar-avaliacao')?.classList.remove('hidden')"
                                class="cursor-pointer text-xs font-bold font-heading px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white shadow-xs transition inline-flex items-center gap-2">
                            <x-lucide-download class="w-4 h-4 text-emerald-200" />
                            <span>Exportar Relatório Consolidado</span>
                        </button>

                        <form action="{{ route('professor.avaliacoes.relatorio.email', $assessment) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="cursor-pointer text-xs font-bold font-heading px-4 py-2.5 rounded-xl border border-royal-purple-300 bg-royal-purple-50 hover:bg-royal-purple-100 text-royal-purple-900 shadow-2xs transition inline-flex items-center gap-2"
                                    title="Enviar relatório consolidado para {{ Auth::user()->email }}">
                                <x-lucide-mail class="w-4 h-4 text-royal-purple-800" />
                                <span>Enviar por E-mail</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3 shrink-0">
                    <div class="bg-magnolia-50 border border-magnolia-200 rounded-2xl px-4 py-3 text-center">
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-cloud-burst-600">Salas</span>
                        <span class="block text-2xl font-extrabold font-heading text-cloud-burst-950">{{ $totalApplications }}</span>
                    </div>
                    <div class="bg-royal-purple-50 border border-royal-purple-200 rounded-2xl px-4 py-3 text-center">
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-royal-purple-800">Participações</span>
                        <span class="block text-2xl font-extrabold font-heading text-royal-purple-900">{{ $totalAttempts }}</span>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl px-4 py-3 text-center">
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-emerald-800">Questões</span>
                        <span class="block text-2xl font-extrabold font-heading text-emerald-900">{{ $assessment->questions->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gráfico 1: Salas com Mais Acertos (Barras) --}}
        <section class="bg-white rounded-3xl p-6 md:p-8 border border-magnolia-200 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-magnolia-100 pb-4">
                <div>
                    <h2 class="text-lg font-extrabold font-heading text-cloud-burst-950 flex items-center gap-2">
                        <x-lucide-award class="w-5 h-5 text-royal-purple-800" />
                        <span>Ranking de Acertos por Sala de Aula</span>
                    </h2>
                    <p class="text-xs text-cloud-burst-600 mt-0.5">
                        Comparativo do total de acertos acumulados e taxa de acerto entre as turmas que aplicaram esta avaliação
                    </p>
                </div>
            </div>

            @php
                $maxClassroomCorrect = max(1, (int) $classroomStats->max('total_correct'));
            @endphp

            @if($classroomStats->isEmpty())
                <p class="text-sm text-cloud-burst-600 py-6 text-center">
                    Nenhuma sala de aula foi criada para esta avaliação até o momento.
                </p>
            @else
                <div class="space-y-4">
                    @foreach($classroomStats as $idx => $room)
                        @php
                            $barWidth = round(($room['total_correct'] / $maxClassroomCorrect) * 100, 1);
                        @endphp
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full font-extrabold font-heading {{ $idx === 0 && $room['total_correct'] > 0 ? 'bg-broom-300 text-broom-950' : 'bg-magnolia-100 text-cloud-burst-800' }}">
                                        {{ $idx + 1 }}º
                                    </span>
                                    <a href="{{ route('professor.salas.show', $room['id']) }}" class="font-bold font-heading text-cloud-burst-950 hover:text-royal-purple-800 hover:underline">
                                        {{ $room['name'] }}
                                    </a>
                                    <span class="font-mono text-[11px] px-2 py-0.5 rounded bg-magnolia-100 text-cloud-burst-700">{{ $room['code'] }}</span>
                                </div>
                                <div class="flex items-center gap-3 font-bold font-heading text-cloud-burst-800">
                                    <span>{{ $room['total_correct'] }} acerto(s)</span>
                                    <span class="text-royal-purple-800">{{ $room['accuracy_rate'] }}% de aproveitamento</span>
                                    <span class="text-cloud-burst-500 font-normal">({{ $room['attempts_count'] }} aluno(s))</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 text-[11px] text-cloud-burst-600 pl-8">
                                <span class="inline-flex items-center gap-1">
                                    <x-lucide-calendar class="w-3.5 h-3.5 text-royal-purple-700" />
                                    <span>Criada em: <strong class="text-cloud-burst-900">{{ $room['created_at'] }}</strong></span>
                                </span>
                                <span class="text-magnolia-300">•</span>
                                <span class="inline-flex items-center gap-1">
                                    <x-lucide-clock class="w-3.5 h-3.5 text-royal-purple-700" />
                                    <span>Aplicação: <strong class="text-cloud-burst-900">{{ $room['application_date'] }}</strong></span>
                                </span>
                            </div>
                            <div class="w-full h-5 rounded-full bg-magnolia-100 overflow-hidden p-0.5">
                                <div class="h-full rounded-full bg-linear-to-r from-royal-purple-700 to-royal-purple-500 transition-all duration-500"
                                     style="width: {{ max($room['total_correct'] > 0 ? 4 : 0, $barWidth) }}%"
                                     role="progressbar"
                                     aria-valuenow="{{ $room['total_correct'] }}"
                                     aria-valuemin="0"
                                     aria-valuemax="{{ $maxClassroomCorrect }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Gráficos 2 e 3: Questões Mais Acertadas vs Mais Erradas (Barras) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Questões Mais Acertadas --}}
            <section class="bg-white rounded-3xl p-6 md:p-8 border border-emerald-200 shadow-xs space-y-5">
                <div class="border-b border-emerald-100 pb-4">
                    <h2 class="text-lg font-extrabold font-heading text-cloud-burst-950 flex items-center gap-2">
                        <x-lucide-trending-up class="w-5 h-5 text-emerald-600" />
                        <span>Questões Mais Acertadas</span>
                    </h2>
                    <p class="text-xs text-cloud-burst-600 mt-0.5">
                        Total de acertos por questão considerando todas as salas
                    </p>
                </div>

                @php
                    $maxQuestionCorrect = max(1, (int) $mostCorrectQuestions->max('correct_count'));
                @endphp

                @if($mostCorrectQuestions->isEmpty())
                    <p class="text-sm text-cloud-burst-600 py-6 text-center">Nenhuma questão cadastrada.</p>
                @else
                    <div class="space-y-4">
                        @foreach($mostCorrectQuestions as $qStat)
                            @php
                                $barWidth = round(($qStat['correct_count'] / $maxQuestionCorrect) * 100, 1);
                            @endphp
                            <div class="space-y-1.5">
                                <div class="flex items-start justify-between gap-3 text-xs">
                                    <div class="min-w-0">
                                        <span class="font-extrabold font-heading text-emerald-800">Q{{ $qStat['number'] }}</span>
                                        <span class="text-cloud-burst-700 ml-1">{{ $qStat['short_prompt'] }}</span>
                                    </div>
                                    <span class="shrink-0 font-bold font-heading text-emerald-800 whitespace-nowrap">
                                        {{ $qStat['correct_count'] }} ({{ $qStat['accuracy_rate'] }}%)
                                    </span>
                                </div>
                                <div class="w-full h-4 rounded-full bg-emerald-50 border border-emerald-100 overflow-hidden p-0.5">
                                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                         style="width: {{ max($qStat['correct_count'] > 0 ? 4 : 0, $barWidth) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Questões Mais Erradas --}}
            <section class="bg-white rounded-3xl p-6 md:p-8 border border-rose-200 shadow-xs space-y-5">
                <div class="border-b border-rose-100 pb-4">
                    <h2 class="text-lg font-extrabold font-heading text-cloud-burst-950 flex items-center gap-2">
                        <x-lucide-alert-circle class="w-5 h-5 text-rose-600" />
                        <span>Questões Mais Erradas</span>
                    </h2>
                    <p class="text-xs text-cloud-burst-600 mt-0.5">
                        Total de erros por questão considerando todas as salas
                    </p>
                </div>

                @php
                    $maxQuestionWrong = max(1, (int) $mostWrongQuestions->max('wrong_count'));
                @endphp

                @if($mostWrongQuestions->isEmpty())
                    <p class="text-sm text-cloud-burst-600 py-6 text-center">Nenhuma questão cadastrada.</p>
                @else
                    <div class="space-y-4">
                        @foreach($mostWrongQuestions as $qStat)
                            @php
                                $barWidth = round(($qStat['wrong_count'] / $maxQuestionWrong) * 100, 1);
                            @endphp
                            <div class="space-y-1.5">
                                <div class="flex items-start justify-between gap-3 text-xs">
                                    <div class="min-w-0">
                                        <span class="font-extrabold font-heading text-rose-800">Q{{ $qStat['number'] }}</span>
                                        <span class="text-cloud-burst-700 ml-1">{{ $qStat['short_prompt'] }}</span>
                                    </div>
                                    <span class="shrink-0 font-bold font-heading text-rose-800 whitespace-nowrap">
                                        {{ $qStat['wrong_count'] }} ({{ $qStat['error_rate'] }}%)
                                    </span>
                                </div>
                                <div class="w-full h-4 rounded-full bg-rose-50 border border-rose-100 overflow-hidden p-0.5">
                                    <div class="h-full rounded-full bg-rose-500 transition-all duration-500"
                                         style="width: {{ max($qStat['wrong_count'] > 0 ? 4 : 0, $barWidth) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
    {{-- Modal de Exportação do Relatório Consolidado da Avaliação --}}
    <div id="modal-exportar-avaliacao" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-cloud-burst-950/60 backdrop-blur-xs transition-opacity" onclick="document.getElementById('modal-exportar-avaliacao')?.classList.add('hidden')"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white rounded-3xl p-6 md:p-8 shadow-2xl border border-magnolia-200 space-y-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <x-lucide-bar-chart-3 class="w-3.5 h-3.5" />
                            <span>Exportar Relatório Consolidado</span>
                        </span>
                        <h3 class="text-xl font-extrabold font-heading text-cloud-burst-950">
                            {{ $assessment->title }}
                        </h3>
                        <p class="text-xs text-cloud-burst-700">Baixe os dados consolidados somando todas as turmas que realizaram esta avaliação.</p>
                    </div>
                    <button type="button" onclick="document.getElementById('modal-exportar-avaliacao')?.classList.add('hidden')"
                            class="cursor-pointer p-1.5 text-cloud-burst-400 hover:text-cloud-burst-900 rounded-lg hover:bg-gray-100 transition">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                {{-- Downloads Imediatos --}}
                <div class="space-y-2.5">
                    <span class="block text-xs font-bold font-heading uppercase text-cloud-burst-800 tracking-wider">
                        Opções de Download:
                    </span>

                    <a href="{{ route('professor.avaliacoes.relatorio.download', ['avaliacao' => $assessment, 'type' => 'zip']) }}"
                       class="flex items-center justify-between p-3.5 rounded-2xl border-2 border-emerald-600 bg-emerald-50/60 hover:bg-emerald-100/60 transition group shadow-2xs">
                        <div class="flex items-center gap-3">
                            <span class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-xs">
                                <x-lucide-archive class="w-5 h-5" />
                            </span>
                            <div>
                                <span class="text-xs font-bold font-heading text-emerald-950 block">Pacote Consolidado (.ZIP)</span>
                                <span class="text-[11px] text-emerald-800">Contém o PDF executivo e a planilha XLSX de todas as salas</span>
                            </div>
                        </div>
                        <x-lucide-download class="w-4 h-4 text-emerald-700 group-hover:translate-y-0.5 transition" />
                    </a>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <a href="{{ route('professor.avaliacoes.relatorio.download', ['avaliacao' => $assessment, 'type' => 'pdf']) }}"
                           class="flex flex-col items-center justify-center p-3 rounded-xl border border-magnolia-300 hover:border-royal-purple-600 hover:bg-royal-purple-50/50 transition text-center group">
                            <x-lucide-file-text class="w-5 h-5 text-shocking-pink-600 mb-1" />
                            <span class="text-xs font-bold font-heading text-cloud-burst-950">Relatório PDF</span>
                            <span class="text-[10px] text-cloud-burst-600">Visual executivo multi-salas</span>
                        </a>

                        <a href="{{ route('professor.avaliacoes.relatorio.download', ['avaliacao' => $assessment, 'type' => 'xlsx']) }}"
                           class="flex flex-col items-center justify-center p-3 rounded-xl border border-magnolia-300 hover:border-emerald-600 hover:bg-emerald-50/50 transition text-center group">
                            <x-lucide-file-spreadsheet class="w-5 h-5 text-emerald-600 mb-1" />
                            <span class="text-xs font-bold font-heading text-cloud-burst-950">Planilha XLSX</span>
                            <span class="text-[10px] text-cloud-burst-600">Matriz geral de notas</span>
                        </a>
                    </div>
                </div>

                {{-- Envio por E-mail --}}
                <div class="pt-4 border-t border-magnolia-200">
                    <form action="{{ route('professor.avaliacoes.relatorio.email', $assessment) }}" method="POST" class="space-y-2.5">
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
                            Os arquivos <code>.pdf</code> e <code>.xlsx</code> consolidados serão enviados diretamente para o seu e-mail cadastrado.
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
</x-layouts.admin>
