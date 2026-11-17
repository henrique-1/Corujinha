<x-layouts.admin :title="'Editar Sala · ' . $classroom->name . ' · Corujinha'">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <a href="{{ route('professor.salas.show', $classroom) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-royal-purple-800 hover:text-royal-purple-900 hover:underline mb-2 transition">
                <x-lucide-arrow-left class="w-4 h-4" />
                <span>Voltar para o Painel da Sala</span>
            </a>
            <h1 class="text-2xl font-extrabold text-cloud-burst-950 font-heading tracking-tight">
                Editar Sala de Aula
            </h1>
            <p class="text-sm text-cloud-burst-900/70 mt-1">
                Altere a identificação da turma, ative/desative a Revisão Coletiva e defina o limite de questões alteráveis
            </p>
        </div>

        <form action="{{ route('professor.salas.update', $classroom) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-2xl p-6 md:p-8 border border-royal-purple-200/60 shadow-xs space-y-5">
                <div>
                    <label for="name" class="block text-xs font-bold text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Identificação da Sala / Turma <span class="text-shocking-pink-600">*</span>
                    </label>
                    <input id="name" name="name" type="text" required value="{{ old('name', $classroom->name) }}"
                           class="w-full px-4 py-3 text-sm border border-royal-purple-200 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-200 focus:outline-none transition">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-3.5 rounded-xl bg-gray-50 border border-magnolia-200">
                        <span class="block text-[11px] font-bold text-cloud-burst-700 uppercase tracking-wider mb-1">Avaliação Aplicada</span>
                        <span class="text-sm font-bold font-heading text-cloud-burst-950">{{ $classroom->assessment->title }}</span>
                        <span class="block text-xs text-cloud-burst-600 mt-0.5">{{ $totalQuestions }} questão(ões)</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-gray-50 border border-magnolia-200">
                        <span class="block text-[11px] font-bold text-cloud-burst-700 uppercase tracking-wider mb-1">Código da Sala</span>
                        <span class="text-base font-mono font-extrabold text-royal-purple-800 tracking-widest">{{ $classroom->code }}</span>
                    </div>
                </div>

                {{-- Modo de Jogo da Sala (Corujinha vs Corujão) --}}
                <div class="pt-2 border-t border-royal-purple-100 space-y-3">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <span class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                                Modo de Jogo da Sala <span class="text-shocking-pink-600">*</span>
                            </span>
                            <p class="text-xs text-cloud-burst-700">Selecione o nível de desafio e a mecânica do cronômetro para esta turma.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="game-mode-card relative flex items-start gap-3.5 p-4 rounded-2xl border-2 cursor-pointer transition {{ old('game_mode', $classroom->game_mode ?? 'corujinha') === 'corujinha' ? 'border-royal-purple-800 bg-royal-purple-50/70 ring-2 ring-royal-purple-400/40 shadow-xs' : 'border-magnolia-200 bg-white hover:border-royal-purple-300' }}">
                            <input type="radio" name="game_mode" value="corujinha" {{ old('game_mode', $classroom->game_mode ?? 'corujinha') === 'corujinha' ? 'checked' : '' }} class="mt-1 text-royal-purple-800 focus:ring-royal-purple-800 w-4 h-4 shrink-0">
                            <div class="w-14 h-14 rounded-2xl bg-royal-purple-100/60 border border-royal-purple-200/60 flex items-center justify-center p-1.5 shrink-0 shadow-2xs">
                                <picture>
                                    <source srcset="{{ asset('images/logo/corujinha-icone.avif') }}" type="image/avif">
                                    <source srcset="{{ asset('images/logo/corujinha-icone.webp') }}" type="image/webp">
                                    <img src="{{ asset('images/logo/corujinha-icone.png') }}" alt="Modo Corujinha" class="w-10 h-10 object-contain drop-shadow-xs">
                                </picture>
                            </div>
                            <div class="space-y-1 grow min-w-0">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <span class="text-sm font-bold font-heading text-cloud-burst-950">Modo Corujinha</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-broom-100 text-broom-950 border border-broom-300">Padrão · Fácil</span>
                                </div>
                                <p class="text-xs text-cloud-burst-800 leading-relaxed">
                                    O cronômetro é um guia pedagógico. O aluno avança no seu próprio ritmo e você pode ativar a <b>Revisão Coletiva</b> antes do resultado final.
                                </p>
                                <span class="selected-indicator text-[11px] font-bold text-royal-purple-800 {{ old('game_mode', $classroom->game_mode ?? 'corujinha') === 'corujinha' ? '' : 'hidden' }} inline-flex items-center gap-1 pt-1">
                                    <x-lucide-check-circle class="w-3.5 h-3.5 text-royal-purple-700" />
                                    <span>Modo Selecionado</span>
                                </span>
                            </div>
                        </label>

                        <label class="game-mode-card relative flex items-start gap-3.5 p-4 rounded-2xl border-2 cursor-pointer transition {{ old('game_mode', $classroom->game_mode ?? 'corujinha') === 'corujao' ? 'border-royal-purple-800 bg-royal-purple-50/70 ring-2 ring-royal-purple-400/40 shadow-xs' : 'border-magnolia-200 bg-white hover:border-royal-purple-300' }}">
                            <input type="radio" name="game_mode" value="corujao" {{ old('game_mode', $classroom->game_mode ?? 'corujinha') === 'corujao' ? 'checked' : '' }} class="mt-1 text-royal-purple-800 focus:ring-royal-purple-800 w-4 h-4 shrink-0">
                            <div class="w-14 h-14 rounded-2xl bg-shocking-pink-100/60 border border-shocking-pink-200/60 flex items-center justify-center p-1.5 shrink-0 shadow-2xs">
                                <picture>
                                    <source srcset="{{ asset('images/logo/CORUJAO_ICONE.avif') }}" type="image/avif">
                                    <source srcset="{{ asset('images/logo/CORUJAO_ICONE.webp') }}" type="image/webp">
                                    <img src="{{ asset('images/logo/CORUJAO_300_DPI.png') }}" alt="Modo Corujão" class="w-10 h-10 object-contain drop-shadow-xs">
                                </picture>
                            </div>
                            <div class="space-y-1 grow min-w-0">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <span class="text-sm font-bold font-heading text-cloud-burst-950">Modo Corujão</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-shocking-pink-100 text-shocking-pink-900 border border-shocking-pink-300">Hardcore · Relógio Rígido</span>
                                </div>
                                <p class="text-xs text-cloud-burst-800 leading-relaxed">
                                    <b>Sem Revisão Coletiva.</b> Ao zerar o tempo da questão, a resposta é salva e o aluno é <b>redirecionado automaticamente</b> para a próxima pergunta.
                                </p>
                                <span class="selected-indicator text-[11px] font-bold text-shocking-pink-900 {{ old('game_mode', $classroom->game_mode ?? 'corujinha') === 'corujao' ? '' : 'hidden' }} inline-flex items-center gap-1 pt-1">
                                    <x-lucide-check-circle class="w-3.5 h-3.5 text-shocking-pink-700" />
                                    <span>Modo Selecionado</span>
                                </span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Toggle de Revisão e Correção Coletiva + Limite de Alterações --}}
                <div id="bloco-revisao-coletiva" class="pt-2 border-t border-royal-purple-100 space-y-3">
                    <label class="flex items-start gap-3 p-4 rounded-xl border border-royal-purple-200 bg-royal-purple-50/40 hover:bg-royal-purple-50 cursor-pointer transition">
                        <input id="collective_review_enabled" type="checkbox" name="collective_review_enabled" value="1"
                               {{ old('collective_review_enabled', $classroom->collective_review_enabled) ? 'checked' : '' }}
                               class="mt-1 rounded text-royal-purple-800 focus:ring-royal-purple-800 w-4 h-4">
                        <div>
                            <span class="text-sm font-bold text-cloud-burst-950 block font-heading">Ativar Etapa de "Revisão e Correção Coletiva"</span>
                            <p class="text-xs text-cloud-burst-900/80 mt-0.5 leading-relaxed">
                                <b>Se ATIVADO:</b> Após responder as questões, o estudante entra no Modo de Revisão para mudar sua alternativa e justificar a mudança. Depois, vê o gabarito.
                            </p>
                            <p class="text-xs text-cloud-burst-900/60 mt-1 leading-relaxed">
                                <b>Se DESATIVADO:</b> O estudante é direcionado imediatamente após o teste para a tela de Resultado com gabarito e devolutivas.
                            </p>
                        </div>
                    </label>

                    <div id="review-limit-wrapper" class="p-4 rounded-xl border border-magnolia-200 bg-magnolia-50/60 space-y-2">
                        <label for="max_review_changes" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider">
                            Quantas questões o aluno poderá alterar na Revisão Coletiva? <span class="text-shocking-pink-600">*</span>
                        </label>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <input id="max_review_changes" name="max_review_changes" type="number" min="1" max="{{ max(1, $totalQuestions) }}"
                                   value="{{ old('max_review_changes', $classroom->effectiveMaxReviewChanges()) }}"
                                   class="w-32 px-3.5 py-2 text-sm font-mono font-bold text-center border border-royal-purple-200 rounded-xl bg-white focus:border-royal-purple-800 focus:outline-none">
                            <span class="text-xs text-cloud-burst-700">
                                Escolha entre <b>1</b> e <b>{{ max(1, $totalQuestions) }}</b> questão(ões) cadastrada(s) nesta avaliação.
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Agendamento de Abertura e Fechamento (Lição de Casa) --}}
                <div class="pt-2 border-t border-royal-purple-100 space-y-3">
                    <div>
                        <span class="text-sm font-bold text-cloud-burst-950 block font-heading flex items-center gap-1.5">
                            <x-lucide-calendar-clock class="w-4 h-4 text-royal-purple-800" />
                            <span>Agendamento de Abertura e Fechamento (Opcional · Lição de Casa)</span>
                        </span>
                        <p class="text-xs text-cloud-burst-700 mt-0.5">
                            Defina uma data/hora de abertura e de encerramento para controlar quando os estudantes podem responder.
                            Deixe em branco para liberar imediatamente e sem prazo final.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="starts_at" class="block text-xs font-bold text-cloud-burst-950 uppercase tracking-wider mb-1">
                                Data e Hora de Abertura
                            </label>
                            <input id="starts_at" name="starts_at" type="datetime-local"
                                   value="{{ old('starts_at', $classroom->starts_at?->format('Y-m-d\TH:i')) }}"
                                   class="w-full px-3.5 py-2.5 text-xs border border-royal-purple-200 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                            <p class="text-[11px] text-cloud-burst-600 mt-1">Se não informado, a sala abre no momento da criação.</p>
                        </div>
                        <div>
                            <label for="ends_at" class="block text-xs font-bold text-cloud-burst-950 uppercase tracking-wider mb-1">
                                Data e Hora de Fechamento (Prazo Final)
                            </label>
                            <input id="ends_at" name="ends_at" type="datetime-local"
                                   value="{{ old('ends_at', $classroom->ends_at?->format('Y-m-d\TH:i')) }}"
                                   class="w-full px-3.5 py-2.5 text-xs border border-royal-purple-200 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                            <p class="text-[11px] text-cloud-burst-600 mt-1">Se não informado, a sala permanece aberta até você fechá-la.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('professor.salas.show', $classroom) }}" class="px-5 py-2.5 rounded-xl border border-gray-300 text-cloud-burst-900 text-xs font-bold hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="cursor-pointer px-6 py-3 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white text-sm font-bold shadow-md transition flex items-center gap-2">
                        <span>Salvar Alterações</span>
                        <x-lucide-arrow-right class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const gameModeRadios = document.querySelectorAll('input[name="game_mode"]');
                const blocoRevisao = document.getElementById('bloco-revisao-coletiva');
                const reviewToggle = document.getElementById('collective_review_enabled');
                const limitWrapper = document.getElementById('review-limit-wrapper');
                const limitInput = document.getElementById('max_review_changes');

                function syncGameMode() {
                    const selectedMode = document.querySelector('input[name="game_mode"]:checked')?.value || 'corujinha';
                    document.querySelectorAll('.game-mode-card').forEach((card) => {
                        const radio = card.querySelector('input[name="game_mode"]');
                        const indicator = card.querySelector('.selected-indicator');
                        if (radio?.checked) {
                            card.classList.add('border-royal-purple-800', 'bg-royal-purple-50/70', 'ring-2', 'ring-royal-purple-400/40', 'shadow-xs');
                            card.classList.remove('border-magnolia-200', 'bg-white', 'hover:border-royal-purple-300');
                            if (indicator) indicator.classList.remove('hidden');
                        } else {
                            card.classList.remove('border-royal-purple-800', 'bg-royal-purple-50/70', 'ring-2', 'ring-royal-purple-400/40', 'shadow-xs');
                            card.classList.add('border-magnolia-200', 'bg-white', 'hover:border-royal-purple-300');
                            if (indicator) indicator.classList.add('hidden');
                        }
                    });

                    if (selectedMode === 'corujao') {
                        if (blocoRevisao) blocoRevisao.classList.add('hidden');
                        if (reviewToggle) reviewToggle.checked = false;
                        syncReviewLimitState();
                    } else {
                        if (blocoRevisao) blocoRevisao.classList.remove('hidden');
                        syncReviewLimitState();
                    }
                }

                gameModeRadios.forEach((r) => r.addEventListener('change', syncGameMode));

                function syncReviewLimitState() {
                    const enabled = Boolean(reviewToggle?.checked);
                    if (limitWrapper) {
                        limitWrapper.classList.toggle('hidden', !enabled);
                    }
                    if (limitInput) {
                        limitInput.disabled = !enabled;
                        limitInput.required = enabled;
                    }
                }

                reviewToggle?.addEventListener('change', syncReviewLimitState);
                syncGameMode();
                syncReviewLimitState();
            });
        </script>
    @endpush
</x-layouts.admin>
