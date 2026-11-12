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

                {{-- Toggle de Revisão e Correção Coletiva + Limite de Alterações --}}
                <div class="pt-2 border-t border-royal-purple-100 space-y-3">
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
                const reviewToggle = document.getElementById('collective_review_enabled');
                const limitWrapper = document.getElementById('review-limit-wrapper');
                const limitInput = document.getElementById('max_review_changes');

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
                syncReviewLimitState();
            });
        </script>
    @endpush
</x-layouts.admin>
