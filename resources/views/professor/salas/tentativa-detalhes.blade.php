<x-layouts.admin :title="'Histórico de ' . $attempt->student_name . ' · Corujinha'">
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
                <div class="flex items-center gap-3 flex-wrap">
                    <form action="{{ route('professor.salas.tentativa.destroy', [$classroom, $attempt]) }}" method="POST" class="inline"
                          data-confirm="Tem certeza de que deseja deletar a resposta de {{ $attempt->student_name }}? Todas as respostas deste estudante para esta avaliação serão apagadas."
                          data-confirm-title="Deletar Resposta do Estudante"
                          data-confirm-ok="Deletar Resposta"
                          data-confirm-tone="danger">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cursor-pointer inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-2xl border border-rose-300 bg-rose-50 hover:bg-rose-100 text-rose-800 text-xs font-bold font-heading shadow-2xs transition">
                            <x-lucide-trash-2 class="w-4 h-4 text-rose-600" />
                            <span>Deletar Resposta</span>
                        </button>
                    </form>
                    <div class="bg-royal-purple-50 border border-royal-purple-200 px-4 py-2.5 rounded-2xl text-center">
                        <span class="text-[10px] uppercase font-extrabold font-heading text-royal-purple-800 block">Acertos</span>
                        <span class="text-xl font-black font-heading text-royal-purple-900">{{ $attempt->score }} / {{ $questions->count() }}</span>
                    </div>
                    <div class="bg-broom-100 border border-broom-300 px-4 py-2.5 rounded-2xl text-center">
                        <span class="text-[10px] uppercase font-extrabold font-heading text-broom-950 block">Nota / Pontos</span>
                        <span class="text-xl font-black font-heading text-broom-950">{{ number_format($attempt->earnedPoints(), 1, ',', '.') }} / {{ number_format($attempt->totalPossiblePoints(), 1, ',', '.') }}</span>
                    </div>
                    <div class="bg-amber-50 border border-amber-300 px-4 py-2.5 rounded-2xl text-center">
                        <span class="text-[10px] uppercase font-extrabold font-heading text-amber-900 block">Trocas de Aba</span>
                        <span class="text-xl font-black font-heading {{ ($attempt->tab_switches_count ?? 0) > 0 ? 'text-amber-800' : 'text-cloud-burst-950' }}">{{ $attempt->tab_switches_count ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        @php
            $formatDuration = fn (int $sec): string => sprintf('%02d:%02d', intdiv(max(0, $sec), 60), max(0, $sec) % 60);
            $totalTimeSpent = (int) $answers->sum('time_spent_seconds');
        @endphp

        {{-- Status da tentativa --}}
        <div class="bg-white rounded-2xl p-5 border border-royal-purple-200/60 shadow-xs flex flex-wrap items-center justify-between gap-4 text-xs">
            <div class="flex flex-wrap items-center gap-4">
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
                <div>
                    <span class="text-cloud-burst-600 block text-[11px]">Tempo Total nas Questões</span>
                    <span class="font-bold font-mono text-royal-purple-900">{{ $formatDuration($totalTimeSpent) }}</span>
                </div>
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

                    $timeLimitSec = (int) ($q->time_limit_seconds ?? 180);
                    $timeSpentSec = $ans ? (int) $ans->time_spent_seconds : null;
                    $overtimeSec = ($timeSpentSec !== null && $timeSpentSec > $timeLimitSec)
                        ? ($timeSpentSec - $timeLimitSec)
                        : 0;
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

                        <div class="flex items-center gap-2 flex-wrap">
                            @if($timeSpentSec !== null)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-mono border {{ $overtimeSec > 0 ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-gray-50 text-cloud-burst-800 border-gray-200' }}">
                                    <x-lucide-clock class="w-3.5 h-3.5 {{ $overtimeSec > 0 ? 'text-rose-600' : 'text-royal-purple-800' }}" />
                                    <span>Tempo: {{ $formatDuration($timeSpentSec) }} / Limite: {{ $formatDuration($timeLimitSec) }}</span>
                                    @if($overtimeSec > 0)
                                        <span class="text-rose-700 font-extrabold">(+{{ $formatDuration($overtimeSec) }})</span>
                                    @endif
                                </span>
                            @endif
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
                            @elseif($ans?->is_manually_graded && $ans?->manual_score > 0)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold font-heading bg-broom-100 text-broom-950 border border-broom-300">
                                    <x-lucide-check class="w-3.5 h-3.5 text-broom-800" />
                                    <span>Nota Parcial ({{ number_format($ans->manual_score, 1, ',', '.') }} pts)</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold font-heading bg-rose-100 text-rose-800 border border-rose-300">
                                    <x-lucide-x-circle class="w-3.5 h-3.5 text-rose-600" />
                                    <span>Errou</span>
                                </span>
                            @endif

                            @if($ans?->is_manually_graded)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-900 border border-royal-purple-300">
                                    <x-lucide-user-check class="w-3.5 h-3.5 text-royal-purple-700" />
                                    <span>Nota Manual</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Enunciado e Suporte resumidos --}}
                    <div class="space-y-2">
                        @if($q->support_title)
                            <p class="text-xs font-bold font-heading text-cloud-burst-800" data-math>{{ $q->support_title }}</p>
                        @endif
                        @if($q->support_text)
                            <div class="text-xs text-cloud-burst-900 leading-relaxed bg-magnolia-50/50 p-3 rounded-xl border border-magnolia-200" data-math>{!! $q->support_text !!}</div>
                        @endif
                        @if($q->hasImageSupport())
                            <div class="my-2">
                                <img src="{{ $q->image_url }}" alt="Imagem de apoio" class="max-h-48 rounded-xl border border-gray-200 shadow-2xs">
                            </div>
                        @endif

                        <div>
                            <p class="text-xs font-bold font-heading text-royal-purple-800 uppercase tracking-wider mb-1">Enunciado da Questão</p>
                            <p class="text-sm font-bold font-heading text-cloud-burst-950" data-math>{{ $q->prompt }}</p>
                        </div>

                        @if($q->isDirectInput())
                            <div class="mt-2 text-xs bg-royal-purple-50/70 p-3 rounded-xl border border-royal-purple-200">
                                <span class="text-cloud-burst-700 font-bold block mb-1">Gabarito / Resposta Esperada (Fórmula ou Valor):</span>
                                <span class="font-bold text-royal-purple-900 bg-white px-2.5 py-1 rounded-lg border border-royal-purple-200 inline-block font-mono text-sm" data-math>${{ $q->expected_answer }}$</span>
                                @if($q->expected_answer_explanation)
                                    <p class="text-[11px] text-cloud-burst-700 mt-1.5" data-math><b>Explicação:</b> {{ $q->expected_answer_explanation }}</p>
                                @endif
                                @if($q->hasExpectedAnswerImage())
                                    <div class="mt-2.5 pt-2 border-t border-royal-purple-200">
                                        <span class="text-[11px] font-bold text-royal-purple-900 block mb-1">
                                            Foto da Resolução Esperada (Folha de Cálculos do Professor):
                                        </span>
                                        <a href="{{ $q->expected_answer_image_url }}" target="_blank" class="block group">
                                            <img src="{{ $q->expected_answer_image_url }}" alt="Resolução Esperada do Professor" class="max-h-48 rounded-xl border border-gray-200 shadow-2xs group-hover:opacity-95 transition">
                                            <span class="text-[10px] font-bold text-royal-purple-800 flex items-center gap-1 mt-1 group-hover:underline">
                                                <x-lucide-external-link class="w-3 h-3" />
                                                <span>Ver foto da resolução em tamanho real</span>
                                            </span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-xs text-cloud-burst-600 mt-1">Gabarito Oficial: <b>Alternativa {{ $q->correct_option }}</b></p>
                        @endif
                    </div>

                    {{-- Histórico de Respostas Comparadas --}}
                    @if($q->isDirectInput())
                        <div class="bg-magnolia-50/70 p-5 rounded-2xl border border-magnolia-200 space-y-4">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <span class="text-xs font-extrabold font-heading text-cloud-burst-950 uppercase tracking-wider">
                                    Resposta Enviada pelo Estudante
                                </span>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold font-heading {{ $isCorrectFinal ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    Pontos Atribuídos: {{ number_format($ans?->score ?? 0, 1, ',', '.') }} / {{ number_format($q->pivot?->points ?? 1.0, 1, ',', '.') }}
                                </span>
                            </div>

                            <div class="bg-white p-4 rounded-xl border border-magnolia-200 space-y-2">
                                <span class="text-[11px] font-bold text-cloud-burst-700 block">Fórmula / Resposta Digitada:</span>
                                @if($initialLetter)
                                    <div class="font-bold text-cloud-burst-950 text-base" data-math>${{ $initialLetter }}$</div>
                                    <span class="text-[11px] font-mono text-cloud-burst-500 block">Texto bruto inserido: {{ $initialLetter }}</span>
                                @else
                                    <span class="text-xs text-cloud-burst-500 italic">O aluno não informou resposta em texto/fórmula.</span>
                                @endif
                            </div>

                            @if($ans?->hasResolutionImage())
                                <div class="bg-white p-4 rounded-xl border border-royal-purple-200 space-y-2">
                                    <span class="text-xs font-bold font-heading text-royal-purple-900 flex items-center gap-1.5">
                                        <x-lucide-image class="w-4 h-4 text-royal-purple-700" />
                                        <span>Foto da Resolução / Cálculos do Aluno:</span>
                                    </span>
                                    <div class="relative inline-block">
                                        <a href="{{ $ans->resolution_image_url }}" target="_blank" class="block group">
                                            <img src="{{ $ans->resolution_image_url }}" alt="Resolução do Aluno" class="max-h-60 rounded-xl border border-gray-200 shadow-2xs group-hover:opacity-95 transition">
                                            <span class="text-[11px] font-bold text-royal-purple-800 flex items-center gap-1 mt-1.5 group-hover:underline">
                                                <x-lucide-external-link class="w-3.5 h-3.5" />
                                                <span>Visualizar imagem em tamanho real</span>
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
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
                                                @if(is_array($m) && isset($m['text']) && is_string($m['text']))
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold font-heading {{ ($m['kind'] ?? '') === 'command' ? 'bg-broom-200 text-broom-950' : 'bg-sky-blue-200 text-sky-blue-950' }}">
                                                        "{{ $m['text'] }}"
                                                    </span>
                                                @endif
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
                    @endif

                    {{-- Bloco de Correção Manual do Professor --}}
                    @if($ans)
                        <div class="bg-royal-purple-50/50 p-4 rounded-2xl border border-royal-purple-200 space-y-3">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <span class="text-xs font-extrabold font-heading text-royal-purple-950 uppercase tracking-wider flex items-center gap-1.5">
                                    <x-lucide-file-edit class="w-4 h-4 text-royal-purple-800" />
                                    <span>Correção Manual do Professor</span>
                                </span>
                                @if($ans->is_manually_graded)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold font-heading bg-broom-200 text-broom-950 border border-broom-300">
                                        <x-lucide-check-circle-2 class="w-3 h-3 text-broom-800" />
                                        <span>Avaliação Manual Salva</span>
                                    </span>
                                @endif
                            </div>

                            <form action="{{ route('professor.salas.respostas.corrigir', [$classroom, $attempt, $ans]) }}" method="POST" class="space-y-3">
                                @csrf
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-bold text-cloud-burst-900 mb-1">
                                            Pontuação (Máx: {{ number_format($q->pivot?->points ?? 1.0, 1, ',', '.') }}):
                                        </label>
                                        <input type="number" step="0.1" min="0" max="{{ $q->pivot?->points ?? 1.0 }}" name="manual_score"
                                               value="{{ old('manual_score', $ans->manual_score !== null ? $ans->manual_score : $ans->score) }}"
                                               class="w-full px-3 py-1.5 text-xs rounded-xl border border-gray-300 focus:border-royal-purple-600 focus:ring-1 focus:ring-royal-purple-600 bg-white" required>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-cloud-burst-900 mb-1">
                                            Status da Correção:
                                        </label>
                                        <select name="is_correct" class="w-full px-3 py-1.5 text-xs rounded-xl border border-gray-300 focus:border-royal-purple-600 focus:ring-1 focus:ring-royal-purple-600 bg-white" required>
                                            <option value="correct" {{ ($ans->is_correct_final ?? $ans->is_correct_initial) ? 'selected' : '' }}>Correta (Pontuação Total)</option>
                                            <option value="partial" {{ ($ans->is_manually_graded && $ans->manual_score > 0 && !($ans->is_correct_final ?? $ans->is_correct_initial)) ? 'selected' : '' }}>Parcial (Pontuação Parcial)</option>
                                            <option value="incorrect" {{ (!($ans->is_correct_final ?? $ans->is_correct_initial) && (!$ans->is_manually_graded || $ans->manual_score == 0)) ? 'selected' : '' }}>Incorreta (Zerada)</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-cloud-burst-900 mb-1">
                                        Comentário / Devolutiva Pedagógica do Professor:
                                    </label>
                                    <textarea name="teacher_comment" rows="2" placeholder="Ex: O raciocínio está perfeito, verifique apenas a simplificação da fração final..."
                                              class="w-full px-3 py-1.5 text-xs rounded-xl border border-gray-300 focus:border-royal-purple-600 focus:ring-1 focus:ring-royal-purple-600 bg-white">{{ old('teacher_comment', $ans->teacher_comment) }}</textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="cursor-pointer inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-xs shadow-2xs transition">
                                        <x-lucide-save class="w-3.5 h-3.5" />
                                        <span>Salvar Correção e Nota</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
