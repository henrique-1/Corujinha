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
            <div class="flex items-center gap-2">
                <button type="button" onclick="document.getElementById('modal-create-folder').classList.remove('hidden')"
                        class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white hover:bg-magnolia-50 text-cloud-burst-950 font-bold font-heading text-sm border border-magnolia-300 shadow-2xs transition">
                    <x-lucide-folder-plus class="w-4 h-4 text-royal-purple-800" />
                    <span>Nova Pasta</span>
                </button>
                <a href="{{ route('professor.perguntas.create') }}" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-sm shadow-sm transition">
                    <x-lucide-plus class="w-4 h-4" />
                    <span>Nova Pergunta</span>
                </a>
            </div>
        </div>

        {{-- Seção de Pastas de Perguntas --}}
        @if(($myFolders->isNotEmpty()) || ($sharedFolders->isNotEmpty()))
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-extrabold font-heading text-cloud-burst-950 uppercase tracking-wider flex items-center gap-2">
                        <x-lucide-folder class="w-4 h-4 text-royal-purple-800" />
                        <span>Pastas de Perguntas</span>
                    </h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                    {{-- Minhas Pastas --}}
                    @foreach($myFolders as $folder)
                        <div class="bg-white rounded-2xl p-4 border border-magnolia-200 hover:border-royal-purple-300 shadow-xs hover:shadow-sm transition flex flex-col justify-between space-y-3">
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="w-8 h-8 rounded-xl bg-royal-purple-100 text-royal-purple-800 flex items-center justify-center">
                                        <x-lucide-folder class="w-4 h-4" />
                                    </div>
                                    @if($folder->visibility === 'shared')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-heading bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            Compartilhada
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-heading bg-gray-100 text-gray-700 border border-gray-200">
                                            Privada
                                        </span>
                                    @endif
                                </div>
                                <a href="{{ route('professor.pastas.show', $folder) }}" class="block font-bold font-heading text-cloud-burst-950 hover:text-royal-purple-800 text-sm leading-snug">
                                    {{ $folder->name }}
                                </a>
                                @if($folder->description)
                                    <p class="text-xs text-cloud-burst-600 line-clamp-2">{{ $folder->description }}</p>
                                @endif
                            </div>

                            <div class="pt-2 border-t border-magnolia-100 flex items-center justify-between text-xs">
                                <span class="text-cloud-burst-600 font-medium">
                                    <b>{{ $folder->questions_count }}</b> questão(ões)
                                </span>
                                <a href="{{ route('professor.pastas.show', $folder) }}" class="font-bold text-royal-purple-800 hover:underline">
                                    Abrir Pasta →
                                </a>
                            </div>
                        </div>
                    @endforeach

                    {{-- Pastas Compartilhadas Comigo --}}
                    @foreach($sharedFolders as $sFolder)
                        @php
                            $userShare = $sFolder->shares->first();
                            $perm = $userShare?->permission ?? 'view';
                        @endphp
                        <div class="bg-royal-purple-50/40 rounded-2xl p-4 border border-royal-purple-200 hover:border-royal-purple-400 shadow-xs hover:shadow-sm transition flex flex-col justify-between space-y-3">
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-800 flex items-center justify-center">
                                        <x-lucide-folder-open class="w-4 h-4" />
                                    </div>
                                    @if($perm === 'edit')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Pode Editar
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-heading bg-amber-100 text-amber-800 border border-amber-200">
                                            Apenas Leitura
                                        </span>
                                    @endif
                                </div>
                                <a href="{{ route('professor.pastas.show', $sFolder) }}" class="block font-bold font-heading text-cloud-burst-950 hover:text-royal-purple-800 text-sm leading-snug">
                                    {{ $sFolder->name }}
                                </a>
                                <p class="text-[11px] text-cloud-burst-600">De: <b>{{ $sFolder->user->name }}</b></p>
                            </div>

                            <div class="pt-2 border-t border-royal-purple-100 flex items-center justify-between text-xs">
                                <span class="text-cloud-burst-600 font-medium">
                                    <b>{{ $sFolder->questions_count }}</b> questão(ões)
                                </span>
                                <form action="{{ route('professor.pastas.clone', $sFolder) }}" method="POST" class="inline"
                                      data-confirm="Clonar esta pasta para sua conta? Todas as perguntas serão copiadas."
                                      data-confirm-title="Clonar Pasta"
                                      data-confirm-ok="Clonar"
                                      data-confirm-tone="primary">
                                    @csrf
                                    <button type="submit" class="cursor-pointer font-bold text-royal-purple-800 hover:underline flex items-center gap-1" title="Clonar para minha conta">
                                        <x-lucide-copy class="w-3.5 h-3.5" />
                                        <span>Clonar</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Abas de Navegação: Minhas Perguntas vs Compartilhadas Comigo --}}
        <div class="flex items-center gap-2 border-b border-magnolia-200">
            <a href="{{ route('professor.perguntas.index', array_merge(request()->query(), ['tab' => 'minhas'])) }}"
               class="cursor-pointer px-4 py-2.5 text-xs font-bold font-heading border-b-2 transition flex items-center gap-2 {{ ($tab ?? 'minhas') === 'minhas' ? 'border-royal-purple-800 text-royal-purple-950' : 'border-transparent text-cloud-burst-600 hover:text-royal-purple-800' }}">
                <x-lucide-library class="w-4 h-4" />
                <span>Minhas Perguntas</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ ($tab ?? 'minhas') === 'minhas' ? 'bg-royal-purple-100 text-royal-purple-900 font-extrabold' : 'bg-gray-100 text-gray-700' }}">
                    {{ $myQuestionsCount ?? 0 }}
                </span>
            </a>

            <a href="{{ route('professor.perguntas.index', array_merge(request()->query(), ['tab' => 'compartilhadas'])) }}"
               class="cursor-pointer px-4 py-2.5 text-xs font-bold font-heading border-b-2 transition flex items-center gap-2 {{ ($tab ?? 'minhas') === 'compartilhadas' ? 'border-royal-purple-800 text-royal-purple-950' : 'border-transparent text-cloud-burst-600 hover:text-royal-purple-800' }}">
                <x-lucide-share-2 class="w-4 h-4" />
                <span>Compartilhadas Comigo</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ ($tab ?? 'minhas') === 'compartilhadas' ? 'bg-royal-purple-100 text-royal-purple-900 font-extrabold' : 'bg-gray-100 text-gray-700' }}">
                    {{ $sharedQuestionsCount ?? 0 }}
                </span>
            </a>
        </div>

        {{-- Barra de Filtros e Busca --}}
        <div class="bg-white rounded-2xl p-4 border border-magnolia-200 shadow-xs">
            <form action="{{ route('professor.perguntas.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <input type="hidden" name="tab" value="{{ $tab ?? 'minhas' }}">
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
                        <a href="{{ route('professor.perguntas.index', ['tab' => $tab ?? 'minhas']) }}" class="cursor-pointer px-3 py-2 border border-magnolia-300 hover:bg-magnolia-50 text-cloud-burst-800 font-bold font-heading text-xs rounded-xl transition">
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
                            @if(($tab ?? 'minhas') === 'compartilhadas')
                                <th class="py-3.5 px-6">Proprietário / Permissão</th>
                            @else
                                <th class="py-3.5 px-6">Compartilhamento</th>
                            @endif
                            <th class="py-3.5 px-6">Tempo</th>
                            <th class="py-3.5 px-6">Marca-Texto</th>
                            <th class="py-3.5 px-6 text-center">Gabarito</th>
                            <th class="py-3.5 px-6 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-magnolia-100">
                        @forelse($questions as $q)
                            @php
                                $isOwner = (int)$q->user_id === (int)auth()->id();
                                $userShare = !$isOwner ? $q->shares->first(fn($s) => (int)$s->user_id === (int)auth()->id() || mb_strtolower($s->email) === mb_strtolower(auth()->user()->email)) : null;
                                $canEdit = $isOwner || ($userShare && $userShare->permission === 'edit') || auth()->user()->isSuperUser();
                            @endphp
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
                                        @if($q->folder)
                                            <a href="{{ route('professor.pastas.show', $q->folder) }}"
                                               class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-magnolia-200 text-royal-purple-900 border border-magnolia-300 hover:border-royal-purple-500">
                                                <x-lucide-folder class="w-3 h-3 text-royal-purple-800" />
                                                <span>{{ $q->folder->name }}</span>
                                            </a>
                                        @endif
                                        @if(! $q->area && ! $q->grade_level && ! $q->folder)
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
                                        @if(!empty($q->focus_terms))
                                            <div class="inline-flex items-center gap-1 flex-wrap">
                                                @foreach($q->focus_terms as $fTerm)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-royal-purple-50 text-royal-purple-900 border border-royal-purple-200">
                                                        {{ $fTerm }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @elseif($q->focus)
                                            <span class="text-xs font-bold font-heading text-cloud-burst-700 truncate max-w-xs">{{ $q->focus }}</span>
                                        @endif
                                    </div>
                                    <p class="line-clamp-2 max-w-md text-sm">{{ $q->prompt }}</p>
                                    @if($q->support_title)
                                        <p class="text-xs text-royal-purple-700 mt-1 truncate max-w-md italic font-heading font-medium">Fonte: {{ $q->support_title }}</p>
                                    @endif
                                </td>

                                {{-- Coluna de Compartilhamento --}}
                                <td class="py-4 px-6 text-xs align-top">
                                    @if(($tab ?? 'minhas') === 'compartilhadas')
                                        <div class="flex items-center gap-3">
                                            <x-user-avatar :user="$q->user" :name="$q->user->name ?? 'Professor'" :email="$q->user->email ?? null" size="sm" />
                                            <div class="min-w-0">
                                                <div class="font-bold font-heading text-cloud-burst-950 truncate">{{ $q->user->name ?? 'Professor' }}</div>
                                                <div class="text-[11px] text-cloud-burst-600 truncate">{{ $q->user->email ?? '—' }}</div>
                                                <div class="mt-0.5">
                                                    @if($canEdit)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                            <x-lucide-edit-3 class="w-3 h-3 text-emerald-600" />
                                                            <span>Pode Editar</span>
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold font-heading bg-amber-100 text-amber-800 border border-amber-200">
                                                            <x-lucide-eye class="w-3 h-3 text-amber-600" />
                                                            <span>Apenas Visualização</span>
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        @if($q->shares->isNotEmpty())
                                            <button type="button" onclick="document.getElementById('modal-share-pergunta-{{ $q->id }}').classList.remove('hidden')"
                                                    class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-heading bg-indigo-50 text-indigo-800 border border-indigo-200 hover:bg-indigo-100 transition">
                                                <x-lucide-users class="w-3.5 h-3.5 text-indigo-600" />
                                                <span>{{ $q->shares->count() }} professor(es)</span>
                                            </button>
                                        @else
                                            <button type="button" onclick="document.getElementById('modal-share-pergunta-{{ $q->id }}').classList.remove('hidden')"
                                                    class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-heading bg-gray-50 text-gray-700 border border-gray-200 hover:bg-gray-100 transition">
                                                <x-lucide-lock class="w-3.5 h-3.5 text-gray-500" />
                                                <span>Privada</span>
                                            </button>
                                        @endif
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
                                    @if($isOwner)
                                        <button type="button" onclick="document.getElementById('modal-share-pergunta-{{ $q->id }}').classList.remove('hidden')"
                                                class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-indigo-700 hover:underline bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg border border-indigo-200 transition">
                                            <x-lucide-share-2 class="w-3.5 h-3.5" />
                                            <span>Compartilhar</span>
                                        </button>
                                    @endif

                                    @if(!$isOwner)
                                        <form action="{{ route('professor.perguntas.clone', $q) }}" method="POST" class="inline"
                                              data-confirm="Deseja clonar esta pergunta para o seu banco pessoal de questões?"
                                              data-confirm-title="Clonar Pergunta"
                                              data-confirm-ok="Clonar Pergunta"
                                              data-confirm-tone="primary">
                                            @csrf
                                            <button type="submit" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-broom-950 bg-broom-300 hover:bg-broom-400 px-3 py-1.5 rounded-lg border border-broom-400 transition shadow-2xs">
                                                <x-lucide-copy class="w-3.5 h-3.5" />
                                                <span>Clonar</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if($canEdit)
                                        <a href="{{ route('professor.perguntas.edit', $q) }}" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-royal-purple-800 hover:underline bg-royal-purple-50 hover:bg-royal-purple-100 px-3 py-1.5 rounded-lg border border-royal-purple-200 transition">
                                            <x-lucide-edit class="w-3.5 h-3.5" />
                                            <span>Editar</span>
                                        </a>
                                    @endif

                                    @if($isOwner)
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
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-cloud-burst-600">
                                    @if(($tab ?? 'minhas') === 'compartilhadas')
                                        Nenhuma pergunta compartilhada com você no momento.
                                    @else
                                        Nenhuma pergunta encontrada com os filtros atuais.
                                    @endif
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

        {{-- Modal Nova Pasta --}}
        <div id="modal-create-folder" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-cloud-burst-950/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-magnolia-200 space-y-4 animate-scale-in">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-lucide-folder-plus class="w-5 h-5 text-royal-purple-800" />
                        <h3 class="text-lg font-extrabold font-heading text-cloud-burst-950">Nova Pasta de Perguntas</h3>
                    </div>
                    <button type="button" onclick="document.getElementById('modal-create-folder').classList.add('hidden')"
                            class="cursor-pointer text-cloud-burst-400 hover:text-cloud-burst-700 p-1">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <form action="{{ route('professor.pastas.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="folder_name" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                            Nome da Pasta <span class="text-shocking-pink-600">*</span>
                        </label>
                        <input id="folder_name" name="name" type="text" required placeholder="Ex.: Simulado 1º Bimestre, Recuperação..."
                               class="w-full px-3.5 py-2.5 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                    </div>

                    <div>
                        <label for="folder_desc" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                            Descrição (opcional)
                        </label>
                        <textarea id="folder_desc" name="description" rows="2" placeholder="Finalidade ou orientações desta pasta..."
                                  class="w-full px-3.5 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" onclick="document.getElementById('modal-create-folder').classList.add('hidden')"
                                class="cursor-pointer px-4 py-2 border border-magnolia-300 rounded-xl text-xs font-bold font-heading text-cloud-burst-800 hover:bg-magnolia-50 transition">
                            Cancelar
                        </button>
                        <button type="submit" class="cursor-pointer px-5 py-2 bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-xs rounded-xl shadow-2xs transition">
                            Criar Pasta
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modais de Compartilhamento das Perguntas --}}
        @foreach($questions as $q)
            @if((int)$q->user_id === (int)auth()->id())
                <div id="modal-share-pergunta-{{ $q->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-cloud-burst-950/60 backdrop-blur-xs">
                    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-magnolia-200 space-y-5 animate-scale-in">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <x-lucide-share-2 class="w-5 h-5 text-royal-purple-800" />
                                <h3 class="text-lg font-extrabold font-heading text-cloud-burst-950">Compartilhar Pergunta</h3>
                            </div>
                            <button type="button" onclick="document.getElementById('modal-share-pergunta-{{ $q->id }}').classList.add('hidden')"
                                    class="cursor-pointer text-cloud-burst-400 hover:text-cloud-burst-700 p-1">
                                <x-lucide-x class="w-5 h-5" />
                            </button>
                        </div>

                        <div>
                            <h4 class="text-xs font-bold font-heading text-royal-purple-950 line-clamp-2">{{ $q->prompt }}</h4>
                            <p class="text-xs text-cloud-burst-700 mt-1">
                                Convide outros professores informando o e-mail deles. Se o professor não tiver cadastro, enviaremos um convite por e-mail com acesso automático à questão.
                            </p>
                        </div>

                        {{-- Formulário para Convidar --}}
                        <form action="{{ route('professor.perguntas.share', $q) }}" method="POST" class="space-y-3 p-4 bg-royal-purple-50/40 rounded-2xl border border-royal-purple-100">
                            @csrf
                            <div>
                                <label for="share_email_q_{{ $q->id }}" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                                    E-mail do Professor
                                </label>
                                <input id="share_email_q_{{ $q->id }}" name="email" type="email" required placeholder="professor@escola.com"
                                       class="w-full px-3.5 py-2.5 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <div class="grow">
                                    <label for="share_permission_q_{{ $q->id }}" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                                        Permissão de Acesso
                                    </label>
                                    <select id="share_permission_q_{{ $q->id }}" name="permission" class="w-full px-3.5 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white font-bold">
                                        <option value="view">Apenas Visualizar (Pode Clonar para seu banco)</option>
                                        <option value="edit">Pode Editar (Modificar a questão original)</option>
                                    </select>
                                </div>
                                <div class="shrink-0 self-end">
                                    <button type="submit" class="cursor-pointer px-4 py-2.5 bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-xs rounded-xl shadow-2xs transition">
                                        Enviar Convite
                                    </button>
                                </div>
                            </div>
                        </form>

                        {{-- Lista de Compartilhamentos Atuais --}}
                        <div class="space-y-2">
                            <span class="block text-xs font-bold font-heading text-cloud-burst-900 uppercase tracking-wider">
                                Professores com Acesso ({{ $q->shares->count() }})
                            </span>

                            @if($q->shares->isEmpty())
                                <p class="text-xs text-cloud-burst-600 italic py-2">Esta pergunta ainda não foi compartilhada com ninguém.</p>
                            @else
                                <div class="divide-y divide-magnolia-100 max-h-48 overflow-y-auto">
                                    @foreach($q->shares as $share)
                                        <div class="py-2.5 flex items-center justify-between gap-3 text-xs">
                                            <div class="flex items-center gap-2.5 grow min-w-0">
                                                <x-user-avatar :user="$share->user" :name="$share->user?->name ?? 'Professor Convidado'" :email="$share->email" size="sm" />
                                                <div class="grow min-w-0">
                                                    <div class="font-bold text-cloud-burst-950 truncate">{{ $share->user?->name ?? 'Professor Convidado' }}</div>
                                                    <div class="text-[11px] text-cloud-burst-600 truncate">{{ $share->email }}</div>
                                                    <div class="text-[10px] mt-0.5">
                                                        @if($share->permission === 'edit')
                                                            <span class="text-emerald-700 font-bold">Pode Editar</span>
                                                        @else
                                                            <span class="text-amber-700 font-bold">Apenas Visualização</span>
                                                        @endif
                                                        @if($share->user && $share->user->status === 'pending')
                                                            · <span class="text-indigo-600 font-medium">Convite pendente</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <form action="{{ route('professor.perguntas.share.destroy', [$q, $share]) }}" method="POST"
                                                  data-confirm="Revogar o acesso deste professor a esta pergunta?"
                                                  data-confirm-title="Revogar Acesso"
                                                  data-confirm-ok="Revogar"
                                                  data-confirm-tone="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="cursor-pointer text-xs text-rose-600 hover:text-rose-800 font-bold hover:underline">
                                                    Remover
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="pt-2 text-right">
                            <button type="button" onclick="document.getElementById('modal-share-pergunta-{{ $q->id }}').classList.add('hidden')"
                                    class="cursor-pointer px-4 py-2 border border-magnolia-300 rounded-xl text-xs font-bold font-heading text-cloud-burst-800 hover:bg-magnolia-50 transition">
                                Fechar
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</x-layouts.admin>
