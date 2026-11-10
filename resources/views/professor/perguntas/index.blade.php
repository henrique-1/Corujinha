<x-layouts.admin title="Banco de Perguntas · Corujinha AVALIA+">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                    Banco de Perguntas
                </h1>
                <p class="text-sm text-cloud-burst-700 mt-1">
                    Crie e organize perguntas pedagógicas com texto ou imagem de apoio, pistas e devolutivas
                </p>
            </div>
            <a href="{{ route('professor.perguntas.create') }}" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-sm shadow-sm transition">
                <x-lucide-plus class="w-4 h-4" />
                <span>Nova Pergunta</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-magnolia-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-royal-purple-50/70 border-b border-magnolia-200 text-cloud-burst-800 text-xs font-bold font-heading uppercase tracking-wider">
                            <th class="py-3.5 px-6">Tipo / Foco</th>
                            <th class="py-3.5 px-6">Comando da Questão</th>
                            <th class="py-3.5 px-6">Marca-Texto</th>
                            <th class="py-3.5 px-6 text-center">Gabarito</th>
                            <th class="py-3.5 px-6 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-magnolia-100">
                        @forelse($questions as $q)
                            <tr class="hover:bg-royal-purple-50/30 transition">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold font-heading {{ $q->support_type === 'image' ? 'bg-sky-blue-100 text-sky-blue-950' : 'bg-royal-purple-100 text-royal-purple-900' }}">
                                            @if($q->support_type === 'image')
                                                <x-lucide-image class="w-3 h-3" />
                                                <span>Imagem</span>
                                            @else
                                                <x-lucide-file-text class="w-3 h-3" />
                                                <span>Texto</span>
                                            @endif
                                        </span>
                                        @if($q->focus)
                                            <span class="text-xs font-bold font-heading text-cloud-burst-950">{{ $q->focus }}</span>
                                        @endif
                                    </div>
                                    @if($q->support_title)
                                        <p class="text-xs text-cloud-burst-600 mt-1 truncate max-w-xs">{{ $q->support_title }}</p>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-cloud-burst-950 font-medium">
                                    <p class="line-clamp-2 max-w-md">{{ $q->prompt }}</p>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        @if($q->require_command_highlight)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-heading bg-broom-100 text-broom-950 border border-broom-300" title="Exige marcação de comando">
                                                Comando
                                            </span>
                                        @endif
                                        @if($q->require_clue_highlight)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-heading bg-sky-blue-100 text-sky-blue-950 border border-sky-blue-300" title="Exige marcação de pistas">
                                                Pistas
                                            </span>
                                        @endif
                                        @if(! $q->require_command_highlight && ! $q->require_clue_highlight)
                                            <span class="text-xs text-gray-400">Opcional</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 text-emerald-800 font-extrabold font-heading text-xs">
                                        {{ $q->correct_option }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right space-x-2">
                                    <a href="{{ route('professor.perguntas.edit', $q) }}" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-royal-purple-800 hover:underline bg-royal-purple-50 hover:bg-royal-purple-100 px-3 py-1.5 rounded-lg border border-royal-purple-200 transition">
                                        <x-lucide-edit class="w-3.5 h-3.5" />
                                        <span>Editar</span>
                                    </a>
                                    <form action="{{ route('professor.perguntas.destroy', $q) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir esta pergunta?');">
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
                                <td colspan="5" class="py-8 text-center text-cloud-burst-600">
                                    Nenhuma pergunta cadastrada ainda. Clique em "Nova Pergunta" para começar seu banco de questões.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($questions->hasPages())
                <div class="p-4 border-t border-magnolia-200">
                    {{ $questions->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
