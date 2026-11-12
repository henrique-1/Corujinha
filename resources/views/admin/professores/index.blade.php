<x-layouts.admin title="Gestão de Professores · Corujinha">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                    Professores Cadastrados
                </h1>
                <p class="text-sm text-cloud-burst-700 mt-1">
                    Gerencie os professores da instituição e acompanhe o status de ativação
                </p>
            </div>
            <a href="{{ route('admin.professores.create') }}" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-sm shadow-sm transition">
                <x-lucide-plus class="w-4 h-4" />
                <span>Cadastrar Novo Professor</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-magnolia-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-royal-purple-50/70 border-b border-magnolia-200 text-cloud-burst-800 text-xs font-bold font-heading uppercase tracking-wider">
                            <th class="py-3.5 px-6">Nome</th>
                            <th class="py-3.5 px-6">E-mail</th>
                            <th class="py-3.5 px-6">CPF</th>
                            <th class="py-3.5 px-6">Status da Conta</th>
                            <th class="py-3.5 px-6">MFA (TOTP)</th>
                            <th class="py-3.5 px-6 text-center whitespace-nowrap min-w-[240px]">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="professores-table-body" class="divide-y divide-magnolia-100">
                        @forelse($professores as $prof)
                            <tr id="professor-row-{{ $prof->uuid }}" class="hover:bg-royal-purple-50/30 transition">
                                <td class="py-4 px-6 font-bold font-heading text-cloud-burst-950">
                                    {{ $prof->name }}
                                </td>
                                <td class="py-4 px-6 text-cloud-burst-800">
                                    {{ $prof->email }}
                                </td>
                                <td class="py-4 px-6 text-cloud-burst-800 font-mono text-xs" id="prof-cpf-{{ $prof->uuid }}">
                                    {{ $prof->cpf ? \App\Rules\CpfRule::format($prof->cpf) : 'Pendente de preenchimento' }}
                                </td>
                                <td class="py-4 px-6" id="prof-status-{{ $prof->uuid }}">
                                    @if($prof->isActive())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <x-lucide-check-circle class="w-3.5 h-3.5 text-emerald-600" />
                                            <span>Ativo</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-heading bg-amber-100 text-amber-800 border border-amber-200">
                                            <x-lucide-clock class="w-3.5 h-3.5 text-amber-600" />
                                            <span>Convite Pendente</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6" id="prof-totp-{{ $prof->uuid }}">
                                    @if($prof->isTotpEnabled())
                                        <span class="inline-flex items-center gap-1 text-xs font-bold font-heading text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                            <x-lucide-smartphone class="w-3.5 h-3.5 text-emerald-600" />
                                            <span>Ativo</span>
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Inativo</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="inline-flex items-center justify-center gap-2.5">
                                        @if(! $prof->isActive())
                                            <form action="{{ route('admin.professores.resend_invite', $prof) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="cursor-pointer inline-flex items-center gap-1.5 text-xs font-bold font-heading text-royal-purple-800 bg-royal-purple-50 hover:bg-royal-purple-100 hover:text-royal-purple-900 px-3 py-1.5 rounded-lg border border-royal-purple-200 transition shadow-2xs whitespace-nowrap">
                                                    <x-lucide-send class="w-3.5 h-3.5 text-royal-purple-700" />
                                                    <span>Reenviar Convite</span>
                                                </button>
                                            </form>

                                            <form action="{{ route('admin.professores.destroy_invite', $prof) }}" method="POST" class="inline"
                                                  data-confirm="Tem certeza que deseja cancelar e remover o convite para este professor?"
                                                  data-confirm-title="Remover Convite"
                                                  data-confirm-ok="Remover Convite"
                                                  data-confirm-tone="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="cursor-pointer inline-flex items-center gap-1.5 text-xs font-bold font-heading text-rose-700 bg-rose-50 hover:bg-rose-100 hover:text-rose-900 px-3 py-1.5 rounded-lg border border-rose-200 transition shadow-2xs whitespace-nowrap">
                                                    <x-lucide-trash-2 class="w-3.5 h-3.5 text-rose-600" />
                                                    <span>Remover Convite</span>
                                                </button>
                                            </form>
                                        @endif

                                        @if($prof->isTotpEnabled())
                                            <form action="{{ route('admin.professores.disable_totp', $prof) }}" method="POST" class="inline"
                                                  data-confirm="Desabilitar o autenticador (TOTP) deste professor? O professor voltará a acessar apenas com senha."
                                                  data-confirm-title="Resetar Autenticador (TOTP)"
                                                  data-confirm-ok="Resetar TOTP"
                                                  data-confirm-tone="warning">
                                                @csrf
                                                <button type="submit" class="cursor-pointer inline-flex items-center gap-1.5 text-xs font-bold font-heading text-rose-700 bg-rose-50 hover:bg-rose-100 hover:text-rose-900 px-3 py-1.5 rounded-lg border border-rose-200 transition shadow-2xs whitespace-nowrap" title="Usar caso o professor tenha perdido o celular">
                                                    <x-lucide-key-round class="w-3.5 h-3.5 text-rose-600" />
                                                    <span>Resetar TOTP</span>
                                                </button>
                                            </form>
                                        @endif

                                        @if($prof->isActive() && ! $prof->isTotpEnabled())
                                            <span class="text-xs text-cloud-burst-400 font-medium">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-professores-row">
                                <td colspan="6" class="py-8 text-center text-cloud-burst-600">
                                    Nenhum professor cadastrado ainda. Clique em "Cadastrar Novo Professor" para enviar o primeiro convite.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($professores->hasPages())
                <div class="p-4 border-t border-magnolia-200">
                    {{ $professores->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
