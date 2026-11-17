<x-layouts.admin title="Meu Perfil · Corujinha">
    <div class="max-w-5xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                Meu Perfil
            </h1>
            <p class="text-sm text-cloud-burst-700 mt-1">
                Visualize seus dados cadastrais, gerencie suas informações e configure a segurança da sua conta
            </p>
        </div>

        {{-- Abas de Navegação do Perfil --}}
        <div class="flex items-center gap-2 border-b border-magnolia-200">
            <button type="button" data-profile-tab="dados"
                    class="cursor-pointer px-4 py-3 text-xs font-bold font-heading border-b-2 transition flex items-center gap-2 {{ ($activeTab ?? 'dados') === 'dados' ? 'border-royal-purple-800 text-royal-purple-950 bg-royal-purple-50/60 rounded-t-xl' : 'border-transparent text-cloud-burst-600 hover:text-royal-purple-800 hover:bg-magnolia-50/50 rounded-t-xl' }}">
                <x-lucide-user class="w-4 h-4 text-royal-purple-800" />
                <span>Dados Pessoais & E-mail</span>
            </button>

            <button type="button" data-profile-tab="seguranca"
                    class="cursor-pointer px-4 py-3 text-xs font-bold font-heading border-b-2 transition flex items-center gap-2 {{ ($activeTab ?? 'dados') === 'seguranca' ? 'border-royal-purple-800 text-royal-purple-950 bg-royal-purple-50/60 rounded-t-xl' : 'border-transparent text-cloud-burst-600 hover:text-royal-purple-800 hover:bg-magnolia-50/50 rounded-t-xl' }}">
                <x-lucide-shield-check class="w-4 h-4 text-royal-purple-800" />
                <span>Segurança & MFA</span>
                @if($user->isTotpEnabled())
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        TOTP Ativo
                    </span>
                @endif
                @if($credentials->isNotEmpty())
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-royal-purple-100 text-royal-purple-800 border border-royal-purple-300">
                        {{ $credentials->count() }} Passkey(s)
                    </span>
                @endif
            </button>
        </div>

        {{-- Painel 1: Dados Pessoais & E-mail --}}
        <div id="panel-dados" class="{{ ($activeTab ?? 'dados') === 'dados' ? '' : 'hidden' }}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Coluna Esquerda: Foto de Perfil (Avatar) --}}
                <div class="md:col-span-1 space-y-6">
                    <div class="bg-white rounded-3xl p-6 border border-magnolia-200 shadow-xs flex flex-col items-center text-center space-y-4">
                        <div class="relative group">
                            <x-user-avatar :user="$user" size="xl" class="shadow-md" />
                        </div>

                        <div>
                            <h2 class="text-base font-extrabold font-heading text-cloud-burst-950">{{ $user->name }}</h2>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold font-heading bg-royal-purple-100 text-royal-purple-900 border border-royal-purple-200 mt-1">
                                {{ $user->isSuperAdmin() ? 'Super-Usuário' : 'Docente / Professor' }}
                            </span>
                            @if($user->institution)
                                <p class="text-xs text-cloud-burst-700 font-medium mt-1">{{ $user->institution }}</p>
                            @endif
                        </div>

                        {{-- Form de Upload de Foto --}}
                        <form action="{{ route('profile.avatar.update') }}" method="POST" enctype="multipart/form-data" class="w-full pt-3 border-t border-magnolia-200 space-y-3">
                            @csrf
                            <div class="space-y-1">
                                <label for="avatar_input" class="cursor-pointer block w-full py-2 px-3 border border-dashed border-royal-purple-300 rounded-xl text-xs font-bold font-heading text-royal-purple-800 hover:bg-royal-purple-50 transition">
                                    <span id="avatar_label_text">Selecionar Nova Foto</span>
                                    <input id="avatar_input" type="file" name="avatar" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="document.getElementById('avatar_label_text').textContent = this.files[0]?.name || 'Selecionar Nova Foto'; document.getElementById('btn-save-avatar').classList.remove('hidden');">
                                </label>
                                <p class="text-[10px] text-cloud-burst-600">JPG, PNG ou WEBP até 4 MB.</p>
                            </div>

                            <button id="btn-save-avatar" type="submit" class="hidden cursor-pointer w-full py-2 px-3 bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-xs rounded-xl shadow-2xs transition">
                                Salvar Foto
                            </button>
                        </form>

                        @if($user->avatar_path)
                            <form action="{{ route('profile.avatar.destroy') }}" method="POST" class="w-full">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="cursor-pointer text-xs font-bold text-rose-600 hover:text-rose-800 hover:underline transition">
                                    Remover Foto Atual
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Coluna Direita: Dados Cadastrais & E-mail --}}
                <div class="md:col-span-2 space-y-6">
                    {{-- Card de Dados Pessoais --}}
                    <div class="bg-white rounded-3xl p-6 md:p-8 border border-magnolia-200 shadow-xs space-y-5">
                        <div class="flex items-center gap-2 pb-3 border-b border-magnolia-200">
                            <x-lucide-user class="w-5 h-5 text-royal-purple-800" />
                            <h3 class="text-base font-extrabold font-heading text-cloud-burst-950">Dados do Docente</h3>
                        </div>

                        <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label for="name" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                                    Nome Completo <span class="text-shocking-pink-600">*</span>
                                </label>
                                <input id="name" name="name" type="text" required value="{{ old('name', $user->name) }}"
                                       class="w-full px-4 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-200 focus:outline-none transition bg-white">
                            </div>

                            <div>
                                <label for="institution" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                                    Instituição onde Atua
                                </label>
                                <input id="institution" name="institution" type="text" maxlength="256" value="{{ old('institution', $user->institution) }}" placeholder="Ex: Escola Estadual Modelo"
                                       class="w-full px-4 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-200 focus:outline-none transition bg-white">
                                <p class="text-[11px] text-cloud-burst-600 mt-1">Até 256 caracteres.</p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider">
                                        CPF (Cadastro de Pessoa Física)
                                    </label>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-gray-700 bg-gray-100 px-2 py-0.5 rounded-full border border-gray-200">
                                        <x-lucide-lock class="w-3 h-3 text-gray-500" />
                                        <span>Dado Institucional Protegido</span>
                                    </span>
                                </div>
                                <input type="text" disabled value="{{ $user->cpf ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $user->cpf) : 'Não informado' }}"
                                       class="w-full px-4 py-2.5 text-sm font-mono border border-gray-200 bg-gray-50 text-gray-600 rounded-xl cursor-not-allowed select-none">
                                <p class="text-[11px] text-cloud-burst-600 mt-1">O CPF é registrado na ativação institucional e não pode ser alterado diretamente pelo perfil.</p>
                            </div>

                            <div class="pt-2 text-right">
                                <button type="submit" class="cursor-pointer px-5 py-2.5 bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-xs rounded-xl shadow-2xs transition inline-flex items-center gap-1.5">
                                    <x-lucide-check class="w-4 h-4" />
                                    <span>Salvar Dados Cadastrais</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Card de Alteração de E-mail (Double-Opt-In Seguro) --}}
                    <div class="bg-white rounded-3xl p-6 md:p-8 border border-magnolia-200 shadow-xs space-y-4">
                        <div class="flex items-center gap-2 pb-3 border-b border-magnolia-200">
                            <x-lucide-mail class="w-5 h-5 text-royal-purple-800" />
                            <h3 class="text-base font-extrabold font-heading text-cloud-burst-950">Endereço de E-mail</h3>
                        </div>

                        <div class="p-4 rounded-2xl bg-royal-purple-50/50 border border-royal-purple-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold font-heading text-cloud-burst-700 uppercase tracking-wider block">E-mail Cadastrado Atualmente</span>
                                <span class="text-sm font-bold font-heading text-cloud-burst-950">{{ $user->email }}</span>
                            </div>

                            <form action="{{ route('profile.email.request') }}" method="POST">
                                @csrf
                                <button type="submit" class="cursor-pointer px-4 py-2 rounded-xl bg-white hover:bg-royal-purple-50 border border-royal-purple-300 text-royal-purple-900 font-bold font-heading text-xs shadow-2xs transition inline-flex items-center gap-1.5">
                                    <x-lucide-refresh-cw class="w-3.5 h-3.5 text-royal-purple-700" />
                                    <span>Solicitar Troca de E-mail</span>
                                </button>
                            </form>
                        </div>

                        <div class="text-xs text-cloud-burst-700 bg-magnolia-50/80 p-3.5 rounded-xl border border-magnolia-200 space-y-1">
                            <p class="font-bold text-royal-purple-950 flex items-center gap-1">
                                <x-lucide-shield-alert class="w-3.5 h-3.5 text-royal-purple-800" />
                                <span>Processo Seguro de Troca de E-mail (2 Etapas)</span>
                            </p>
                            <p>1. Ao solicitar, enviamos um link de confirmação para o seu e-mail atual.</p>
                            <p>2. Ao acessar esse link, você informará o novo e-mail e receberá nele o link de validação final.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Painel 2: Segurança & MFA --}}
        <div id="panel-seguranca" class="{{ ($activeTab ?? 'dados') === 'seguranca' ? '' : 'hidden' }}">
            <div class="space-y-6">
                <div class="bg-white rounded-3xl p-6 border border-magnolia-200 shadow-xs">
                    <h2 class="text-lg font-extrabold font-heading text-cloud-burst-950">
                        Central de Segurança & MFA
                    </h2>
                    <p class="text-xs text-cloud-burst-700 mt-1">
                        Configure autenticação em duas etapas (TOTP) e chaves de acesso biométricas (Passkeys) para proteger sua conta contra acessos não autorizados.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- TOTP Card --}}
                    <div class="bg-white rounded-3xl p-6 md:p-8 border border-magnolia-200 shadow-xs flex flex-col justify-between space-y-6">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-royal-purple-100 flex items-center justify-center text-royal-purple-800 shrink-0">
                                        <x-lucide-smartphone class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold font-heading text-cloud-burst-950">Autenticador TOTP</h3>
                                        <p class="text-xs text-cloud-burst-700">Google Authenticator, Authy ou 1Password</p>
                                    </div>
                                </div>
                                @if($user->isTotpEnabled())
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold font-heading bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        Ativo
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold font-heading bg-gray-100 text-gray-600 border border-gray-300">
                                        Inativo
                                    </span>
                                @endif
                            </div>

                            @if($user->isTotpEnabled())
                                <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-200 text-xs text-emerald-950 space-y-2 mb-6">
                                    <p class="font-bold font-heading flex items-center gap-1.5">
                                        <x-lucide-check-circle class="w-4 h-4 text-emerald-600" />
                                        <span>Sua conta está protegida por verificação em duas etapas.</span>
                                    </p>
                                    <p>Ao fazer login com sua senha, o sistema solicitará o código temporário de 6 dígitos gerado pelo aplicativo autenticador do seu celular.</p>
                                </div>

                                <form action="{{ route('security.totp.disable') }}" method="POST"
                                      data-confirm="Deseja realmente desativar a autenticação em duas etapas?"
                                      data-confirm-title="Desativar TOTP"
                                      data-confirm-ok="Desativar"
                                      data-confirm-tone="danger">
                                    @csrf
                                    <button type="submit" class="cursor-pointer px-4 py-2.5 rounded-xl border border-rose-300 text-rose-700 hover:bg-rose-50 text-xs font-bold font-heading transition">
                                        Desativar TOTP
                                    </button>
                                </form>
                            @else
                                <p class="text-xs text-cloud-burst-800 mb-4 leading-relaxed">
                                    Escaneie o código QR abaixo com o aplicativo autenticador do seu celular ou insira a chave manual de configuração.
                                </p>

                                <div class="bg-royal-purple-50 p-4 rounded-2xl border border-royal-purple-100 mb-4 space-y-3">
                                    @if(!empty($totpQrSvg))
                                        <div class="flex justify-center">
                                            <div class="inline-block rounded-xl shadow-2xs border border-royal-purple-200 bg-white p-2.5" role="img" aria-label="QR Code TOTP">
                                                {!! $totpQrSvg !!}
                                            </div>
                                        </div>
                                    @endif
                                    <div class="text-center">
                                        <span class="text-[11px] text-cloud-burst-700 block mb-0.5">Chave de configuração manual:</span>
                                        <code class="font-mono text-xs font-bold text-royal-purple-900 tracking-widest break-all select-all bg-white px-2.5 py-1 rounded-lg border border-royal-purple-200">
                                            {{ $pendingSecret }}
                                        </code>
                                    </div>
                                </div>

                                <form action="{{ route('security.totp.enable') }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label for="code" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                                            Código de 6 dígitos gerado no app:
                                        </label>
                                        <input id="code" name="code" type="text" maxlength="6" required placeholder="000000"
                                               class="w-full text-center font-mono text-lg font-bold px-3 py-2 border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none focus:ring-2 focus:ring-royal-purple-800/20 bg-white">
                                    </div>
                                    <button type="submit" class="w-full cursor-pointer py-2.5 px-4 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-xs shadow-xs transition">
                                        Confirmar e Ativar TOTP
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    {{-- Passkeys (WebAuthn) Card --}}
                    <div class="bg-white rounded-3xl p-6 md:p-8 border border-magnolia-200 shadow-xs flex flex-col justify-between space-y-6">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-royal-purple-100 flex items-center justify-center text-royal-purple-800 shrink-0">
                                        <x-lucide-key class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold font-heading text-cloud-burst-950">Passkeys & Biometria</h3>
                                        <p class="text-xs text-cloud-burst-700">Touch ID, Face ID, Windows Hello ou Chave USB</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-800 border border-royal-purple-300">
                                    {{ $credentials->count() }} cadastrada(s)
                                </span>
                            </div>

                            <p class="text-xs text-cloud-burst-800 mb-4 leading-relaxed">
                                Chaves criptográficas seguras vinculadas diretamente ao seu navegador ou hardware. Elas permitem login ultrarrápido sem precisar digitar senha.
                            </p>

                            <div class="space-y-2 mb-6">
                                @forelse($credentials as $cred)
                                    <div class="flex items-center justify-between p-3.5 rounded-2xl border border-gray-200 bg-gray-50 text-xs">
                                        <div>
                                            <p class="font-bold font-heading text-cloud-burst-950">{{ $cred->name ?? 'Passkey' }}</p>
                                            <p class="text-[11px] text-gray-500">Cadastrada em {{ $cred->created_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                        <form action="{{ route('security.passkey.destroy', $cred) }}" method="POST"
                                              data-confirm="Deseja remover esta Passkey?"
                                              data-confirm-title="Remover Passkey"
                                              data-confirm-ok="Remover"
                                              data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="cursor-pointer text-rose-600 hover:text-rose-800 font-bold px-2 py-1 text-xs hover:underline">
                                                Remover
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <div class="p-6 rounded-2xl bg-gray-50 border border-dashed border-gray-300 text-center text-xs text-gray-500 space-y-1">
                                        <x-lucide-shield-alert class="w-5 h-5 text-gray-400 mx-auto mb-1" />
                                        <p class="font-semibold text-gray-700">Nenhuma Passkey cadastrada ainda neste dispositivo.</p>
                                        <p class="text-[11px]">Adicione uma chave de acesso para entrar com impressão digital, reconhecimento facial ou PIN.</p>
                                    </div>
                                @endforelse
                            </div>

                            <button id="btn-register-passkey" type="button"
                                    data-options-url="{{ route('security.passkey.options') }}"
                                    data-store-url="{{ route('security.passkey.store') }}"
                                    class="w-full cursor-pointer py-3 px-4 rounded-xl bg-white border border-magnolia-300 hover:border-royal-purple-800 hover:bg-royal-purple-50 text-royal-purple-950 font-bold font-heading text-xs shadow-2xs transition flex items-center justify-center gap-2">
                                <x-lucide-plus class="w-4 h-4 text-royal-purple-800" />
                                <span>Registrar Nova Passkey neste Dispositivo</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Script de Alternância Dinâmica das Abas --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('[data-profile-tab]');
            const panels = {
                dados: document.getElementById('panel-dados'),
                seguranca: document.getElementById('panel-seguranca')
            };

            const activateTab = (tabName) => {
                tabs.forEach((tab) => {
                    const isActive = tab.dataset.profileTab === tabName;
                    tab.classList.toggle('border-royal-purple-800', isActive);
                    tab.classList.toggle('text-royal-purple-950', isActive);
                    tab.classList.toggle('bg-royal-purple-50/60', isActive);
                    tab.classList.toggle('border-transparent', !isActive);
                    tab.classList.toggle('text-cloud-burst-600', !isActive);
                });

                Object.entries(panels).forEach(([key, panel]) => {
                    panel?.classList.toggle('hidden', key !== tabName);
                });
            };

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const target = tab.dataset.profileTab;
                    activateTab(target);

                    try {
                        const url = new URL(window.location);
                        url.searchParams.set('tab', target);
                        window.history.replaceState({}, '', url);
                    } catch (e) {}
                });
            });
        });
    </script>
</x-layouts.admin>
