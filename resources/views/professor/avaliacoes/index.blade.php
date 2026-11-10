<x-layouts.admin title="Avaliações · Corujinha AVALIA+">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                    Minhas Avaliações
                </h1>
                <p class="text-sm text-cloud-burst-700 mt-1">
                    Agrupe conjuntos de perguntas em avaliações prontas para aplicar em salas de aula
                </p>
            </div>
            <a href="{{ route('professor.avaliacoes.create') }}" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-sm shadow-sm transition">
                <x-lucide-plus class="w-4 h-4" />
                <span>Nova Avaliação</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-magnolia-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-royal-purple-50/70 border-b border-magnolia-200 text-cloud-burst-800 text-xs font-bold font-heading uppercase tracking-wider">
                            <th class="py-3.5 px-6">Título da Avaliação</th>
                            <th class="py-3.5 px-6">Qtd. de Perguntas</th>
                            <th class="py-3.5 px-6">Criada em</th>
                            <th class="py-3.5 px-6 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-magnolia-100">
                        @forelse($assessments as $item)
                            <tr class="hover:bg-royal-purple-50/30 transition">
                                <td class="py-4 px-6 font-bold font-heading text-cloud-burst-950">
                                    {{ $item->title }}
                                    @if($item->description)
                                        <p class="text-xs text-cloud-burst-600 font-normal mt-0.5">{{ $item->description }}</p>
                                    @endif
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-800 border border-royal-purple-200">
                                        {{ $item->questions_count }} questão(ões)
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-cloud-burst-600 text-xs">
                                    {{ $item->created_at->format('d/m/Y') }}
                                </td>
                                <td class="py-4 px-6 text-right space-x-2">
                                    <a href="{{ route('professor.avaliacoes.edit', $item) }}" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-royal-purple-800 hover:underline bg-royal-purple-50 hover:bg-royal-purple-100 px-3 py-1.5 rounded-lg border border-royal-purple-200 transition">
                                        <x-lucide-edit class="w-3.5 h-3.5" />
                                        <span>Editar</span>
                                    </a>
                                    <form action="{{ route('professor.avaliacoes.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Deseja realmente excluir esta avaliação?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg border border-rose-200 transition">
                                            <x-lucide-trash-2 class="w-3.5 h-3.5" />
                                            <span>Excluir</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-cloud-burst-600">
                                    Nenhuma avaliação criada ainda. Cadastre perguntas no banco e depois crie sua primeira avaliação!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($assessments->hasPages())
                <div class="p-4 border-t border-magnolia-200">
                    {{ $assessments->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
