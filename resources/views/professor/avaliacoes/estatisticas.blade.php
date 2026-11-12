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
                    <h1 class="text-2xl md:text-3xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                        {{ $assessment->title }}
                    </h1>
                    @if($assessment->description)
                        <p class="text-sm text-cloud-burst-700">{{ $assessment->description }}</p>
                    @endif
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
</x-layouts.admin>
