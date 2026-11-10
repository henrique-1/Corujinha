<x-layouts.admin :title="'Histórico de ' . $attempt->student_name . ' · Corujinha AVALIA+'">
    <div class="space-y-6">
        <div>
            <a href="{{ route('professor.salas.show', $classroom) }}" class="inline-flex items-center gap-1.5 text-xs font-bold font-heading text-royal-purple-800 hover:text-royal-purple-900 hover:underline mb-2 transition">
                <x-lucide-arrow-left class="w-4 h-4" />
                <span>Voltar para o Painel da Sala ({{ $classroom->name }})</span>
            </a>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-cloud-burst-950 font-heading tracking-tight flex items-center gap-2">
                        <span>{{ $attempt->student_name }}</span>
                    </h1>
                    <p class="text-sm text-cloud-burst-900/70 mt-1">
                        Sala: <span class="font-bold text-cloud-burst-950">{{ $classroom->name }}</span> · Avaliação: <span class="font-bold text-cloud-burst-950">{{ $classroom->assessment->title }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-royal-purple-50 border border-royal-purple-200 px-4 py-2.5 rounded-2xl text-center">
                        <span class="text-[10px] uppercase font-extrabold font-heading text-royal-purple-800 block">Pontuação Final</span>
                        <span class="text-xl font-black font-heading text-royal-purple-900">{{ $attempt->score }} / {{ $questions->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status da tentativa --}}
        <div class="bg-white rounded-2xl p-5 border border-royal-purple-200/60 shadow-xs flex flex-wrap items-center justify-between gap-4 text-xs">
            <div class="flex items-center gap-4">
                <div>
                    <span class="text-cloud-burst-600 block text-[11px]">Início</span>
                    <span class="font-bold text-cloud-burst-950 font-heading">{{ $attempt->started_at->format('d/m/Y H:i:s') }}</span>
                </div>
                @if($attempt->completed_at)
                    <div>
                        <span class="text-cloud-burst-600 block text-[11px]">Envio Inicial</span>
                        <span class="font-bold text-cloud-burst-950 font-heading">{{ $attempt->completed_at->format('d/m/Y H:i:s') }}</span>
                    </div>
                @endif
                @if($attempt->reviewed_at)
                    <div>
                        <span class="text-cloud-burst-600 block text-[11px]">Revisão Concluída</span>
                        <span class="font-bold text-cloud-burst-950 font-heading">{{ $attempt->reviewed_at->format('d/m/Y H:i:s') }}</span>
                    </div>
                @endif
            </div>

            <div>
                @if($attempt->status === 'reviewed')
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <x-lucide-check-circle class="w-3.5 h-3.5 text-emerald-600" />
                        <span>Passou pela Correção Coletiva</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold font-heading bg-broom-100 text-broom-950 border border-broom-300">
                        <x-lucide-clock class="w-3.5 h-3.5 text-broom-800" />
                        <span>Resolução Direta</span>
                    </span>
                @endif
            </div>
        </div>

        {{-- Lista de Questões e Histórico de Respostas --}}
        <div class="space-y-6">
            @foreach($questions as $idx => $q)
                @php
                    $ans = $answers->get($q->id);
                    $initialLetter = $ans?->initial_answer;
                    $reviewedLetter = $ans?->reviewed_answer;
                    $finalLetter = $reviewedLetter ?: $initialLetter;

                    $changedAnswer = ($reviewedLetter && $reviewedLetter !== $initialLetter);
                    $isCorrectInitial = (bool) $ans?->is_correct_initial;
                    $isCorrectFinal = (bool) $ans?->is_correct_final;
                @endphp

                <article class="bg-white rounded-3xl p-6 md:p-8 border border-royal-purple-200/60 shadow-xs space-y-6">
                    <div class="flex items-center justify-between border-b border-royal-purple-100 pb-4 flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-royal-purple-100 text-royal-purple-900 font-extrabold font-heading text-sm">
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
                            @if($changedAnswer)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold font-heading bg-sky-blue-100 text-sky-blue-950 border border-sky-blue-300">
                                    <x-lucide-refresh-cw class="w-3.5 h-3.5 text-sky-blue-800" />
                                    <span>Alterou de {{ $initialLetter }} para {{ $reviewedLetter }}</span>
                                </span>
                            @elseif($reviewedLetter)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold font-heading bg-gray-100 text-gray-800 border border-gray-300">
                                    <x-lucide-check class="w-3.5 h-3.5 text-gray-600" />
                                    <span>Manteve a Resposta {{ $initialLetter }}</span>
                                </span>
                            @endif

                            @if($isCorrectFinal)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    <x-lucide-check-circle class="w-3.5 h-3.5 text-emerald-600" />
                                    <span>Acertou</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold font-heading bg-rose-100 text-rose-800 border border-rose-300">
                                    <x-lucide-x-circle class="w-3.5 h-3.5 text-rose-600" />
                                    <span>Errou</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Enunciado e Suporte resumidos --}}
                    <div>
                        <p class="text-xs font-bold font-heading text-royal-purple-800 uppercase tracking-wider mb-1">Enunciado da Questão</p>
                        <p class="text-sm font-bold font-heading text-cloud-burst-950">{{ $q->prompt }}</p>
                        <p class="text-xs text-cloud-burst-600 mt-1">Gabarito Oficial: <b>Alternativa {{ $q->correct_option }}</b></p>
                    </div>

                    {{-- Histórico de Respostas Comparadas --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Resposta Inicial --}}
                        <div class="bg-magnolia-50/70 p-4 rounded-2xl border border-magnolia-200 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold font-heading text-cloud-burst-950 uppercase tracking-wider">
                                    1ª Resposta (Individual)
                                </span>
                                <span class="px-2 py-0.5 rounded text-xs font-bold font-heading {{ $isCorrectInitial ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    Alternativa {{ $initialLetter ?: '—' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-cloud-burst-700 block">Justificativa Inicial:</span>
                                <p class="text-xs text-cloud-burst-900 italic bg-white p-2.5 rounded-xl border border-magnolia-200 mt-0.5">
                                    "{{ $ans?->initial_reason ?: 'Não informada' }}"
                                </p>
                            </div>
                            @if(!empty($ans?->initial_markers))
                                <div class="pt-1">
                                    <span class="text-[10px] font-bold text-cloud-burst-600 block uppercase">Destaques com Marca-Texto ({{ count($ans->initial_markers) }} marcações):</span>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach($ans->initial_markers as $m)
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold font-heading {{ ($m['kind'] ?? '') === 'command' ? 'bg-broom-200 text-broom-950' : 'bg-sky-blue-200 text-sky-blue-950' }}">
                                                "{{ $m['text'] ?? '' }}"
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Resposta Pós-Revisão --}}
                        <div class="bg-emerald-50/40 p-4 rounded-2xl border border-emerald-200 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold font-heading text-emerald-950 uppercase tracking-wider">
                                    2ª Resposta (Pós-Correção Coletiva)
                                </span>
                                <span class="px-2 py-0.5 rounded text-xs font-bold font-heading {{ $isCorrectFinal ? 'bg-emerald-200 text-emerald-900' : 'bg-rose-100 text-rose-800' }}">
                                    Alternativa {{ $finalLetter ?: '—' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-emerald-800 block">Reflexão / Justificativa da Mudança:</span>
                                <p class="text-xs text-cloud-burst-900 italic bg-white p-2.5 rounded-xl border border-emerald-200 mt-0.5">
                                    "{{ $ans?->review_reflection ?: 'Nenhuma alteração de justificativa informada' }}"
                                </p>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
