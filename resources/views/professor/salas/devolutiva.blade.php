<x-layouts.admin title="Devolutiva da Sala · Corujinha">
    <div class="space-y-8">
        {{-- Navegação e Cabeçalho --}}
        <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-4 text-xs font-bold">
                <a href="{{ route('professor.salas.show', $classroom) }}" class="inline-flex items-center gap-1.5 text-royal-purple-800 hover:text-royal-purple-900 hover:underline transition">
                    <x-lucide-arrow-left class="w-4 h-4" />
                    <span>Voltar ao Painel da Sala</span>
                </a>
                <a href="{{ route('professor.salas.index') }}" class="inline-flex items-center gap-1.5 text-cloud-burst-600 hover:text-cloud-burst-900 hover:underline transition">
                    <x-lucide-school class="w-4 h-4" />
                    <span>Minhas Salas</span>
                </a>
            </div>

            <div class="bg-white rounded-3xl p-6 md:p-8 border border-magnolia-200 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-800 border border-royal-purple-200">
                            <x-lucide-presentation class="w-3.5 h-3.5" />
                            <span>Modo Devolutiva</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-gray-100 text-gray-800 border border-gray-300">
                            <x-lucide-lock class="w-3.5 h-3.5 text-gray-600" />
                            <span>Sala Encerrada · Código {{ $classroom->code }}</span>
                        </span>
                        @if($classroom->collective_review_enabled)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-sky-blue-100 text-sky-blue-900 border border-sky-blue-300">
                                <x-lucide-refresh-cw class="w-3.5 h-3.5" />
                                <span>Revisão Coletiva ({{ $classroom->effectiveMaxReviewChanges() }} alt.)</span>
                            </span>
                        @endif
                    </div>

                    <h1 class="text-2xl md:text-3xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                        Devolutiva · {{ $classroom->name }}
                    </h1>
                    <p class="text-sm text-cloud-burst-700">
                        Avaliação: <span class="font-bold text-cloud-burst-950">{{ $classroom->assessment->title }}</span>
                    </p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 shrink-0">
                    <div class="bg-magnolia-50 border border-magnolia-200 rounded-2xl px-4 py-3 text-center">
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-cloud-burst-600">Questões</span>
                        <span class="block text-2xl font-extrabold font-heading text-cloud-burst-950">{{ $questionReports->count() }}</span>
                    </div>
                    <div class="bg-royal-purple-50 border border-royal-purple-200 rounded-2xl px-4 py-3 text-center">
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-royal-purple-800">Participantes</span>
                        <span class="block text-2xl font-extrabold font-heading text-royal-purple-900">{{ $attempts->count() }}</span>
                    </div>
                    @php
                        $avgAccuracy = $questionReports->isNotEmpty()
                            ? round($questionReports->avg('accuracyRate'), 1)
                            : 0;
                    @endphp
                    <div class="col-span-2 sm:col-span-1 bg-emerald-50 border border-emerald-200 rounded-2xl px-4 py-3 text-center">
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-emerald-800">Média de Acerto</span>
                        <span class="block text-2xl font-extrabold font-heading text-emerald-900">{{ $avgAccuracy }}%</span>
                    </div>
                </div>
            </div>

            {{-- Navegação rápida por questão --}}
            @if($questionReports->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2 bg-white p-4 rounded-2xl border border-magnolia-200 shadow-xs">
                    <span class="text-xs font-bold font-heading text-cloud-burst-700 uppercase tracking-wider mr-1">Ir para:</span>
                    @foreach($questionReports as $idx => $report)
                        <a href="#questao-{{ $report['question']->uuid }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold font-heading bg-magnolia-50 hover:bg-royal-purple-50 text-cloud-burst-900 hover:text-royal-purple-900 border border-magnolia-200 hover:border-royal-purple-300 transition">
                            <span>Q{{ $idx + 1 }}</span>
                            <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[11px]">
                                {{ $report['correctCount'] }}/{{ $report['totalAttempts'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Lista Completa das Questões com Comentários, Marcações e Acertos/Erros --}}
        <div class="space-y-8">
            @forelse($questionReports as $idx => $report)
                @php
                    $q = $report['question'];
                    $expectedMarkers = $report['expectedMarkers'];
                    $correctLetter = trim(strtoupper((string) $q->correct_option));
                @endphp

                <article id="questao-{{ $q->uuid }}" class="bg-white rounded-3xl p-6 md:p-8 border border-magnolia-200 shadow-xs space-y-6 scroll-mt-6">
                    {{-- Cabeçalho da Questão e Totais de Acerto --}}
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-magnolia-200 pb-4">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-royal-purple-800 text-white font-extrabold font-heading text-sm">
                                {{ $idx + 1 }}
                            </span>
                            <h2 class="text-lg font-extrabold font-heading text-cloud-burst-950 m-0">Questão {{ $idx + 1 }}</h2>
                            @if($q->focus)
                                <span class="text-xs font-bold font-heading px-2.5 py-0.5 rounded-full bg-royal-purple-100 text-royal-purple-800 border border-royal-purple-200">
                                    {{ $q->focus }}
                                </span>
                            @endif
                            <span class="text-xs font-bold font-heading px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                                Gabarito: {{ $correctLetter }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-300">
                                <x-lucide-check-circle class="w-3.5 h-3.5 text-emerald-700" />
                                <span>{{ $report['correctCount'] }} acerto(s) ({{ $report['accuracyRate'] }}%)</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-rose-100 text-rose-800 border border-rose-300">
                                <x-lucide-x-circle class="w-3.5 h-3.5 text-rose-700" />
                                <span>{{ $report['wrongCount'] }} erro(s)</span>
                            </span>
                            @if($report['unansweredCount'] > 0)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-gray-100 text-gray-700 border border-gray-300">
                                    <span>{{ $report['unansweredCount'] }} sem resposta</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Suporte: Texto com Marcações Esperadas e/ou Imagem --}}
                    @if($q->hasTextSupport())
                        <div class="bg-magnolia-50/70 p-5 rounded-2xl border border-magnolia-200 space-y-3">
                            <span class="inline-block text-[11px] font-bold font-heading text-cloud-burst-600 uppercase tracking-wider">
                                Texto de Apoio
                            </span>
                            @if($q->support_title)
                                <p class="font-bold font-heading text-cloud-burst-950 text-base mb-1">
                                    {!! \App\Services\TextHighlightService::applyHighlights($q->support_title, $expectedMarkers, 'title') !!}
                                </p>
                            @endif
                            @if($q->support_text)
                                <div class="rich-text-content text-sm text-cloud-burst-950 leading-relaxed">
                                    {!! \App\Services\TextHighlightService::applyHighlights($q->support_text, $expectedMarkers, 'support') !!}
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($q->hasImageSupport())
                        <div class="text-center my-3">
                            <img src="{{ $q->image_url }}" alt="Imagem de apoio da questão {{ $idx + 1 }}" class="max-h-80 mx-auto rounded-2xl border border-magnolia-200">
                        </div>
                    @endif

                    {{-- Enunciado / Comando da Questão com Marcações Esperadas --}}
                    <div>
                        <p class="text-xs font-bold font-heading text-royal-purple-800 uppercase tracking-wider mb-1">Comando da Questão</p>
                        <div class="text-base font-bold font-heading text-cloud-burst-950 leading-relaxed">
                            {!! \App\Services\TextHighlightService::applyHighlights($q->prompt, $expectedMarkers, 'prompt') !!}
                        </div>
                    </div>

                    {{-- Resumo das Marcações Esperadas --}}
                    @if(!empty($expectedMarkers))
                        <div class="p-4 rounded-2xl bg-royal-purple-50/60 border border-royal-purple-200 text-xs space-y-2">
                            <span class="font-bold font-heading text-cloud-burst-950 uppercase tracking-wider flex items-center gap-1.5">
                                <x-lucide-highlighter class="w-3.5 h-3.5 text-royal-purple-800" />
                                <span>Marcações Pedagógicas Esperadas (Comando e Pistas):</span>
                            </span>
                            <div class="flex flex-wrap gap-2">
                                @foreach($expectedMarkers as $m)
                                    @php
                                        $isCmd = (($m['kind'] ?? '') === 'command');
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold font-heading {{ $isCmd ? 'bg-broom-200 text-broom-950 border border-broom-300' : 'bg-sky-blue-200 text-sky-blue-950 border border-sky-blue-300' }}">
                                        <span class="w-2 h-2 rounded-full {{ $isCmd ? 'bg-broom-600' : 'bg-sky-blue-600' }}"></span>
                                        <span>{{ $isCmd ? 'Comando' : 'Pista' }}: "{{ $m['text'] ?? '' }}"</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Devolutivas de Comando e Pista --}}
                    @if($q->command_feedback || $q->clue_feedback || $q->expected_commands || $q->expected_clues)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if($q->command_feedback || $q->expected_commands)
                                <div class="bg-broom-50 p-4 rounded-2xl border border-broom-300 text-xs text-broom-950 space-y-1.5">
                                    <p class="font-bold font-heading flex items-center gap-1.5">
                                        <x-lucide-highlighter class="w-4 h-4 text-broom-800" />
                                        <span>Devolutiva do Comando</span>
                                    </p>
                                    @if($q->expected_commands)
                                        <p class="font-mono text-[11px] text-broom-900">Trecho esperado: "{{ $q->expected_commands }}"</p>
                                    @endif
                                    @if($q->command_feedback)
                                        <p class="leading-relaxed">{{ $q->command_feedback }}</p>
                                    @endif
                                </div>
                            @endif

                            @if($q->clue_feedback || $q->expected_clues)
                                <div class="bg-sky-blue-50 p-4 rounded-2xl border border-sky-blue-300 text-xs text-sky-blue-950 space-y-1.5">
                                    <p class="font-bold font-heading flex items-center gap-1.5">
                                        <x-lucide-search class="w-4 h-4 text-sky-blue-800" />
                                        <span>Devolutiva das Pistas</span>
                                    </p>
                                    @if($q->expected_clues)
                                        <p class="font-mono text-[11px] text-sky-blue-900">Pistas esperadas: "{{ $q->expected_clues }}"</p>
                                    @endif
                                    @if($q->clue_feedback)
                                        <p class="leading-relaxed">{{ $q->clue_feedback }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Alternativas com Devolutivas Pedagógicas e Contagem --}}
                    <div class="space-y-3">
                        <p class="text-xs font-bold font-heading text-cloud-burst-800 uppercase tracking-wider">
                            Alternativas e Comentários Pedagógicos
                        </p>
                        <div class="space-y-2.5">
                            @foreach($q->options as $opt)
                                @php
                                    $optLetter = trim(strtoupper($opt->letter));
                                    $isCorrectOpt = ($optLetter === $correctLetter);
                                    $optVotes = $report['optionCounts'][$optLetter] ?? 0;
                                @endphp
                                <div class="p-4 rounded-2xl border {{ $isCorrectOpt ? 'border-emerald-400 bg-emerald-50/60' : 'border-magnolia-200 bg-magnolia-50/30' }}">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="flex items-start gap-2.5">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-extrabold font-heading shrink-0 mt-0.5 {{ $isCorrectOpt ? 'bg-emerald-600 text-white' : 'bg-magnolia-200 text-cloud-burst-900' }}">
                                                {{ $optLetter }}
                                            </span>
                                            <span class="text-sm font-medium text-cloud-burst-950">{{ $opt->text }}</span>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="text-[11px] font-bold font-heading px-2 py-0.5 rounded-full {{ $isCorrectOpt ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-cloud-burst-700' }}">
                                                {{ $optVotes }} escolha(s)
                                            </span>
                                            @if($isCorrectOpt)
                                                <span class="text-[11px] font-bold font-heading px-2 py-0.5 rounded-full bg-emerald-600 text-white">
                                                    Correta
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($opt->feedback)
                                        <p class="text-xs text-cloud-burst-700 mt-2 pl-8 leading-relaxed">
                                            <strong class="font-heading text-cloud-burst-900">Comentário:</strong> {{ $opt->feedback }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Listas Nominais: Quem Acertou e Quem Errou --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <div class="p-4 rounded-2xl border border-emerald-200 bg-emerald-50/40 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xs font-extrabold font-heading text-emerald-900 uppercase tracking-wider flex items-center gap-1.5">
                                    <x-lucide-check-circle class="w-4 h-4 text-emerald-700" />
                                    <span>Acertaram ({{ $report['correctCount'] }})</span>
                                </h3>
                            </div>
                            @if(!empty($report['correctStudents']))
                                <ul class="flex flex-wrap gap-1.5">
                                    @foreach($report['correctStudents'] as $st)
                                        <li class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-emerald-200 text-xs font-semibold text-emerald-950">
                                            <span>{{ $st['name'] }}</span>
                                            @if($classroom->collective_review_enabled && $st['changed'])
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-sky-blue-100 text-sky-blue-900" title="Alterou na revisão">
                                                    {{ $st['initial'] }}→{{ $st['final'] }}
                                                </span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-xs text-emerald-800/70 italic">Nenhum aluno acertou esta questão.</p>
                            @endif
                        </div>

                        <div class="p-4 rounded-2xl border border-rose-200 bg-rose-50/40 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xs font-extrabold font-heading text-rose-900 uppercase tracking-wider flex items-center gap-1.5">
                                    <x-lucide-x-circle class="w-4 h-4 text-rose-700" />
                                    <span>Erraram ({{ $report['wrongCount'] }})</span>
                                </h3>
                            </div>
                            @if(!empty($report['wrongStudents']))
                                <ul class="flex flex-wrap gap-1.5">
                                    @foreach($report['wrongStudents'] as $st)
                                        <li class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-rose-200 text-xs font-semibold text-rose-950">
                                            <span>{{ $st['name'] }}</span>
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-100 text-rose-800">
                                                @if($classroom->collective_review_enabled && $st['changed'])
                                                    {{ $st['initial'] }}→{{ $st['final'] }}
                                                @else
                                                    {{ $st['final'] }}
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-xs text-rose-800/70 italic">Nenhum aluno errou esta questão.</p>
                            @endif
                        </div>
                    </div>

                    @if(!empty($report['unansweredStudents']))
                        <div class="px-4 py-2.5 rounded-xl bg-gray-50 border border-gray-200 text-xs text-gray-700">
                            <span class="font-bold font-heading">Sem resposta registrada:</span>
                            {{ implode(', ', $report['unansweredStudents']) }}
                        </div>
                    @endif
                </article>
            @empty
                <div class="bg-white rounded-3xl p-10 text-center border border-magnolia-200 text-sm text-cloud-burst-600">
                    Nenhuma questão cadastrada nesta avaliação.
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.admin>
