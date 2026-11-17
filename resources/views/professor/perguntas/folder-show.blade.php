<x-layouts.admin title="{{ $pasta->name }} · Pasta de Perguntas · Corujinha">
    <div class="space-y-6">
        <div>
            <a href="{{ route('professor.perguntas.index') }}" class="text-xs font-bold font-heading text-royal-purple-800 hover:underline mb-2 inline-flex items-center gap-1">
                <x-lucide-arrow-left class="w-3.5 h-3.5" />
                <span>Voltar ao Banco de Perguntas</span>
            </a>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-1">
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <div class="w-10 h-10 rounded-2xl bg-royal-purple-100 text-royal-purple-800 flex items-center justify-center shrink-0">
                            <x-lucide-folder class="w-5 h-5" />
                        </div>
                        <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                            {{ $pasta->name }}
                        </h1>
                        @if($isOwner)
                            @if($pasta->visibility === 'shared')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-indigo-100 text-indigo-800 border border-indigo-200">
                                    <x-lucide-users class="w-3.5 h-3.5 text-indigo-600" />
                                    <span>Compartilhada ({{ $pasta->shares->count() }})</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-gray-100 text-gray-700 border border-gray-300">
                                    <x-lucide-lock class="w-3.5 h-3.5 text-gray-600" />
                                    <span>Privada</span>
                                </span>
                            @endif
                        @else
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-900 border border-royal-purple-200">
                                <x-user-avatar :user="$pasta->user" :name="$pasta->user->name" :email="$pasta->user->email" size="xs" />
                                <span>Compartilhada por {{ $pasta->user->name }} ({{ $pasta->user->email }})</span>
                            </div>
                            @if($canEdit)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <x-lucide-edit-3 class="w-3.5 h-3.5 text-emerald-600" />
                                    <span>Pode Editar</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-amber-100 text-amber-800 border border-amber-200">
                                    <x-lucide-eye class="w-3.5 h-3.5 text-amber-600" />
                                    <span>Apenas Visualização</span>
                                </span>
                            @endif
                        @endif
                    </div>
                    @if($pasta->description)
                        <p class="text-sm text-cloud-burst-700 mt-1.5">{{ $pasta->description }}</p>
                    @endif
                </div>

                {{-- Ações Principais da Pasta --}}
                <div class="flex items-center gap-2 flex-wrap">
                    {{-- Botão Clonar para Minha Conta (disponível para todos que acessam) --}}
                    <form action="{{ route('professor.pastas.clone', $pasta) }}" method="POST" class="inline"
                          data-confirm="Deseja clonar esta pasta para sua conta? Uma cópia exata de todas as perguntas será criada sob sua autoria."
                          data-confirm-title="Clonar Pasta de Perguntas"
                          data-confirm-ok="Clonar Pasta"
                          data-confirm-tone="primary">
                        @csrf
                        <button type="submit" class="cursor-pointer inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-broom-400 hover:bg-broom-500 text-broom-950 font-bold font-heading text-xs shadow-2xs transition">
                            <x-lucide-copy class="w-4 h-4 text-broom-950" />
                            <span>Clonar Pasta para Minha Conta</span>
                        </button>
                    </form>

                    @if($isOwner)
                        {{-- Botão Compartilhar --}}
                        <button type="button" onclick="document.getElementById('modal-share-folder').classList.remove('hidden')"
                                class="cursor-pointer inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-magnolia-50 text-cloud-burst-950 border border-magnolia-300 font-bold font-heading text-xs shadow-2xs transition">
                            <x-lucide-share-2 class="w-4 h-4 text-royal-purple-800" />
                            <span>Compartilhar ({{ $pasta->shares->count() }})</span>
                        </button>

                        {{-- Botão Excluir Pasta --}}
                        <form action="{{ route('professor.pastas.destroy', $pasta) }}" method="POST" class="inline"
                              data-confirm="Deseja excluir esta pasta? As perguntas não serão excluídas do sistema."
                              data-confirm-title="Excluir Pasta"
                              data-confirm-ok="Excluir"
                              data-confirm-tone="danger">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cursor-pointer p-2 rounded-xl text-rose-600 hover:bg-rose-50 border border-rose-200 transition" title="Excluir Pasta">
                                <x-lucide-trash-2 class="w-4 h-4" />
                            </button>
                        </form>
                    @endif

                    @if($canEdit)
                        <button type="button" onclick="document.getElementById('modal-add-questions').classList.remove('hidden')"
                                class="cursor-pointer inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-magnolia-50 text-cloud-burst-950 border border-magnolia-300 font-bold font-heading text-xs shadow-2xs transition">
                            <x-lucide-folder-input class="w-4 h-4 text-royal-purple-800" />
                            <span>Adicionar / Mover Perguntas</span>
                        </button>

                        <a href="{{ route('professor.perguntas.create', ['folder_id' => $pasta->uuid]) }}"
                           class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-xs shadow-2xs transition">
                            <x-lucide-plus class="w-4 h-4" />
                            <span>Nova Pergunta nesta Pasta</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Filtros de Perguntas da Pasta --}}
        <div class="bg-white rounded-2xl p-4 border border-magnolia-200 shadow-xs">
            <form action="{{ route('professor.pastas.show', $pasta) }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar nas perguntas desta pasta..."
                           class="w-full px-3 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                </div>
                <div>
                    <select name="area" class="w-full px-3 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                        <option value="">Todas as áreas</option>
                        @foreach($areas as $area)
                            <option value="{{ $area }}" {{ request('area') === $area ? 'selected' : '' }}>{{ $area }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="grade_level" class="w-full px-3 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                        <option value="">Todas as turmas</option>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade }}" {{ request('grade_level') === $grade ? 'selected' : '' }}>{{ $grade }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="cursor-pointer grow px-4 py-2 bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-xs rounded-xl transition">
                        Filtrar
                    </button>
                    @if(request()->hasAny(['search', 'area', 'grade_level']))
                        <a href="{{ route('professor.pastas.show', $pasta) }}" class="cursor-pointer px-3 py-2 border border-magnolia-300 hover:bg-magnolia-50 text-cloud-burst-800 font-bold font-heading text-xs rounded-xl transition">
                            Limpar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Lista de Perguntas da Pasta --}}
        <div class="space-y-4">
            @forelse($questions as $q)
                <div class="bg-white rounded-2xl p-5 border border-magnolia-200 shadow-xs hover:border-royal-purple-300 transition space-y-3">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1.5 grow">
                            <div class="flex items-center gap-2 flex-wrap">
                                @if($q->area)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold font-heading bg-royal-purple-100 text-royal-purple-900 border border-royal-purple-200">
                                        {{ $q->area }}
                                    </span>
                                @endif
                                @if($q->grade_level)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold font-heading bg-broom-100 text-broom-950 border border-broom-200">
                                        {{ $q->grade_level }}
                                    </span>
                                @endif
                                @if(!empty($q->focus_terms))
                                    @foreach($q->focus_terms as $fTerm)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-royal-purple-50 text-royal-purple-900 border border-royal-purple-200">
                                            {{ $fTerm }}
                                        </span>
                                    @endforeach
                                @elseif($q->focus)
                                    <span class="text-xs font-bold font-heading text-cloud-burst-700">
                                        {{ $q->focus }}
                                    </span>
                                @endif
                            </div>
                            <h3 class="text-sm font-bold font-heading text-cloud-burst-950 leading-snug">
                                {{ $q->prompt }}
                            </h3>
                        </div>

                        @if($canEdit)
                            <div class="flex items-center gap-1 shrink-0">
                                @if($q->user_id === Auth::id())
                                    <a href="{{ route('professor.perguntas.edit', $q) }}" class="cursor-pointer p-2 rounded-lg text-cloud-burst-800 hover:bg-royal-purple-50 transition" title="Editar Pergunta">
                                        <x-lucide-edit class="w-4 h-4" />
                                    </a>
                                @endif
                                <form action="{{ route('professor.pastas.remove-question', [$pasta, $q]) }}" method="POST" class="inline"
                                      data-confirm="Deseja remover esta pergunta desta pasta? A pergunta continuará existindo no banco de perguntas (na raiz)."
                                      data-confirm-title="Remover Pergunta da Pasta"
                                      data-confirm-ok="Remover da Pasta"
                                      data-confirm-tone="warning">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="cursor-pointer p-2 rounded-lg text-cloud-burst-500 hover:text-rose-600 hover:bg-rose-50 transition" title="Remover desta pasta">
                                        <x-lucide-folder-minus class="w-4 h-4" />
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                    {{-- Alternativas da Pergunta --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2 pt-2 border-t border-magnolia-100 text-xs">
                        @foreach($q->options as $opt)
                            <div class="p-2.5 rounded-xl border {{ $opt->letter === $q->correct_option ? 'border-emerald-300 bg-emerald-50/50' : 'border-magnolia-200 bg-magnolia-50/20' }}">
                                <span class="font-bold font-heading {{ $opt->letter === $q->correct_option ? 'text-emerald-700' : 'text-cloud-burst-900' }}">
                                    {{ $opt->letter }})
                                </span>
                                <span class="text-cloud-burst-800 line-clamp-2 mt-0.5">{{ $opt->text }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="p-8 rounded-2xl bg-white border border-magnolia-200 text-center space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-royal-purple-100 text-royal-purple-800 flex items-center justify-center">
                        <x-lucide-folder-open class="w-6 h-6" />
                    </div>
                    <h3 class="text-base font-bold font-heading text-cloud-burst-950">Nenhuma pergunta nesta pasta ainda</h3>
                    <p class="text-xs text-cloud-burst-700 max-w-md mx-auto">
                        @if($canEdit)
                            Cadastre perguntas diretamente dentro desta pasta ou mova perguntas existentes para cá.
                        @else
                            O proprietário ainda não adicionou perguntas a esta pasta.
                        @endif
                    </p>
                    @if($canEdit)
                        <div class="flex items-center justify-center gap-2 flex-wrap pt-2">
                            <button type="button" onclick="document.getElementById('modal-add-questions').classList.remove('hidden')"
                                    class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-royal-purple-800 text-white font-bold font-heading text-xs shadow-sm hover:bg-royal-purple-900 transition">
                                <x-lucide-folder-input class="w-4 h-4" />
                                <span>Adicionar Perguntas do Banco</span>
                            </button>
                            <a href="{{ route('professor.perguntas.create', ['folder_id' => $pasta->uuid]) }}"
                               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-magnolia-300 text-cloud-burst-950 font-bold font-heading text-xs shadow-2xs hover:bg-magnolia-50 transition">
                                <x-lucide-plus class="w-4 h-4" />
                                <span>Criar Nova Pergunta nesta Pasta</span>
                            </a>
                        </div>
                    @endif
                </div>
            @endforelse

            <div class="pt-2">
                {{ $questions->links() }}
            </div>
        </div>
    </div>

    {{-- Modal de Compartilhamento (Apenas para o Dono) --}}
    @if($isOwner)
        <div id="modal-share-folder" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-cloud-burst-950/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-magnolia-200 space-y-5 animate-scale-in">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-lucide-share-2 class="w-5 h-5 text-royal-purple-800" />
                        <h3 class="text-lg font-extrabold font-heading text-cloud-burst-950">Compartilhar Pasta</h3>
                    </div>
                    <button type="button" onclick="document.getElementById('modal-share-folder').classList.add('hidden')"
                            class="cursor-pointer text-cloud-burst-400 hover:text-cloud-burst-700 p-1">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                <p class="text-xs text-cloud-burst-700">
                    Convide outros professores informando o e-mail deles. Se o professor ainda não tiver cadastro no Corujinha, enviaremos um convite seguro com acesso automático à pasta.
                </p>

                {{-- Formulário para Convidar --}}
                <form action="{{ route('professor.pastas.share', $pasta) }}" method="POST" class="space-y-3 p-4 bg-royal-purple-50/40 rounded-2xl border border-royal-purple-100">
                    @csrf
                    <div>
                        <label for="share_email" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                            E-mail do Professor
                        </label>
                        <input id="share_email" name="email" type="email" required placeholder="professor@escola.com"
                               class="w-full px-3.5 py-2.5 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <div class="grow">
                            <label for="share_permission" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                                Permissão de Acesso
                            </label>
                            <select id="share_permission" name="permission" class="w-full px-3.5 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white font-bold">
                                <option value="view">Apenas Visualizar (Pode Clonar)</option>
                                <option value="edit">Pode Editar (Adicionar/Modificar)</option>
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
                        Pessoas com Acesso ({{ $pasta->shares->count() }})
                    </span>

                    @if($pasta->shares->isEmpty())
                        <p class="text-xs text-cloud-burst-600 italic py-2">Esta pasta ainda não foi compartilhada com ninguém.</p>
                    @else
                        <div class="divide-y divide-magnolia-100 max-h-48 overflow-y-auto">
                            @foreach($pasta->shares as $share)
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
                                    <form action="{{ route('professor.pastas.share.destroy', [$pasta, $share]) }}" method="POST"
                                          data-confirm="Revogar o acesso deste professor a esta pasta?"
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
                    <button type="button" onclick="document.getElementById('modal-share-folder').classList.add('hidden')"
                            class="cursor-pointer px-4 py-2 border border-magnolia-300 rounded-xl text-xs font-bold font-heading text-cloud-burst-800 hover:bg-magnolia-50 transition">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal de Adicionar / Mover Perguntas para esta Pasta --}}
    @if($canEdit)
        <div id="modal-add-questions" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-cloud-burst-950/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-magnolia-200 space-y-4 animate-scale-in max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between pb-3 border-b border-magnolia-200 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="p-2 rounded-xl bg-royal-purple-100 text-royal-purple-800">
                            <x-lucide-folder-input class="w-5 h-5" />
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold font-heading text-cloud-burst-950">Adicionar / Mover Perguntas para a Pasta</h3>
                            <p class="text-xs text-cloud-burst-700">Selecione as perguntas do seu banco para mover para <strong>{{ $pasta->name }}</strong></p>
                        </div>
                    </div>
                    <button type="button" onclick="document.getElementById('modal-add-questions').classList.add('hidden')"
                            class="cursor-pointer text-cloud-burst-400 hover:text-cloud-burst-700 p-1">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>

                @if($availableQuestions->isEmpty())
                    <div class="p-8 text-center space-y-3 my-auto">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-royal-purple-100 text-royal-purple-800 flex items-center justify-center">
                            <x-lucide-library class="w-6 h-6" />
                        </div>
                        <h4 class="text-sm font-bold font-heading text-cloud-burst-950">Nenhuma outra pergunta disponível</h4>
                        <p class="text-xs text-cloud-burst-700 max-w-md mx-auto">
                            Todas as perguntas do seu acervo já estão nesta pasta ou você ainda não cadastrou perguntas adicionais.
                        </p>
                        <div class="pt-2">
                            <a href="{{ route('professor.perguntas.create', ['folder_id' => $pasta->uuid]) }}"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-royal-purple-800 text-white font-bold font-heading text-xs shadow-2xs hover:bg-royal-purple-900 transition">
                                <x-lucide-plus class="w-4 h-4" />
                                <span>Criar Nova Pergunta</span>
                            </a>
                        </div>
                    </div>
                @else
                    <form action="{{ route('professor.pastas.add-questions', $pasta) }}" method="POST" class="space-y-4 flex flex-col min-h-0 grow">
                        @csrf

                        {{-- Barra de Busca e Seleção Rápida --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 shrink-0">
                            <div class="relative grow">
                                <x-lucide-search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-cloud-burst-400" />
                                <input type="text" id="search-available-questions" placeholder="Filtrar por enunciado, área, turma ou descritor..."
                                       class="w-full pl-9 pr-3.5 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                            </div>

                            <div class="flex items-center gap-3 shrink-0 justify-between sm:justify-end">
                                <label class="inline-flex items-center gap-2 text-xs font-bold font-heading text-cloud-burst-800 cursor-pointer select-none">
                                    <input type="checkbox" id="cb-select-all-available" onchange="toggleSelectAllAvailable(this.checked)"
                                           class="rounded text-royal-purple-800 focus:ring-royal-purple-800 w-4 h-4 cursor-pointer">
                                    <span>Selecionar Todas</span>
                                </label>
                                <span id="selected-questions-count" class="text-xs font-bold text-royal-purple-800 bg-royal-purple-50 px-2.5 py-1 rounded-lg border border-royal-purple-200">
                                    0 selecionada(s)
                                </span>
                            </div>
                        </div>

                        {{-- Lista com rolagem de perguntas disponíveis --}}
                        <div id="available-questions-list" class="space-y-2 overflow-y-auto pr-1 grow max-h-[50vh]">
                            @foreach($availableQuestions as $availQ)
                                <label class="available-question-item flex items-start gap-3 p-3 rounded-xl border border-magnolia-200 hover:border-royal-purple-300 hover:bg-royal-purple-50/20 transition cursor-pointer select-none"
                                       data-search="{{ strtolower($availQ->prompt . ' ' . $availQ->area . ' ' . $availQ->grade_level . ' ' . $availQ->focus . ' ' . ($availQ->folder?->name ?? '')) }}">
                                    <input type="checkbox" name="question_uuids[]" value="{{ $availQ->uuid }}" onchange="updateSelectedCount()"
                                           class="available-question-cb mt-1 rounded text-royal-purple-800 focus:ring-royal-purple-800 w-4 h-4 shrink-0 cursor-pointer">
                                    <div class="grow space-y-1 min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            @if($availQ->area)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-royal-purple-100 text-royal-purple-900">
                                                    {{ $availQ->area }}
                                                </span>
                                            @endif
                                            @if($availQ->grade_level)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-broom-100 text-broom-950 border border-broom-200">
                                                    {{ $availQ->grade_level }}
                                                </span>
                                            @endif
                                            @if(!empty($availQ->focus_terms))
                                                @foreach($availQ->focus_terms as $fTerm)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-royal-purple-50 text-royal-purple-900 border border-royal-purple-200 truncate max-w-xs">
                                                        {{ $fTerm }}
                                                    </span>
                                                @endforeach
                                            @elseif($availQ->focus)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-magnolia-100 text-cloud-burst-800 truncate max-w-xs">
                                                    {{ $availQ->focus }}
                                                </span>
                                            @endif
                                            @if($availQ->folder)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-heading bg-amber-50 text-amber-900 border border-amber-200">
                                                    <x-lucide-folder class="w-3 h-3 text-amber-800" />
                                                    <span>Pasta atual: {{ $availQ->folder->name }}</span>
                                                </span>
                                            @else
                                                <span class="text-[10px] text-gray-400 font-medium italic">
                                                    (Sem pasta / Raiz)
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs font-medium text-cloud-burst-950 line-clamp-2">
                                            {{ $availQ->prompt }}
                                        </p>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        {{-- Rodapé do Modal --}}
                        <div class="flex items-center justify-between gap-3 pt-3 border-t border-magnolia-200 shrink-0">
                            <button type="button" onclick="document.getElementById('modal-add-questions').classList.add('hidden')"
                                    class="cursor-pointer px-4 py-2 border border-magnolia-300 hover:bg-magnolia-50 text-cloud-burst-800 font-bold font-heading text-xs rounded-xl transition">
                                Cancelar
                            </button>
                            <button type="submit" id="btn-submit-move-questions" disabled
                                    class="cursor-pointer inline-flex items-center gap-1.5 px-4 py-2 bg-royal-purple-800 hover:bg-royal-purple-900 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold font-heading text-xs rounded-xl shadow-2xs transition">
                                <x-lucide-folder-input class="w-4 h-4" />
                                <span>Mover Perguntas Selecionadas</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('search-available-questions');
                if (searchInput) {
                    searchInput.addEventListener('input', function () {
                        const term = this.value.toLowerCase().trim();
                        document.querySelectorAll('.available-question-item').forEach(el => {
                            const searchData = (el.getAttribute('data-search') || '').toLowerCase();
                            el.style.display = searchData.includes(term) ? '' : 'none';
                        });
                    });
                }
            });

            function toggleSelectAllAvailable(checked) {
                document.querySelectorAll('.available-question-cb').forEach(cb => {
                    const parent = cb.closest('.available-question-item');
                    if (parent && parent.style.display !== 'none') {
                        cb.checked = checked;
                    }
                });
                updateSelectedCount();
            }

            function updateSelectedCount() {
                const count = document.querySelectorAll('.available-question-cb:checked').length;
                const countBadge = document.getElementById('selected-questions-count');
                const submitBtn = document.getElementById('btn-submit-move-questions');
                if (countBadge) {
                    countBadge.textContent = `${count} selecionada(s)`;
                }
                if (submitBtn) {
                    submitBtn.disabled = count === 0;
                }
            }
        </script>
    @endif
</x-layouts.admin>
