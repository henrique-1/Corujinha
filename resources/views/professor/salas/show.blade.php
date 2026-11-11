<x-layouts.admin title="Painel da Sala · Corujinha AVALIA+">
    <div class="space-y-6">
        <div>
            <a href="{{ route('professor.salas.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-royal-purple-800 hover:text-royal-purple-900 hover:underline mb-2 transition">
                <x-lucide-arrow-left class="w-4 h-4" />
                <span>Voltar para Minhas Salas</span>
            </a>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-cloud-burst-950 font-heading tracking-tight">
                        {{ $classroom->name }}
                    </h1>
                    <p class="text-sm text-cloud-burst-900/70 mt-1">
                        Avaliação: <span class="font-bold text-cloud-burst-950">{{ $classroom->assessment->title }}</span> ({{ $totalQuestions }} questões)
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <form action="{{ route('professor.salas.toggle_status', $classroom) }}" method="POST">
                        @csrf
                        <button type="submit" class="cursor-pointer text-xs font-bold px-4 py-2.5 rounded-xl border transition flex items-center gap-2 {{ $classroom->is_active ? 'border-broom-400 text-broom-950 bg-broom-50 hover:bg-broom-100' : 'border-emerald-300 text-emerald-800 bg-emerald-50 hover:bg-emerald-100' }}">
                            @if($classroom->is_active)
                                <x-lucide-pause class="w-4 h-4 text-broom-800" />
                                <span>Fechar Sala para Respostas</span>
                            @else
                                <x-lucide-play class="w-4 h-4 text-emerald-700" />
                                <span>Reabrir Sala</span>
                            @endif
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Top Cards: Código e Configuração --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Big Code Card --}}
            <div class="bg-gradient-to-br from-royal-purple-950 to-royal-purple-900 rounded-2xl p-6 text-white text-center shadow-md flex flex-col justify-center items-center">
                <span class="text-xs font-bold uppercase tracking-wider text-royal-purple-200 mb-1">
                    Código de Entrada do Aluno
                </span>
                <span class="text-4xl md:text-5xl font-mono font-black tracking-widest text-broom-400 my-2 select-all font-heading">
                    {{ $classroom->code }}
                </span>
                <p class="text-[11px] text-royal-purple-200 mt-1">
                    Peça aos estudantes para acessarem <b>/sala</b> e digitarem este código.
                </p>
            </div>

            {{-- Status Card --}}
            <div class="bg-white rounded-2xl p-6 border border-royal-purple-200/60 shadow-xs flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-cloud-burst-900/60 uppercase tracking-wider block mb-2">Status da Sala</span>
                    <div class="flex items-center gap-2">
                        @if($classroom->is_active)
                            <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-lg font-bold text-emerald-700 font-heading">Aberta para Respostas</span>
                        @else
                            <span class="w-3 h-3 rounded-full bg-gray-400"></span>
                            <span class="text-lg font-bold text-gray-600 font-heading">Fechada</span>
                        @endif
                    </div>
                </div>
                <div class="pt-4 border-t border-royal-purple-100 mt-4 text-xs text-cloud-burst-900/60">
                    Criada em {{ $classroom->created_at->format('d/m/Y H:i') }}
                </div>
            </div>

            {{-- Review Mode Card --}}
            <div class="bg-white rounded-2xl p-6 border border-royal-purple-200/60 shadow-xs flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-cloud-burst-900/60 uppercase tracking-wider block mb-2">Revisão e Correção Coletiva</span>
                    @if($classroom->collective_review_enabled)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            <x-lucide-check class="w-3.5 h-3.5" />
                            <span>Ativa na Turma</span>
                        </span>
                        <p class="text-xs text-cloud-burst-900/70 mt-2">
                            Alunos revisam respostas e justificam mudanças antes de ver o gabarito.
                        </p>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700 border border-gray-300">
                            <x-lucide-x class="w-3.5 h-3.5" />
                            <span>Desativada</span>
                        </span>
                        <p class="text-xs text-cloud-burst-900/70 mt-2">
                            Alunos veem o gabarito e devolutivas imediatamente após a última questão.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Students Attempts Table --}}
        <div class="bg-white rounded-2xl border border-royal-purple-200/60 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-royal-purple-100 flex items-center justify-between">
                <div>
                    <h2 id="attempts-total-count" class="text-base font-bold text-cloud-burst-950 font-heading">
                        Respostas dos Estudantes ({{ $attempts->total() }})
                    </h2>
                    <p class="text-xs text-cloud-burst-900/60">Acompanhamento das tentativas registradas nesta sala</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-royal-purple-50/60 border-b border-royal-purple-100 text-cloud-burst-900/70 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-6">Estudante</th>
                            <th class="py-3.5 px-6">Status</th>
                            <th class="py-3.5 px-6">Início</th>
                            <th class="py-3.5 px-6">Conclusão</th>
                            <th class="py-3.5 px-6 text-center">Acertos</th>
                            <th class="py-3.5 px-6 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="attempts-table-body" class="divide-y divide-royal-purple-100/60">
                        @forelse($attempts as $att)
                            <tr id="attempt-row-{{ $att->id }}" class="hover:bg-royal-purple-50/30 transition">
                                <td class="py-4 px-6 font-bold text-cloud-burst-950">
                                    {{ $att->student_name }}
                                </td>
                                <td id="status-cell-{{ $att->id }}" class="py-4 px-6">
                                    @if($att->status === 'reviewed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Revisão Concluída
                                        </span>
                                    @elseif($att->status === 'submitted')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-royal-purple-100 text-royal-purple-800 border border-royal-purple-200">
                                            Submetido
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-broom-100 text-broom-950 border border-broom-300">
                                            Em andamento
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-cloud-burst-900/70 text-xs">
                                    {{ $att->started_at->format('d/m/Y H:i') }}
                                </td>
                                <td id="concluded-cell-{{ $att->id }}" class="py-4 px-6 text-cloud-burst-900/70 text-xs">
                                    {{ $att->reviewed_at ? $att->reviewed_at->format('d/m/Y H:i') : ($att->completed_at ? $att->completed_at->format('d/m/Y H:i') : '—') }}
                                </td>
                                <td id="score-cell-{{ $att->id }}" class="py-4 px-6 text-center font-bold text-royal-purple-800">
                                    {{ $att->score }} / {{ $totalQuestions }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('professor.salas.tentativa', [$classroom, $att]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-900 hover:bg-royal-purple-200 transition shadow-2xs">
                                        <x-lucide-eye class="w-3.5 h-3.5" />
                                        <span>Ver Respostas</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-attempts-row">
                                <td colspan="6" class="py-8 text-center text-cloud-burst-900/60">
                                    Nenhum aluno entrou nesta sala ainda. Projete o código <b class="text-royal-purple-800">{{ $classroom->code }}</b> para iniciar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($attempts->hasPages())
                <div class="p-4 border-t border-royal-purple-100">
                    {{ $attempts->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            window.__CLASSROOM_MONITOR__ = {
                classroomId: {{ $classroom->id }},
                totalAttempts: {{ $attempts->total() }},
                totalQuestions: {{ $totalQuestions }},
                attemptUrlPattern: "{{ route('professor.salas.tentativa', [$classroom, '__ATTEMPT_ID__']) }}"
            };
        </script>
    @endpush
</x-layouts.admin>
