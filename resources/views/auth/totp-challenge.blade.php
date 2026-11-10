<x-layouts.app title="Autenticação em Duas Etapas · Corujinha AVALIA+">
    <div class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6 bg-white p-8 md:p-10 rounded-3xl shadow-xl border border-magnolia-200 text-center">
            <picture class="mb-3 block">
                <source srcset="{{ asset('images/logo/logo-icon.avif') }}" type="image/avif">
                <source srcset="{{ asset('images/logo/logo-icon.webp') }}" type="image/webp">
                <img src="{{ asset('images/logo/Corujinha_AVALIA_Mais_ICO.png') }}" alt="Corujinha" class="h-14 w-auto object-contain mx-auto">
            </picture>

            <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                Verificação em Duas Etapas (TOTP)
            </h1>
            <p class="text-xs text-cloud-burst-800">
                Abra o aplicativo autenticador no seu celular (Google Authenticator ou Authy) e digite o código de 6 dígitos gerado para o Corujinha AVALIA+.
            </p>

            @if($errors->any())
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs font-medium text-rose-800 text-left space-y-1">
                    @foreach($errors->all() as $error)
                        <p class="flex items-center gap-1.5">
                            <x-lucide-alert-circle class="w-3.5 h-3.5 text-rose-600 shrink-0" />
                            <span>{{ $error }}</span>
                        </p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('login.totp') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <input name="code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" autofocus required
                           class="w-48 mx-auto text-center font-mono text-3xl tracking-[0.3em] font-bold px-4 py-3 border-2 border-royal-purple-300 rounded-2xl focus:border-royal-purple-800 focus:outline-none focus:ring-4 focus:ring-royal-purple-800/20 transition text-royal-purple-900"
                           placeholder="000000">
                </div>

                <button type="submit" class="w-full cursor-pointer py-3.5 px-4 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-sm shadow-md transition flex items-center justify-center gap-2">
                    <x-lucide-shield-check class="w-4 h-4" />
                    <span>Verificar e Entrar</span>
                </button>
            </form>

            <p class="text-[11px] text-cloud-burst-600">
                Perdeu o acesso ao seu autenticador? Solicite ao Super-Usuário a desativação do segundo fator da sua conta.
            </p>
        </div>
    </div>
</x-layouts.app>
