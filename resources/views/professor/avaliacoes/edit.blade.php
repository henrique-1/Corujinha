<x-layouts.admin title="Editar Avaliação · Corujinha AVALIA+">
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <a href="{{ route('professor.avaliacoes.index') }}" class="text-xs font-bold font-heading text-royal-purple-800 hover:underline mb-2 inline-flex items-center gap-1">
                <x-lucide-arrow-left class="w-3.5 h-3.5" />
                <span>Voltar para Minhas Avaliações</span>
            </a>
            <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                Editar Avaliação
            </h1>
        </div>

        <form action="{{ route('professor.avaliacoes.update', $assessment) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-2xl p-6 border border-magnolia-200 shadow-xs space-y-4">
                <div>
                    <label for="title" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Título da Avaliação <span class="text-shocking-pink-600">*</span>
                    </label>
                    <input id="title" name="title" type="text" required value="{{ old('title', $assessment->title) }}"
                           class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Instruções ou Descrição Geral
                    </label>
                    <textarea id="description" name="description" rows="2"
                              class="w-full px-4 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">{{ old('description', $assessment->description) }}</textarea>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-magnolia-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold font-heading text-cloud-burst-950 flex items-center gap-2">
                        <span>Perguntas da Avaliação</span>
                    </h2>
                    <span class="text-xs text-cloud-burst-700">Marque as perguntas que farão parte desta avaliação</span>
                </div>

                <div class="divide-y divide-magnolia-100 border border-magnolia-200 rounded-xl max-h-96 overflow-y-auto">
                    @foreach($questions as $q)
                        <label class="flex items-start gap-3 p-4 hover:bg-royal-purple-50/30 cursor-pointer transition">
                            <input type="checkbox" name="question_ids[]" value="{{ $q->id }}"
                                   {{ (is_array(old('question_ids')) ? in_array($q->id, old('question_ids')) : in_array($q->id, $selectedQuestionIds)) ? 'checked' : '' }}
                                   class="mt-1 rounded text-royal-purple-800 focus:ring-royal-purple-800 w-4 h-4">
                            <div class="grow">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-heading {{ $q->support_type === 'image' ? 'bg-sky-blue-100 text-sky-blue-950' : 'bg-royal-purple-100 text-royal-purple-900' }}">
                                        {{ $q->support_type === 'image' ? 'Imagem' : 'Texto' }}
                                    </span>
                                    @if($q->focus)
                                        <span class="text-xs font-bold font-heading text-cloud-burst-950">{{ $q->focus }}</span>
                                    @endif
                                    <span class="text-[11px] text-cloud-burst-600 ml-auto">Gabarito: {{ $q->correct_option }}</span>
                                </div>
                                <p class="text-xs text-cloud-burst-950 font-medium line-clamp-2">{{ $q->prompt }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('professor.avaliacoes.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-300 text-cloud-burst-800 text-xs font-bold font-heading hover:bg-gray-50 transition">
                    Cancelar
                </a>
                <button type="submit" class="cursor-pointer px-6 py-3 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white text-sm font-bold font-heading shadow-md transition flex items-center gap-2">
                    <span>Salvar Alterações</span>
                    <x-lucide-arrow-right class="w-4 h-4" />
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
