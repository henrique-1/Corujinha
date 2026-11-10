<x-layouts.admin title="Central de Segurança · Corujinha AVALIA+">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                Central de Segurança & MFA
            </h1>
            <p class="text-sm text-cloud-burst-800 mt-1">
                Configure autenticação em duas etapas (TOTP) e chaves de acesso (Passkeys) para proteger sua conta
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- TOTP Card --}}
            <div class="bg-white rounded-2xl p-6 border border-magnolia-200 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-xl bg-royal-purple-100 flex items-center justify-center text-royal-purple-800">
                                <x-lucide-smartphone class="w-5 h-5" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold font-heading text-cloud-burst-950">Autenticador TOTP</h2>
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
                        <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-200 text-xs text-emerald-950 space-y-2 mb-6">
                            <p class="font-bold font-heading flex items-center gap-1.5">
                                <x-lucide-check-circle class="w-4 h-4 text-emerald-600" />
                                <span>Sua conta está protegida por verificação em duas etapas.</span>
                            </p>
                            <p>Ao fazer login com sua senha, o sistema solicitará o código de 6 dígitos gerado pelo seu celular.</p>
                        </div>

                        <form action="{{ route('security.totp.disable') }}" method="POST" onsubmit="return confirm('Deseja realmente desativar a autenticação em duas etapas?');">
                            @csrf
                            <button type="submit" class="cursor-pointer px-4 py-2.5 rounded-xl border border-rose-300 text-rose-700 hover:bg-rose-50 text-xs font-bold font-heading transition">
                                Desativar TOTP
                            </button>
                        </form>
                    @else
                        <p class="text-xs text-cloud-burst-800 mb-4 leading-relaxed">
                            Escaneie o código abaixo no seu aplicativo autenticador ou adicione a chave secreta manualmente.
                        </p>

                        <div class="bg-royal-purple-50 p-4 rounded-xl border border-royal-purple-100 mb-4 space-y-3">
                            <div class="text-center">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ urlencode($totpUri) }}"
                                     alt="QR Code TOTP" class="mx-auto rounded-lg shadow-xs border border-royal-purple-200 bg-white p-2">
                            </div>
                            <div class="text-center">
                                <span class="text-[11px] text-cloud-burst-700 block">Chave de configuração manual:</span>
                                <code class="font-mono text-xs font-bold text-royal-purple-800 tracking-widest break-all select-all">
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
                                       class="w-full text-center font-mono text-lg font-bold px-3 py-2 border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none focus:ring-2 focus:ring-royal-purple-800/20">
                            </div>
                            <button type="submit" class="w-full cursor-pointer py-2.5 px-4 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-xs shadow-xs transition">
                                Confirmar e Ativar TOTP
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Passkeys (WebAuthn) Card --}}
            <div class="bg-white rounded-2xl p-6 border border-magnolia-200 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-xl bg-royal-purple-100 flex items-center justify-center text-royal-purple-800">
                                <x-lucide-key class="w-5 h-5" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold font-heading text-cloud-burst-950">Passkeys & Biometria</h2>
                                <p class="text-xs text-cloud-burst-700">Touch ID, Face ID, Windows Hello ou Chave USB</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-800 border border-royal-purple-300">
                            {{ $credentials->count() }} cadastrada(s)
                        </span>
                    </div>

                    <p class="text-xs text-cloud-burst-800 mb-4 leading-relaxed">
                        Chaves de segurança criptográficas vinculadas diretamente ao seu dispositivo ou navegador.
                    </p>

                    <div class="space-y-2 mb-6">
                        @forelse($credentials as $cred)
                            <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 bg-gray-50 text-xs">
                                <div>
                                    <p class="font-bold font-heading text-cloud-burst-950">{{ $cred->name ?? 'Passkey' }}</p>
                                    <p class="text-[11px] text-gray-400">Cadastrada em {{ $cred->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <form action="{{ route('security.passkey.destroy', $cred) }}" method="POST" onsubmit="return confirm('Remover esta Passkey?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="cursor-pointer text-rose-600 hover:text-rose-800 font-bold px-2 py-1">
                                        Remover
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="p-4 rounded-xl bg-gray-50 border border-dashed border-gray-200 text-center text-xs text-gray-500">
                                Nenhuma Passkey cadastrada ainda neste dispositivo.
                            </div>
                        @endforelse
                    </div>

                    <button id="btn-register-passkey" type="button" class="w-full cursor-pointer py-2.5 px-4 rounded-xl bg-white border border-magnolia-300 hover:border-royal-purple-800 text-cloud-burst-950 font-bold font-heading text-xs shadow-xs transition flex items-center justify-center gap-2">
                        <x-lucide-plus class="w-4 h-4 text-royal-purple-800" />
                        <span>Registrar Nova Passkey neste Dispositivo</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('btn-register-passkey');
            if (!btn) return;

            btn.addEventListener('click', async () => {
                if (!window.PublicKeyCredential) {
                    alert('Seu navegador não possui suporte a Passkeys (WebAuthn).');
                    return;
                }

                try {
                    const res = await fetch("{{ route('security.passkey.options') }}");
                    const options = await res.json();

                    options.challenge = Uint8Array.from(atob(btoa(options.challenge)), c => c.charCodeAt(0));
                    options.user.id = new TextEncoder().encode(options.user.id);

                    if (options.excludeCredentials) {
                        options.excludeCredentials = options.excludeCredentials.map(c => ({
                            ...c,
                            id: new TextEncoder().encode(c.id)
                        }));
                    }

                    const cred = await navigator.credentials.create({ publicKey: options });
                    if (cred) {
                        const name = prompt('Dê um nome para esta chave (ex: Notebook Lenovo, Celular)', 'Minha Passkey');
                        const saveRes = await fetch("{{ route('security.passkey.store') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                id: cred.id,
                                rawId: btoa(String.fromCharCode(...new Uint8Array(cred.rawId))),
                                name: name || 'Minha Passkey'
                            })
                        });

                        if (saveRes.ok) {
                            alert('Passkey registrada com sucesso!');
                            window.location.reload();
                        } else {
                            alert('Erro ao registrar a Passkey no servidor.');
                        }
                    }
                } catch (err) {
                    if (err.name !== 'NotAllowedError') {
                        alert('Erro ao criar Passkey: ' + err.message);
                    }
                }
            });
        });
    </script>
</x-layouts.admin>
