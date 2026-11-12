<x-layouts.admin title="Salas de Aula · Corujinha">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                    Minhas Salas de Aula
                </h1>
                <p class="text-sm text-cloud-burst-700 mt-1">
                    Crie salas com código exclusivo para os estudantes responderem às avaliações
                </p>
            </div>
            <a href="{{ route('professor.salas.create') }}" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-sm shadow-sm transition">
                <x-lucide-plus class="w-4 h-4" />
                <span>Nova Sala de Aula</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-magnolia-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-royal-purple-50/70 border-b border-magnolia-200 text-cloud-burst-800 text-xs font-bold font-heading uppercase tracking-wider">
                            <th class="py-3.5 px-6">Código de Acesso</th>
                            <th class="py-3.5 px-6">Nome da Sala / Turma</th>
                            <th class="py-3.5 px-6">Avaliação Aplicada</th>
                            <th class="py-3.5 px-6">Revisão Coletiva</th>
                            <th class="py-3.5 px-6">Respostas</th>
                            <th class="py-3.5 px-6 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="classrooms-table-body" class="divide-y divide-magnolia-100">
                        @forelse($classrooms as $sala)
                            <tr id="classroom-row-{{ $sala->id }}" class="hover:bg-royal-purple-50/30 transition">
                                <td class="py-4 px-6 font-mono font-extrabold text-base text-royal-purple-800">
                                    <span class="px-2.5 py-1 rounded-lg bg-royal-purple-100 border border-royal-purple-200">
                                        {{ $sala->code }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 font-bold font-heading text-cloud-burst-950">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span>{{ $sala->name }}</span>
                                        @if(! $sala->is_active)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold font-heading bg-gray-100 text-gray-700 border border-gray-300">
                                                <x-lucide-lock class="w-3 h-3" />
                                                <span>Fechada</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-cloud-burst-800">
                                    {{ $sala->assessment->title }}
                                </td>
                                <td class="py-4 px-6">
                                    @if($sala->collective_review_enabled)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <x-lucide-check-circle class="w-3.5 h-3.5 text-emerald-600" />
                                            <span>Ativa (até {{ $sala->effectiveMaxReviewChanges() }} alt.)</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-gray-100 text-gray-600 border border-gray-200">
                                            Desativada (Direto ao Resultado)
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6">
                                    <span class="text-xs font-bold font-heading text-cloud-burst-800 flex items-center gap-1">
                                        <x-lucide-users class="w-3.5 h-3.5 text-royal-purple-800" />
                                        <span id="classroom-students-count-{{ $sala->id }}">{{ $sala->student_attempts_count }} aluno(s)</span>
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        <a href="{{ route('professor.salas.show', $sala) }}" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-royal-purple-800 hover:underline bg-royal-purple-50 hover:bg-royal-purple-100 px-3 py-1.5 rounded-lg border border-royal-purple-200 transition">
                                            <x-lucide-bar-chart-2 class="w-3.5 h-3.5" />
                                            <span>Painel</span>
                                        </a>
                                        <form action="{{ route('professor.salas.devolutiva.enter', $sala) }}" method="POST" class="inline"
                                              @if($sala->is_active)
                                                  data-confirm="Ao entrar na Devolutiva, a sala será fechada definitivamente para novas respostas e não poderá mais ser reaberta. Deseja continuar?"
                                                  data-confirm-title="Entrar na Devolutiva"
                                                  data-confirm-ok="Fechar Sala e Abrir Devolutiva"
                                                  data-confirm-tone="primary"
                                              @endif>
                                            @csrf
                                            <button type="submit" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-white bg-royal-purple-800 hover:bg-royal-purple-900 px-3 py-1.5 rounded-lg shadow-2xs transition">
                                                <x-lucide-presentation class="w-3.5 h-3.5" />
                                                <span>Devolutiva</span>
                                            </button>
                                        </form>
                                        <a href="{{ route('professor.salas.edit', $sala) }}" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-cloud-burst-800 hover:bg-magnolia-100 bg-white px-3 py-1.5 rounded-lg border border-magnolia-300 transition">
                                            <x-lucide-edit class="w-3.5 h-3.5" />
                                            <span>Editar</span>
                                        </a>
                                        <form action="{{ route('professor.salas.destroy', $sala) }}" method="POST" class="inline"
                                              data-confirm="Deseja realmente excluir esta sala de aula? Todos os registros vinculados serão removidos."
                                              data-confirm-title="Excluir Sala de Aula"
                                              data-confirm-ok="Excluir Sala"
                                              data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold font-heading text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg border border-rose-200 transition">
                                                <x-lucide-trash-2 class="w-3.5 h-3.5" />
                                                <span>Excluir</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-classrooms-row">
                                <td colspan="6" class="py-8 text-center text-cloud-burst-600">
                                    Nenhuma sala criada ainda. Clique em "Nova Sala de Aula" para gerar o código da sua primeira turma.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($classrooms->hasPages())
                <div class="p-4 border-t border-magnolia-200">
                    {{ $classrooms->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            window.__CLASSROOMS_LIST_CONFIG__ = {
                userId: {{ auth()->id() }}
            };
        </script>
    @endpush
</x-layouts.admin>
