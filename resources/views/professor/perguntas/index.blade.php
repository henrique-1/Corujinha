<x-layouts.admin title="Banco de Perguntas · Corujinha">
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

        {{-- Barra de Filtros e Busca --}}
        <div class="bg-white rounded-2xl p-4 border border-magnolia-200 shadow-xs">
            <form action="{{ route('professor.perguntas.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="search" class="block text-[11px] font-bold font-heading text-cloud-burst-800 uppercase tracking-wider mb-1">
                        Buscar no Enunciado ou Fonte
                    </label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Digite palavras-chave..."
                           class="w-full px-3 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                </div>

                <div>
                    <label for="area" class="block text-[11px] font-bold font-heading text-cloud-burst-800 uppercase tracking-wider mb-1">
                        Área do Conhecimento
                    </label>
                    <select id="area" name="area" class="w-full px-3 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                        <option value="">Todas as áreas</option>
                        @foreach($areas as $area)
                            <option value="{{ $area }}" {{ request('area') === $area ? 'selected' : '' }}>{{ $area }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="grade_level" class="block text-[11px] font-bold font-heading text-cloud-burst-800 uppercase tracking-wider mb-1">
                        Turma / Ano Escolar
                    </label>
                    <select id="grade_level" name="grade_level" class="w-full px-3 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                        <option value="">Todas as turmas</option>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade }}" {{ request('grade_level') === $grade ? 'selected' : '' }}>{{ $grade }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="cursor-pointer grow px-4 py-2 bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-xs rounded-xl transition shadow-2xs">
                        Filtrar
                    </button>
                    @if(request()->hasAny(['search', 'area', 'grade_level']))
                        <a href="{{ route('professor.perguntas.index') }}" class="cursor-pointer px-3 py-2 border border-magnolia-300 hover:bg-magnolia-50 text-cloud-burst-800 font-bold font-heading text-xs rounded-xl transition">
                            Limpar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-magnolia-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-royal-purple-50/70 border-b border-magnolia-200 text-cloud-burst-800 text-xs font-bold font-heading uppercase tracking-wider">
                            <th class="py-3.5 px-6">Área / Turma</th>
                            <th class="py-3.5 px-6">Comando & Suporte</th>
                            <th class="py-3.5 px-6">Tempo</th>
                            <th class="py-3.5 px-6">Marca-Texto</th>
                            <th class="py-3.5 px-6 text-center">Gabarito</th>
                            <th class="py-3.5 px-6 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-magnolia-100">
                        @forelse($questions as $q)
                            <tr class="hover:bg-royal-purple-50/30 transition">
                                <td class="py-4 px-6 align-top">
                                    <div class="flex flex-col gap-1.5 items-start">
                                        @if($q->area)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-royal-purple-100 text-royal-purple-900">
                                                {{ $q->area }}
                                            </span>
                                        @endif
                                        @if($q->grade_level)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-broom-100 text-broom-950 border border-broom-200">
                                                {{ $q->grade_level }}
                                            </span>
                                        @endif
                                        @if(! $q->area && ! $q->grade_level)
                                            <span class="text-xs text-gray-400">Geral</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-cloud-burst-950 font-medium">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        @if($q->hasTextSupport())
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold font-heading bg-magnolia-200 text-cloud-burst-900">
                                                <x-lucide-file-text class="w-3 h-3" />
                                                <span>Texto</span>
                                            </span>
                                        @endif
                                        @if($q->hasImageSupport())
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold font-heading bg-sky-blue-100 text-sky-blue-950">
                                                <x-lucide-image class="w-3 h-3" />
                                                <span>Imagem</span>
                                            </span>
                                        @endif
                                        @if(! $q->hasTextSupport() && ! $q->hasImageSupport())
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold font-heading bg-gray-100 text-gray-600">
                                                <span>Sem apoio</span>
                                            </span>
                                        @endif
                                        @if($q->focus)
                                            <span class="text-xs font-bold font-heading text-cloud-burst-700 truncate max-w-xs">{{ $q->focus }}</span>
                                        @endif
                                    </div>
                                    <p class="line-clamp-2 max-w-md text-sm">{{ $q->prompt }}</p>
                                    @if($q->support_title)
                                        <p class="text-xs text-royal-purple-700 mt-1 truncate max-w-md italic font-heading font-medium">Fonte: {{ $q->support_title }}</p>
                                    @endif
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap align-top">
                                    <span class="inline-flex items-center gap-1 text-xs font-bold font-mono text-cloud-burst-800 bg-gray-50 px-2 py-1 rounded-lg border border-gray-200">
                                        <x-lucide-clock class="w-3.5 h-3.5 text-royal-purple-800" />
                                        <span>{{ ceil($q->time_limit_seconds / 60) }} min</span>
                                    </span>
                                </td>
                                <td class="py-4 px-6 align-top">
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
                                <td class="py-4 px-6 text-center align-top">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 text-emerald-800 font-extrabold font-heading text-xs">
                                        {{ $q->correct_option }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right space-x-2 align-top whitespace-nowrap">
                                    <a href="{{ route('professor.perguntas.edit', $q) }}" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-royal-purple-800 hover:underline bg-royal-purple-50 hover:bg-royal-purple-100 px-3 py-1.5 rounded-lg border border-royal-purple-200 transition">
                                        <x-lucide-edit class="w-3.5 h-3.5" />
                                        <span>Editar</span>
                                    </a>
                                    <form action="{{ route('professor.perguntas.destroy', $q) }}" method="POST" class="inline"
                                          data-confirm="Tem certeza que deseja excluir esta pergunta?"
                                          data-confirm-title="Excluir Pergunta"
                                          data-confirm-ok="Excluir"
                                          data-confirm-tone="danger">
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
                                <td colspan="6" class="py-8 text-center text-cloud-burst-600">
                                    Nenhuma pergunta encontrada com os filtros atuais.
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
