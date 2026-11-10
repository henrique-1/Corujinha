<x-layouts.app :title="'Resultado e Devolutivas · ' . $classroom->name . ' · Corujinha AVALIA+'">
    <header class="bg-royal-purple-950 text-white px-4 md:px-8 py-6 shadow-md">
        <div class="max-w-5xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <picture>
                    <source srcset="{{ asset('images/logo/logo-icon.avif') }}" type="image/avif">
                    <source srcset="{{ asset('images/logo/logo-icon.webp') }}" type="image/webp">
                    <img src="{{ asset('images/logo/Corujinha_AVALIA_Mais_ICO.png') }}" alt="Corujinha" class="h-12 w-auto object-contain">
                </picture>
                <div>
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold font-heading bg-shocking-pink-500/20 text-shocking-pink-200 border border-shocking-pink-400/30 mb-1">
                        Gabarito & Devolutivas Pedagógicas
                    </span>
                    <h1 class="text-2xl md:text-3xl font-extrabold font-heading tracking-tight">
                        Desempenho da Avaliação
                    </h1>
                    <p class="text-xs md:text-sm text-royal-purple-200 mt-0.5">
                        {{ $classroom->name }} · {{ $classroom->assessment->title }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-white/10 px-5 py-3 rounded-2xl border border-white/15">
                <div class="text-center">
                    <span class="text-xs text-royal-purple-200 uppercase tracking-wider block font-bold font-heading">Total de Acertos</span>
                    <span class="text-3xl font-extrabold font-heading text-broom-400">{{ $score }} / {{ $totalQuestions }}</span>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-5xl w-full mx-auto px-4 md:px-8 py-8 space-y-8 grow">
        <div class="space-y-6">
            @foreach($questions as $idx => $q)
                @php
                    $ans = $answers->get($q->id);
                    $initialLetter = $ans?->initial_answer ? trim(strtoupper($ans->initial_answer)) : null;
                    $reviewedLetter = $ans?->reviewed_answer ? trim(strtoupper($ans->reviewed_answer)) : null;
                    $chosenLetter = $reviewedLetter ?: $initialLetter;
                    $correctOption = trim(strtoupper($q->correct_option));

                    $isCorrect = ($chosenLetter === $correctOption);
                    $isInitialCorrect = ($initialLetter === $correctOption);
                    $hasReviewDifference = ($reviewedLetter && $reviewedLetter !== $initialLetter);
                    $markers = $ans?->initial_markers ?? [];
                @endphp

                <article class="bg-white rounded-3xl p-6 md:p-8 border shadow-xs space-y-6 {{ $isCorrect ? 'border-emerald-300 bg-emerald-50/10' : 'border-rose-200 bg-rose-50/10' }}">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full font-extrabold font-heading text-sm {{ $isCorrect ? 'bg-emerald-100 text-emerald-900' : 'bg-rose-100 text-rose-900' }}">
                                {{ $idx + 1 }}
                            </span>
                            <h2 class="text-lg font-bold font-heading text-cloud-burst-950 m-0">Questão {{ $idx + 1 }}</h2>
                            @if($q->focus)
                                <span class="text-xs font-bold font-heading px-2.5 py-0.5 rounded-full bg-royal-purple-100 text-royal-purple-800">
                                    {{ $q->focus }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            @if($classroom->collective_review_enabled)
                                @if($hasReviewDifference)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold font-heading bg-sky-blue-100 text-sky-blue-950 border border-sky-blue-300">
                                        <x-lucide-refresh-cw class="w-3.5 h-3.5 text-sky-blue-800" />
                                        <span>Você alterou de {{ $initialLetter }} para {{ $reviewedLetter }}</span>
                                    </span>
                                @elseif($initialLetter)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold font-heading bg-gray-100 text-gray-800 border border-gray-200">
                                        <x-lucide-check class="w-3.5 h-3.5 text-gray-600" />
                                        <span>Você manteve a resposta inicial ({{ $initialLetter }})</span>
                                    </span>
                                @endif
                            @endif

                            @if($isCorrect)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    <x-lucide-check-circle class="w-3.5 h-3.5 text-emerald-600" />
                                    <span>Você Acertou</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-rose-100 text-rose-800 border border-rose-300">
                                    <x-lucide-x-circle class="w-3.5 h-3.5 text-rose-600" />
                                    <span>Você Errou</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Suporte: Imagem ou Texto com Destaques do Marca-Texto --}}
                    @if($q->isImageSupport())
                        <div class="text-center my-3">
                            <img src="{{ $q->image_url }}" alt="Imagem de apoio" class="max-h-72 mx-auto rounded-2xl border border-magnolia-200">
                        </div>
                    @elseif($q->support_text)
                        <div class="bg-magnolia-50/70 p-5 rounded-2xl border border-magnolia-200 space-y-3">
                            @if($q->support_title)
                                <p class="font-bold font-heading text-cloud-burst-950 text-base mb-1">{{ $q->support_title }}</p>
                            @endif
                            <div class="text-sm text-cloud-burst-950 leading-relaxed">
                                {!! \App\Services\TextHighlightService::applyHighlights($q->support_text, $markers, 'support') !!}
                            </div>
                        </div>
                    @endif

                    {{-- Comando da questão com Marca-Texto --}}
                    <div>
                        <p class="text-xs font-bold font-heading text-royal-purple-800 uppercase tracking-wider mb-1">Comando da Questão</p>
                        <div class="text-base font-bold font-heading text-cloud-burst-950">
                            {!! \App\Services\TextHighlightService::applyHighlights($q->prompt, $markers, 'prompt') !!}
                        </div>
                    </div>

                    {{-- Resumo dos Destaques de Marca-Texto Realizados pelo Aluno --}}
                    @if(!empty($markers))
                        <div class="p-4 rounded-2xl bg-royal-purple-50/60 border border-royal-purple-200 text-xs space-y-2">
                            <span class="font-bold font-heading text-cloud-burst-950 uppercase tracking-wider flex items-center gap-1.5">
                                <x-lucide-highlighter class="w-3.5 h-3.5 text-royal-purple-800" />
                                <span>Sentenças e Trechos que Você Marcou nesta Questão:</span>
                            </span>
                            <div class="flex flex-wrap gap-2">
                                @foreach($markers as $m)
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

                    {{-- Devolutivas de Comandos e Pistas --}}
                    @if($q->command_feedback || $q->clue_feedback)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if($q->command_feedback)
                                <div class="bg-broom-50 p-4 rounded-2xl border border-broom-300 text-xs text-broom-950 space-y-1">
                                    <p class="font-bold font-heading flex items-center gap-1.5">
                                        <x-lucide-highlighter class="w-4 h-4 text-broom-800" />
                                        <span>Devolutiva do Comando:</span>
                                    </p>
                                    @if($q->expected_commands)
                                        <p class="font-mono text-[11px] text-broom-900">Trecho esperado: "{{ $q->expected_commands }}"</p>
                                    @endif
                                    <p>{{ $q->command_feedback }}</p>
                                </div>
                            @endif

                            @if($q->clue_feedback)
                                <div class="bg-sky-blue-50 p-4 rounded-2xl border border-sky-blue-300 text-xs text-sky-blue-950 space-y-1">
                                    <p class="font-bold font-heading flex items-center gap-1.5">
                                        <x-lucide-search class="w-4 h-4 text-sky-blue-800" />
                                        <span>Devolutiva das Pistas:</span>
                                    </p>
                                    @if($q->expected_clues)
                                        <p class="font-mono text-[11px] text-sky-blue-900">Pistas no texto: "{{ $q->expected_clues }}"</p>
                                    @endif
                                    <p>{{ $q->clue_feedback }}</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Histórico Comparativo de Respostas do Estudante --}}
                    @if($classroom->collective_review_enabled && $initialLetter)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 rounded-2xl border border-magnolia-300 bg-white space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider">
                                        1ª Resposta (Individual)
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded text-xs font-bold font-heading {{ $isInitialCorrect ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        Alternativa {{ $initialLetter }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-[11px] font-bold text-cloud-burst-700 block">Sua Justificativa Inicial:</span>
                                    <p class="text-xs text-cloud-burst-900 italic bg-magnolia-50 p-2.5 rounded-xl border border-magnolia-200 mt-0.5">
                                        "{{ $ans?->initial_reason ?: 'Não informada' }}"
                                    </p>
                                </div>
                            </div>

                            <div class="p-4 rounded-2xl border border-emerald-300 bg-white space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold font-heading text-emerald-950 uppercase tracking-wider">
                                        Resposta Final (Pós-Revisão)
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded text-xs font-bold font-heading {{ $isCorrect ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        Alternativa {{ $chosenLetter }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-[11px] font-bold text-emerald-800 block">Sua Reflexão na Correção Coletiva:</span>
                                    <p class="text-xs text-cloud-burst-900 italic bg-emerald-50/50 p-2.5 rounded-xl border border-emerald-200 mt-0.5">
                                        "{{ $ans?->review_reflection ?: 'Você manteve sua justificativa inicial' }}"
                                    </p>
                                </div>
                            </div>
                        </div>
                    @elseif($ans?->initial_reason)
                        <div class="bg-royal-purple-50/50 p-4 rounded-xl border border-royal-purple-200 text-xs text-royal-purple-950">
                            <span class="font-bold font-heading block text-cloud-burst-900 mb-1">Sua Justificativa da Alternativa Escolhida:</span>
                            <p class="text-cloud-burst-700 italic m-0">"{{ $ans->initial_reason }}"</p>
                        </div>
                    @endif

                    {{-- Alternativas e Devolutivas Pedagógicas --}}
                    <div class="space-y-3">
                        <p class="text-xs font-bold font-heading text-cloud-burst-800 uppercase tracking-wider">
                            Gabarito e Devolutivas das Alternativas:
                        </p>
                        <div class="space-y-2.5">
                            @foreach($q->options as $opt)
                                @php
                                    $optLetter = trim(strtoupper($opt->letter));
                                    $isOptionCorrect = ($optLetter === $correctOption);
                                    $isFinalChosen = ($chosenLetter === $optLetter);
                                    $isInitialChosen = ($initialLetter === $optLetter);
                                @endphp
                                <div class="p-3.5 rounded-xl border transition {{ $isOptionCorrect ? 'border-emerald-400 bg-emerald-50/70' : ($isFinalChosen ? 'border-rose-400 bg-rose-50/70' : 'border-magnolia-300 bg-white') }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex items-start gap-2">
                                            <span class="font-bold font-heading text-sm {{ $isOptionCorrect ? 'text-emerald-900 font-extrabold' : 'text-cloud-burst-950' }}">
                                                {{ $opt->letter }})
                                            </span>
                                            <span class="text-sm {{ $isOptionCorrect ? 'text-emerald-950 font-bold' : 'text-cloud-burst-900' }}">
                                                {{ $opt->text }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0 flex-wrap">
                                            @if($classroom->collective_review_enabled && $isInitialChosen && !$isFinalChosen)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-gray-200 text-gray-800">
                                                    Sua 1ª Escolha
                                                </span>
                                            @endif
                                            @if($isFinalChosen)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-royal-purple-100 text-royal-purple-900">
                                                    {{ $classroom->collective_review_enabled && $hasReviewDifference ? 'Escolha Final' : 'Sua Escolha' }}
                                                </span>
                                            @endif
                                            @if($isOptionCorrect)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-emerald-200 text-emerald-900">
                                                    Gabarito Correto
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($opt->feedback)
                                        <div class="mt-2 pt-2 border-t {{ $isOptionCorrect ? 'border-emerald-200 text-emerald-900' : 'border-gray-100 text-cloud-burst-700' }} text-xs leading-relaxed">
                                            <b>Devolutiva pedagógica:</b> {{ $opt->feedback }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="text-center pt-6">
            <a href="{{ route('estudante.entrar') }}" class="cursor-pointer inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-sm shadow-md transition">
                <span>Concluir e Voltar ao Início</span>
                <x-lucide-arrow-right class="w-4 h-4" />
            </a>
        </div>
    </main>
</x-layouts.app>
