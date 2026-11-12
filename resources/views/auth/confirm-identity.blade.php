<x-layouts.admin title="Confirmar Identidade · Corujinha">
    <div class="max-w-md mx-auto py-8">
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-magnolia-200 space-y-6">
            <div class="text-center space-y-2">
                <div class="w-12 h-12 rounded-2xl bg-royal-purple-100 text-royal-purple-800 flex items-center justify-center mx-auto">
                    <x-lucide-shield-alert class="w-6 h-6" />
                </div>
                <h1 class="text-xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                    Confirmação de Segurança
                </h1>
                <p class="text-xs text-cloud-burst-700 leading-relaxed">
                    Esta é uma ação sensível. Por segurança, confirme sua senha atual
                    @if($user->isTotpEnabled())
                        e o código de 6 dígitos do seu autenticador (TOTP)
                    @endif
                    para continuar.
                </p>
            </div>

            <form action="{{ route('password.confirm.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="password" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Sua Senha Atual <span class="text-shocking-pink-600">*</span>
                    </label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required autofocus
                           class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-800/20 focus:outline-none transition">
                </div>

                @if($user->isTotpEnabled())
                    <div>
                        <label for="totp_code" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Código do Autenticador (6 dígitos) <span class="text-shocking-pink-600">*</span>
                        </label>
                        <input id="totp_code" name="totp_code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" required placeholder="000000"
                               class="w-full text-center font-mono text-lg tracking-widest font-bold px-4 py-2.5 border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-800/20 focus:outline-none transition">
                    </div>
                @endif

                <div class="flex items-center justify-between gap-3 pt-2">
                    <a href="{{ route('security.index') }}" class="px-4 py-2.5 rounded-xl border border-gray-300 text-cloud-burst-800 text-xs font-bold font-heading hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="cursor-pointer px-5 py-2.5 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-xs shadow-sm transition flex items-center gap-1.5">
                        <x-lucide-check-circle class="w-4 h-4" />
                        <span>Confirmar Identidade</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
