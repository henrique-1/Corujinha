<x-layouts.app :title="'Revisão e Correção Coletiva · ' . $classroom->name . ' · Corujinha AVALIA+'">
    <header class="bg-gradient-to-r from-emerald-800 to-teal-900 text-white px-4 md:px-8 py-5 shadow-sm">
        <div class="max-w-5xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <picture>
                    <source srcset="{{ asset('images/logo/logo-icon.avif') }}" type="image/avif">
                    <source srcset="{{ asset('images/logo/logo-icon.webp') }}" type="image/webp">
                    <img src="{{ asset('images/logo/Corujinha_AVALIA_Mais_ICO.png') }}" alt="Corujinha" class="h-12 w-auto object-contain">
                </picture>
                <div>
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold font-heading bg-white/20 text-white mb-1">
                        Etapa 2 · Correção Coletiva
                    </span>
                    <h1 class="text-2xl md:text-3xl font-extrabold font-heading tracking-tight">
                        Revisão e Correção Coletiva
                    </h1>
                    <p class="text-xs md:text-sm text-emerald-100 mt-0.5">
                        Durante a discussão com a professora e a turma, você pode rever sua alternativa e justificar sua reflexão.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs bg-black/20 px-3.5 py-2 rounded-xl font-bold font-heading flex items-center gap-1.5">
                    <x-lucide-user class="w-4 h-4 text-emerald-300" />
                    <span>{{ $attempt->student_name }}</span>
                </span>
            </div>
        </div>
    </header>

    <main class="max-w-5xl w-full mx-auto px-4 md:px-8 py-8 space-y-8 grow">
        <form action="{{ route('estudante.concluir_revisao', $classroom->code) }}" method="POST" class="space-y-8">
            @csrf

            @foreach($questions as $idx => $q)
                @php
                    $ans = $answers->get($q->id);
                    $initialLetter = $ans?->initial_answer;
                @endphp

                <article class="bg-white rounded-3xl p-6 md:p-8 border border-emerald-200/80 shadow-xs space-y-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 text-emerald-900 font-extrabold font-heading text-sm">
                                {{ $idx + 1 }}
                            </span>
                            <h2 class="text-lg font-bold font-heading text-cloud-burst-950 m-0">Questão {{ $idx + 1 }}</h2>
                            @if($q->focus)
                                <span class="text-xs font-bold font-heading px-2.5 py-0.5 rounded-full bg-royal-purple-100 text-royal-purple-800">
                                    {{ $q->focus }}
                                </span>
                            @endif
                        </div>
                        <span class="text-xs font-bold font-heading px-3 py-1 rounded-full bg-broom-100 text-broom-950 border border-broom-300">
                            Sua resposta anterior: <b>Alternativa {{ $initialLetter ?: '—' }}</b>
                        </span>
                    </div>

                    {{-- Suporte: Texto ou Imagem --}}
                    @if($q->isImageSupport())
                        <div class="text-center my-3">
                            <img src="{{ $q->image_url }}" alt="Imagem de apoio" class="max-h-72 mx-auto rounded-2xl border border-magnolia-200 shadow-xs">
                        </div>
                    @elseif($q->support_text)
                        <div class="bg-magnolia-50/70 p-4 rounded-2xl border border-magnolia-200 text-sm text-cloud-burst-950 leading-relaxed">
                            @if($q->support_title)
                                <p class="font-bold font-heading text-cloud-burst-950 mb-2">{{ $q->support_title }}</p>
                            @endif
                            <p class="whitespace-pre-line m-0">{{ $q->support_text }}</p>
                        </div>
                    @endif

                    {{-- Comando --}}
                    <div>
                        <p class="text-xs font-bold font-heading text-royal-purple-800 uppercase tracking-wider mb-1">Comando da Questão</p>
                        <p class="text-base font-bold font-heading text-cloud-burst-950">{{ $q->prompt }}</p>
                    </div>

                    {{-- Alternativas para Revisão --}}
                    <div>
                        <p class="text-xs font-bold font-heading text-cloud-burst-800 uppercase tracking-wider mb-2">
                            Selecione sua resposta final (rever ou manter):
                        </p>
                        <div class="space-y-2">
                            @foreach($q->options as $opt)
                                <label class="flex items-start gap-3 p-3.5 rounded-xl border border-gray-200 hover:border-emerald-300 hover:bg-emerald-50/20 cursor-pointer transition has-checked:border-emerald-500 has-checked:bg-emerald-50/40">
                                    <input type="radio" name="reviews[{{ $q->id }}][reviewed_answer]" value="{{ $opt->letter }}"
                                           {{ ($ans?->reviewed_answer ?: $initialLetter) === $opt->letter ? 'checked' : '' }}
                                           class="mt-1 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                    <div>
                                        <span class="font-bold font-heading text-sm text-cloud-burst-950">{{ $opt->letter }})</span>
                                        <span class="text-sm text-cloud-burst-900 ml-1">{{ $opt->text }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Campo 4: Justificativa pós-correção coletiva --}}
                    <div class="bg-emerald-50/50 p-5 rounded-2xl border border-emerald-200 space-y-2">
                        <label for="reflection-{{ $q->id }}" class="text-xs font-bold font-heading text-emerald-950 uppercase tracking-wider block">
                            4. Meu registro de leitura · Depois da correção coletiva
                        </label>
                        <p class="text-xs text-cloud-burst-700 m-0">
                            Durante a correção coletiva com a professora e a turma: você manteve ou reviu sua resposta? O que aprendeu?
                        </p>
                        <textarea id="reflection-{{ $q->id }}" name="reviews[{{ $q->id }}][review_reflection]" rows="2"
                                  placeholder="Mantive ou revi minha resposta porque..."
                                  class="w-full px-3.5 py-2.5 text-sm bg-white border border-emerald-300 rounded-xl focus:border-emerald-500 focus:outline-none">{{ $ans?->review_reflection }}</textarea>
                    </div>
                </article>
            @endforeach

            <div class="text-center pt-4">
                <button type="submit" class="cursor-pointer px-8 py-4 rounded-2xl bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold font-heading text-base shadow-lg hover:shadow-xl transition flex items-center gap-2 mx-auto">
                    <span>Revisão Concluída · Ver Meu Resultado e Gabarito</span>
                    <x-lucide-arrow-right class="w-5 h-5" />
                </button>
            </div>
        </form>
    </main>
</x-layouts.app>
