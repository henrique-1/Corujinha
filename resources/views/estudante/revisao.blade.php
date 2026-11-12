<x-layouts.app :title="'Revisão e Correção Coletiva · ' . $classroom->name . ' · Corujinha'">
    <header class="bg-gradient-to-r from-emerald-800 to-teal-900 text-white px-4 md:px-8 py-5 shadow-sm">
        <div class="max-w-5xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-brand-logo variant="icon" img-class="h-12 w-auto object-contain" />
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

    <main class="max-w-5xl w-full mx-auto px-4 md:px-8 py-8 space-y-8 grow" data-max-review-changes="{{ $maxReviewChanges }}" data-classroom-id="{{ $classroom->id }}">
        @if($errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-300 text-rose-900 text-sm font-semibold space-y-1">
                @foreach($errors->all() as $error)
                    <p class="m-0">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if(session('info'))
            <div class="p-4 rounded-2xl bg-royal-purple-50 border border-royal-purple-300 text-royal-purple-900 text-sm font-semibold">
                {{ session('info') }}
            </div>
        @endif

        <div class="sticky top-3 z-20 p-4 rounded-2xl bg-white/95 backdrop-blur border border-emerald-300 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 text-sm text-cloud-burst-900">
                <x-lucide-refresh-cw class="w-4 h-4 text-emerald-700 shrink-0" />
                <span>
                    Você pode escrever sua reflexão em todas as questões e alterar a alternativa em até
                    <strong class="font-extrabold text-emerald-900">{{ $maxReviewChanges }} questão(ões)</strong>.
                </span>
            </div>
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-emerald-100 text-emerald-900 border border-emerald-300 text-xs font-extrabold font-heading shrink-0">
                <span>Alterações:</span>
                <span><span id="review-changes-counter">0</span> / {{ $maxReviewChanges }}</span>
            </div>
        </div>

        <form id="review-form" action="{{ route('estudante.concluir_revisao', $classroom->code) }}" method="POST" class="space-y-8">
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

                    {{-- Suporte: Texto e/ou Imagem --}}
                    @if($q->hasTextSupport())
                        <div class="bg-magnolia-50/70 p-4 rounded-2xl border border-magnolia-200 text-sm text-cloud-burst-950 leading-relaxed space-y-2">
                            @if($q->support_title)
                                <p class="font-bold font-heading text-cloud-burst-950 m-0">{{ $q->support_title }}</p>
                            @endif
                            @if($q->support_text)
                                <div class="rich-text-content m-0">{!! \App\Services\RichTextSanitizer::toHtml($q->support_text) !!}</div>
                            @endif
                        </div>
                    @endif
                    @if($q->hasImageSupport())
                        <div class="text-center my-3">
                            <img src="{{ $q->image_url }}" alt="Imagem de apoio" class="max-h-72 mx-auto rounded-2xl border border-magnolia-200 shadow-xs">
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
                                <label class="flex items-start gap-3 p-3.5 rounded-xl border border-gray-200 hover:border-emerald-300 hover:bg-emerald-50/20 cursor-pointer transition has-checked:border-emerald-500 has-checked:bg-emerald-50/40 has-disabled:opacity-50 has-disabled:cursor-not-allowed">
                                    <input type="radio"
                                           name="reviews[{{ $q->id }}][reviewed_answer]"
                                           value="{{ $opt->letter }}"
                                           data-question-review="{{ $q->id }}"
                                           data-initial-answer="{{ $initialLetter }}"
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
                <button id="review-submit-btn" type="submit" class="cursor-pointer px-8 py-4 rounded-2xl bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold font-heading text-base shadow-lg hover:shadow-xl transition flex items-center gap-2 mx-auto">
                    <span>Revisão Concluída · Ver Meu Resultado e Gabarito</span>
                    <x-lucide-arrow-right class="w-5 h-5" />
                </button>
            </div>
        </form>
    </main>

    <div id="classroom-closed-modal" role="alertdialog" aria-modal="true" aria-labelledby="closed-modal-title"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-cloud-burst-950/75 backdrop-blur-xs p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 md:p-8 border border-royal-purple-200 shadow-2xl space-y-4 text-center">
            <div class="w-12 h-12 rounded-2xl bg-royal-purple-100 text-royal-purple-800 flex items-center justify-center mx-auto">
                <x-lucide-lock class="w-6 h-6" />
            </div>
            <h2 id="closed-modal-title" class="text-xl font-extrabold font-heading text-cloud-burst-950">
                Avaliação Finalizada
            </h2>
            <p id="closed-modal-message" class="text-sm text-cloud-burst-700 leading-relaxed">
                A avaliação foi finalizada pelo professor. Você será redirecionado para a tela de devolutiva.
            </p>
            <a id="closed-modal-link" href="{{ route('estudante.resultado', $classroom->code) }}"
               class="inline-flex items-center justify-center gap-2 w-full px-5 py-3 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-sm transition">
                <span>Ir para a Devolutiva Agora</span>
                <x-lucide-arrow-right class="w-4 h-4" />
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mainEl = document.querySelector('[data-max-review-changes]');
            if (!mainEl) return;

            const maxChanges = Math.max(1, parseInt(mainEl.dataset.maxReviewChanges || '1', 10));
            const classroomId = mainEl.dataset.classroomId;
            const counterEl = document.getElementById('review-changes-counter');
            const formEl = document.getElementById('review-form');
            const radios = Array.from(document.querySelectorAll('input[data-question-review]'));

            const questionIds = Array.from(new Set(radios.map((r) => r.dataset.questionReview)));

            function syncReviewLimit() {
                const changedQuestionIds = new Set();

                questionIds.forEach((qId) => {
                    const checked = document.querySelector(`input[data-question-review="${qId}"]:checked`);
                    if (checked) {
                        const initial = checked.dataset.initialAnswer || '';
                        if (initial !== '' && checked.value !== initial) {
                            changedQuestionIds.add(qId);
                        }
                    }
                });

                const count = changedQuestionIds.size;
                if (counterEl) {
                    counterEl.textContent = String(count);
                }

                const quotaReached = count >= maxChanges;

                questionIds.forEach((qId) => {
                    const isAlreadyChanged = changedQuestionIds.has(qId);
                    const groupRadios = radios.filter((r) => r.dataset.questionReview === qId);

                    groupRadios.forEach((radio) => {
                        const initial = radio.dataset.initialAnswer || '';
                        const shouldLock = quotaReached && !isAlreadyChanged && radio.value !== initial;
                        radio.disabled = shouldLock;
                    });
                });
            }

            radios.forEach((radio) => {
                radio.addEventListener('change', syncReviewLimit);
            });
            syncReviewLimit();

            if (window.Echo && classroomId) {
                window.Echo.channel(`classroom.${classroomId}`)
                    .listen('.classroom.closed', (e) => {
                        if (formEl) {
                            formEl.querySelectorAll('input, textarea, button').forEach((el) => {
                                el.disabled = true;
                            });
                        }

                        const modal = document.getElementById('classroom-closed-modal');
                        const msgEl = document.getElementById('closed-modal-message');
                        const linkEl = document.getElementById('closed-modal-link');
                        const redirectUrl = e?.redirect_url || @json(route('estudante.resultado', $classroom->code));

                        if (msgEl && e?.message) {
                            msgEl.textContent = e.message;
                        }
                        if (linkEl) {
                            linkEl.href = redirectUrl;
                        }
                        if (modal) {
                            modal.classList.remove('hidden');
                            modal.classList.add('flex');
                        }

                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 2000);
                    });
            }
        });
    </script>
</x-layouts.app>
