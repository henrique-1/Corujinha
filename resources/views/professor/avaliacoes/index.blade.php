<x-layouts.admin title="Avaliações · Corujinha">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                    Avaliações Pedagógicas
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

        {{-- Abas de Navegação: Minhas Avaliações vs Compartilhadas Comigo --}}
        <div class="flex items-center gap-2 border-b border-magnolia-200">
            <a href="{{ route('professor.avaliacoes.index', ['tab' => 'minhas']) }}"
               class="cursor-pointer px-4 py-2.5 text-xs font-bold font-heading border-b-2 transition flex items-center gap-2 {{ ($tab ?? 'minhas') === 'minhas' ? 'border-royal-purple-800 text-royal-purple-950' : 'border-transparent text-cloud-burst-600 hover:text-royal-purple-800' }}">
                <x-lucide-folder class="w-4 h-4" />
                <span>Minhas Avaliações</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ ($tab ?? 'minhas') === 'minhas' ? 'bg-royal-purple-100 text-royal-purple-900 font-extrabold' : 'bg-gray-100 text-gray-700' }}">
                    {{ $myCount ?? 0 }}
                </span>
            </a>

            <a href="{{ route('professor.avaliacoes.index', ['tab' => 'compartilhadas']) }}"
               class="cursor-pointer px-4 py-2.5 text-xs font-bold font-heading border-b-2 transition flex items-center gap-2 {{ ($tab ?? 'minhas') === 'compartilhadas' ? 'border-royal-purple-800 text-royal-purple-950' : 'border-transparent text-cloud-burst-600 hover:text-royal-purple-800' }}">
                <x-lucide-share-2 class="w-4 h-4" />
                <span>Compartilhadas Comigo</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ ($tab ?? 'minhas') === 'compartilhadas' ? 'bg-royal-purple-100 text-royal-purple-900 font-extrabold' : 'bg-gray-100 text-gray-700' }}">
                    {{ $sharedCount ?? 0 }}
                </span>
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-magnolia-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-royal-purple-50/70 border-b border-magnolia-200 text-cloud-burst-800 text-xs font-bold font-heading uppercase tracking-wider">
                            <th class="py-3.5 px-6">Título da Avaliação</th>
                            @if(($tab ?? 'minhas') === 'compartilhadas')
                                <th class="py-3.5 px-6">Proprietário / Permissão</th>
                            @else
                                <th class="py-3.5 px-6">Compartilhamento</th>
                            @endif
                            <th class="py-3.5 px-6">Qtd. de Perguntas</th>
                            <th class="py-3.5 px-6">Criada em</th>
                            <th class="py-3.5 px-6 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-magnolia-100">
                        @forelse($assessments as $item)
                            @php
                                $isOwner = (int)$item->user_id === (int)auth()->id();
                                $userShare = !$isOwner ? $item->shares->first(fn($s) => (int)$s->user_id === (int)auth()->id() || mb_strtolower($s->email) === mb_strtolower(auth()->user()->email)) : null;
                                $canEdit = $isOwner || ($userShare && $userShare->permission === 'edit') || auth()->user()->isSuperUser();
                            @endphp
                            <tr class="hover:bg-royal-purple-50/30 transition">
                                <td class="py-4 px-6 font-bold font-heading text-cloud-burst-950">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span>{{ $item->title }}</span>
                                        @if($item->academic_period)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold font-heading bg-sky-blue-100 text-sky-blue-900 border border-sky-blue-200">
                                                {{ $item->academic_period }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($item->description)
                                        <p class="text-xs text-cloud-burst-600 font-normal mt-0.5">{{ $item->description }}</p>
                                    @endif

                                    @if($item->classrooms->isNotEmpty())
                                        <details class="mt-2 text-xs group font-sans font-normal">
                                            <summary class="cursor-pointer text-royal-purple-800 hover:text-royal-purple-900 hover:underline font-semibold flex items-center gap-1 select-none">
                                                <x-lucide-chevron-right class="w-3.5 h-3.5 transition group-open:rotate-90 text-royal-purple-600 shrink-0" />
                                                <span>Ver salas aplicadas ({{ $item->classrooms->count() }})</span>
                                            </summary>
                                            <div class="mt-2 pl-3 border-l-2 border-royal-purple-200 space-y-1.5 py-1">
                                                @foreach($item->classrooms as $room)
                                                    <div class="flex flex-wrap items-center justify-between gap-2 py-1 border-b border-magnolia-100 last:border-0 text-[11px]">
                                                        <div class="flex items-center gap-1.5">
                                                            <a href="{{ route('professor.salas.show', $room) }}" class="font-bold text-cloud-burst-900 hover:text-royal-purple-800 hover:underline">
                                                                {{ $room->name }}
                                                            </a>
                                                            <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-magnolia-100 text-cloud-burst-700">{{ $room->code }}</span>
                                                        </div>
                                                        <div class="flex flex-wrap items-center gap-2 text-cloud-burst-600">
                                                            <span>Criada: <strong class="text-cloud-burst-900">{{ $room->created_at->format('d/m/Y') }}</strong></span>
                                                            <span>•</span>
                                                            <span>Aplicação: <strong class="text-royal-purple-900 font-semibold">{{ $room->applicationDateFormatted() }}</strong></span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                </td>

                                {{-- Coluna de Compartilhamento --}}
                                <td class="py-4 px-6 text-xs">
                                    @if(($tab ?? 'minhas') === 'compartilhadas')
                                        <div class="flex items-center gap-3">
                                            <x-user-avatar :user="$item->user" :name="$item->user->name ?? 'Professor'" :email="$item->user->email ?? null" size="sm" />
                                            <div class="min-w-0">
                                                <div class="font-bold font-heading text-cloud-burst-950 truncate">{{ $item->user->name ?? 'Professor' }}</div>
                                                <div class="text-[11px] text-cloud-burst-600 truncate">{{ $item->user->email ?? '—' }}</div>
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
                                        @if($item->shares->isNotEmpty())
                                            <button type="button" onclick="document.getElementById('modal-share-avaliacao-{{ $item->id }}').classList.remove('hidden')"
                                                    class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-heading bg-indigo-50 text-indigo-800 border border-indigo-200 hover:bg-indigo-100 transition">
                                                <x-lucide-users class="w-3.5 h-3.5 text-indigo-600" />
                                                <span>{{ $item->shares->count() }} professor(es)</span>
                                            </button>
                                        @else
                                            <button type="button" onclick="document.getElementById('modal-share-avaliacao-{{ $item->id }}').classList.remove('hidden')"
                                                    class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-heading bg-gray-50 text-gray-700 border border-gray-200 hover:bg-gray-100 transition">
                                                <x-lucide-lock class="w-3.5 h-3.5 text-gray-500" />
                                                <span>Privada</span>
                                            </button>
                                        @endif
                                    @endif
                                </td>

                                <td class="py-4 px-6">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-800 border border-royal-purple-200">
                                            {{ $item->questions_count }} questão(ões)
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-magnolia-100 text-cloud-burst-800 border border-magnolia-200">
                                            {{ $item->classrooms_count }} sala(s)
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-cloud-burst-600 text-xs">
                                    {{ $item->created_at->format('d/m/Y') }}
                                </td>
                                <td class="py-4 px-6 text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('professor.avaliacoes.estatisticas', $item) }}" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-emerald-800 hover:underline bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg border border-emerald-200 transition">
                                        <x-lucide-bar-chart-3 class="w-3.5 h-3.5" />
                                        <span>Gráficos</span>
                                    </a>

                                    @if($isOwner)
                                        <button type="button" onclick="document.getElementById('modal-share-avaliacao-{{ $item->id }}').classList.remove('hidden')"
                                                class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-indigo-700 hover:underline bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg border border-indigo-200 transition">
                                            <x-lucide-share-2 class="w-3.5 h-3.5" />
                                            <span>Compartilhar</span>
                                        </button>
                                    @endif

                                    @if(!$isOwner)
                                        <form action="{{ route('professor.avaliacoes.clone', $item) }}" method="POST" class="inline"
                                              data-confirm="Deseja clonar esta avaliação para sua conta pessoal? Uma cópia com todas as questões será criada sob sua autoria."
                                              data-confirm-title="Clonar Avaliação"
                                              data-confirm-ok="Clonar Avaliação"
                                              data-confirm-tone="primary">
                                            @csrf
                                            <button type="submit" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-broom-950 bg-broom-300 hover:bg-broom-400 px-3 py-1.5 rounded-lg border border-broom-400 transition shadow-2xs">
                                                <x-lucide-copy class="w-3.5 h-3.5" />
                                                <span>Clonar</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if($canEdit)
                                        <a href="{{ route('professor.avaliacoes.edit', $item) }}" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-royal-purple-800 hover:underline bg-royal-purple-50 hover:bg-royal-purple-100 px-3 py-1.5 rounded-lg border border-royal-purple-200 transition">
                                            <x-lucide-edit class="w-3.5 h-3.5" />
                                            <span>Editar</span>
                                        </a>
                                    @endif

                                    @if($isOwner)
                                        <form action="{{ route('professor.avaliacoes.destroy', $item) }}" method="POST" class="inline"
                                              data-confirm="Deseja realmente excluir esta avaliação?"
                                              data-confirm-title="Excluir Avaliação"
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
                                <td colspan="5" class="py-8 text-center text-cloud-burst-600">
                                    @if(($tab ?? 'minhas') === 'compartilhadas')
                                        Nenhuma avaliação compartilhada com você no momento.
                                    @else
                                        Nenhuma avaliação criada ainda. Cadastre perguntas no banco e depois crie sua primeira avaliação!
                                    @endif
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

    {{-- Modais de Compartilhamento das Avaliações --}}
    @foreach($assessments as $item)
        @if((int)$item->user_id === (int)auth()->id())
            <div id="modal-share-avaliacao-{{ $item->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-cloud-burst-950/60 backdrop-blur-xs">
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-magnolia-200 space-y-5 animate-scale-in">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-lucide-share-2 class="w-5 h-5 text-royal-purple-800" />
                            <h3 class="text-lg font-extrabold font-heading text-cloud-burst-950">Compartilhar Avaliação</h3>
                        </div>
                        <button type="button" onclick="document.getElementById('modal-share-avaliacao-{{ $item->id }}').classList.add('hidden')"
                                class="cursor-pointer text-cloud-burst-400 hover:text-cloud-burst-700 p-1">
                            <x-lucide-x class="w-5 h-5" />
                        </button>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold font-heading text-royal-purple-950 truncate">{{ $item->title }}</h4>
                        <p class="text-xs text-cloud-burst-700 mt-0.5">
                            Convide outros professores informando o e-mail deles. Se o professor não tiver cadastro, enviaremos um convite por e-mail com acesso automático à avaliação.
                        </p>
                    </div>

                    {{-- Formulário para Convidar --}}
                    <form action="{{ route('professor.avaliacoes.share', $item) }}" method="POST" class="space-y-3 p-4 bg-royal-purple-50/40 rounded-2xl border border-royal-purple-100">
                        @csrf
                        <div>
                            <label for="share_email_{{ $item->id }}" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                                E-mail do Professor
                            </label>
                            <input id="share_email_{{ $item->id }}" name="email" type="email" required placeholder="professor@escola.com"
                                   class="w-full px-3.5 py-2.5 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <div class="grow">
                                <label for="share_permission_{{ $item->id }}" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                                    Permissão de Acesso
                                </label>
                                <select id="share_permission_{{ $item->id }}" name="permission" class="w-full px-3.5 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white font-bold">
                                    <option value="view">Apenas Visualizar (Pode Clonar e Ver Gráficos)</option>
                                    <option value="edit">Pode Editar (Modificar Questões/Configurações)</option>
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
                            Professores com Acesso ({{ $item->shares->count() }})
                        </span>

                        @if($item->shares->isEmpty())
                            <p class="text-xs text-cloud-burst-600 italic py-2">Esta avaliação ainda não foi compartilhada com ninguém.</p>
                        @else
                            <div class="divide-y divide-magnolia-100 max-h-48 overflow-y-auto">
                                @foreach($item->shares as $share)
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
                                        <form action="{{ route('professor.avaliacoes.share.destroy', [$item, $share]) }}" method="POST"
                                              data-confirm="Revogar o acesso deste professor a esta avaliação?"
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
                        <button type="button" onclick="document.getElementById('modal-share-avaliacao-{{ $item->id }}').classList.add('hidden')"
                                class="cursor-pointer px-4 py-2 border border-magnolia-300 rounded-xl text-xs font-bold font-heading text-cloud-burst-800 hover:bg-magnolia-50 transition">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</x-layouts.admin>
