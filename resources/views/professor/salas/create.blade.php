<x-layouts.admin title="Nova Sala de Aula · Corujinha">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <a href="{{ route('professor.salas.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-royal-purple-800 hover:text-royal-purple-900 hover:underline mb-2 transition">
                <x-lucide-arrow-left class="w-4 h-4" />
                <span>Voltar para Minhas Salas</span>
            </a>
            <h1 class="text-2xl font-extrabold text-cloud-burst-950 font-heading tracking-tight">
                Criar Nova Sala de Aula
            </h1>
            <p class="text-sm text-cloud-burst-900/70 mt-1">
                Gere um código de acesso e defina se a turma terá a etapa de Revisão Coletiva
            </p>
        </div>

        <form action="{{ route('professor.salas.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-white rounded-2xl p-6 md:p-8 border border-royal-purple-200/60 shadow-xs space-y-5">
                <div>
                    <label for="name" class="block text-xs font-bold text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Identificação da Sala / Turma <span class="text-shocking-pink-600">*</span>
                    </label>
                    <input id="name" name="name" type="text" required value="{{ old('name') }}" placeholder="Ex.: Turma 7º Ano B · Língua Portuguesa"
                           class="w-full px-4 py-3 text-sm border border-royal-purple-200 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-200 focus:outline-none transition">
                </div>

                <div>
                    <label for="assessment_id" class="block text-xs font-bold text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Avaliação que os estudantes responderão <span class="text-shocking-pink-600">*</span>
                    </label>
                    @if($assessments->isEmpty())
                        <div class="p-4 rounded-xl bg-broom-50 border border-broom-300 text-xs text-broom-950 flex items-start gap-2">
                            <x-lucide-alert-circle class="w-4 h-4 text-broom-800 shrink-0 mt-0.5" />
                            <div>
                                Você ainda não tem avaliações com perguntas cadastradas.
                                <a href="{{ route('professor.avaliacoes.create') }}" class="font-bold underline ml-1 text-royal-purple-800">Crie uma avaliação primeiro →</a>
                            </div>
                        </div>
                    @else
                        <select id="assessment_id" name="assessment_id" required
                                class="w-full px-4 py-3 text-sm border border-royal-purple-200 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-200 focus:outline-none bg-white transition">
                            <option value="" data-questions-count="0">Selecione uma avaliação...</option>
                            @foreach($assessments as $av)
                                <option value="{{ $av->uuid }}" data-questions-count="{{ $av->questions_count }}" {{ old('assessment_id') == $av->uuid ? 'selected' : '' }}>
                                    {{ $av->title }}@if($av->academic_period) · {{ $av->academic_period }}@endif ({{ $av->questions_count }} questões)
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div>
                    <label for="code" class="block text-xs font-bold text-cloud-burst-950 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                        <span>Código de Acesso da Sala (Alunos usam este código) <span class="text-shocking-pink-600">*</span></span>
                        <span class="text-[11px] font-bold text-royal-purple-800 flex items-center gap-1 normal-case">
                            <x-lucide-lock class="w-3 h-3" />
                            <span>Gerado automaticamente</span>
                        </span>
                    </label>
                    <input id="code" name="code" type="text" readonly required value="{{ old('code', $suggestedCode) }}"
                           class="w-full px-4 py-3 text-base font-mono font-bold tracking-widest uppercase border border-royal-purple-200 rounded-xl bg-gray-100/80 text-royal-purple-900 cursor-not-allowed select-all focus:outline-none transition">
                    <p class="text-[11px] text-cloud-burst-900/60 mt-1">Este código exclusivo é gerado pelo sistema para garantir segurança e unicidade.</p>
                </div>

                {{-- Toggle de Revisão e Correção Coletiva + Limite de Alterações --}}
                <div class="pt-2 border-t border-royal-purple-100 space-y-3">
                    <label class="flex items-start gap-3 p-4 rounded-xl border border-royal-purple-200 bg-royal-purple-50/40 hover:bg-royal-purple-50 cursor-pointer transition">
                        <input id="collective_review_enabled" type="checkbox" name="collective_review_enabled" value="1" {{ old('collective_review_enabled', '1') ? 'checked' : '' }}
                               class="mt-1 rounded text-royal-purple-800 focus:ring-royal-purple-800 w-4 h-4">
                        <div>
                            <span class="text-sm font-bold text-cloud-burst-950 block font-heading">Ativar Etapa de "Revisão e Correção Coletiva"</span>
                            <p class="text-xs text-cloud-burst-900/80 mt-0.5 leading-relaxed">
                                <b>Se ATIVADO:</b> Após responder as questões, o estudante entra no Modo de Revisão para mudar sua alternativa e justificar a mudança. Depois, vê o gabarito.
                            </p>
                            <p class="text-xs text-cloud-burst-900/60 mt-1 leading-relaxed">
                                <b>Se DESATIVADO:</b> O estudante é direcionado imediatamente após o teste para a tela de Resultado, visualizando quais acertou/errou com as devolutivas.
                            </p>
                        </div>
                    </label>

                    <div id="review-limit-wrapper" class="p-4 rounded-xl border border-magnolia-200 bg-magnolia-50/60 space-y-2">
                        <label for="max_review_changes" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider">
                            Quantas questões o aluno poderá alterar na Revisão Coletiva? <span class="text-shocking-pink-600">*</span>
                        </label>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <input id="max_review_changes" name="max_review_changes" type="number" min="1"
                                   value="{{ old('max_review_changes', 1) }}"
                                   class="w-32 px-3.5 py-2 text-sm font-mono font-bold text-center border border-royal-purple-200 rounded-xl bg-white focus:border-royal-purple-800 focus:outline-none">
                            <span id="review-limit-hint" class="text-xs text-cloud-burst-700">
                                Defina de <b>1</b> até o total de questões da avaliação selecionada.
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('professor.salas.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-300 text-cloud-burst-900 text-xs font-bold hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="cursor-pointer px-6 py-3 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white text-sm font-bold shadow-md transition flex items-center gap-2">
                        <span>Criar Sala e Gerar Código</span>
                        <x-lucide-arrow-right class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const reviewToggle = document.getElementById('collective_review_enabled');
                const assessmentSelect = document.getElementById('assessment_id');
                const limitWrapper = document.getElementById('review-limit-wrapper');
                const limitInput = document.getElementById('max_review_changes');
                const limitHint = document.getElementById('review-limit-hint');
                const hasOldValue = @json(old('max_review_changes') !== null);

                function syncReviewLimitState(updateDefaultValue = false) {
                    const enabled = Boolean(reviewToggle?.checked);
                    if (limitWrapper) {
                        limitWrapper.classList.toggle('hidden', !enabled);
                    }
                    if (!limitInput) return;

                    limitInput.disabled = !enabled;
                    limitInput.required = enabled;

                    const selectedOpt = assessmentSelect?.selectedOptions?.[0];
                    const qCount = parseInt(selectedOpt?.dataset?.questionsCount || '0', 10);

                    if (qCount > 0) {
                        limitInput.max = String(qCount);
                        if (updateDefaultValue || parseInt(limitInput.value || '0', 10) > qCount) {
                            limitInput.value = String(qCount);
                        }
                        if (limitHint) {
                            limitHint.innerHTML = `Escolha entre <b>1</b> e <b>${qCount}</b> questão(ões) cadastrada(s) nesta avaliação.`;
                        }
                    } else {
                        limitInput.removeAttribute('max');
                        if (limitHint) {
                            limitHint.innerHTML = 'Selecione uma avaliação para definir entre <b>1</b> e o total de questões.';
                        }
                    }
                }

                reviewToggle?.addEventListener('change', () => syncReviewLimitState(false));
                assessmentSelect?.addEventListener('change', () => syncReviewLimitState(true));
                syncReviewLimitState(!hasOldValue);
            });
        </script>
    @endpush
</x-layouts.admin>
