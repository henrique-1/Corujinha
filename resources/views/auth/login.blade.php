<x-layouts.app title="Acesso Administrativo · Corujinha">
    <div class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6 bg-white p-8 md:p-10 rounded-3xl shadow-xl border border-magnolia-200">
            <div class="text-center flex flex-col items-center">
                <x-brand-logo variant="horizontal" picture-class="mb-4 block" img-class="h-16 w-auto object-contain mx-auto" />

                <span id="login-passkey-hint" class="inline-block px-3 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-800 mb-1">
                    Painel Administrativo & Docente
                </span>
                <p class="mt-1 text-xs text-cloud-burst-800">
                    Acesso exclusivo para Professores e Super-Usuários
                </p>
            </div>

            @if(session('success'))
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-800 flex items-center gap-2">
                    <x-lucide-check-circle class="w-4 h-4 text-emerald-600 shrink-0" />
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs font-medium text-rose-800 space-y-1">
                    @foreach($errors->all() as $error)
                        <p class="flex items-center gap-1.5">
                            <x-lucide-alert-circle class="w-3.5 h-3.5 text-rose-600 shrink-0" />
                            <span>{{ $error }}</span>
                        </p>
                    @endforeach
                </div>
            @endif

            <div id="passkey-login-error" class="hidden p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs font-medium text-rose-800 flex items-center gap-1.5" role="alert">
                <x-lucide-alert-circle class="w-3.5 h-3.5 text-rose-600 shrink-0" />
                <span data-passkey-error-text></span>
            </div>

            <form class="space-y-4" action="{{ route('login') }}" method="POST">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        E-mail
                    </label>
                    <input id="email" name="email" type="email" autocomplete="email webauthn" autofocus aria-describedby="login-passkey-hint" required value="{{ old('email') }}"
                           class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-800/20 focus:outline-none transition"
                           placeholder="seu.email@escola.com">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Senha
                    </label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-800/20 focus:outline-none transition"
                           placeholder="•••••••••••••••">
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center gap-2 cursor-pointer text-cloud-burst-800 font-medium">
                        <input type="checkbox" name="remember" class="rounded text-royal-purple-800 focus:ring-royal-purple-800 border-gray-300">
                        <span>Lembrar de mim</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="font-bold font-heading text-royal-purple-800 hover:underline">
                        Esqueceu sua senha?
                    </a>
                </div>

                <button type="submit" class="w-full cursor-pointer py-3.5 px-4 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-sm shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                    <span>Acessar Painel</span>
                    <x-lucide-arrow-right class="w-4 h-4" />
                </button>
            </form>

            <div class="relative flex items-center py-1">
                <div class="grow border-t border-magnolia-200"></div>
                <span class="shrink mx-3 text-[11px] font-bold font-heading text-cloud-burst-600 uppercase tracking-wider">ou</span>
                <div class="grow border-t border-magnolia-200"></div>
            </div>

            <button id="btn-login-passkey" type="button"
                    data-options-url="{{ route('login.passkey.options') }}"
                    data-verify-url="{{ route('login.passkey.verify') }}"
                    class="w-full cursor-pointer py-3 px-4 rounded-xl bg-white border border-magnolia-300 hover:border-royal-purple-800 hover:bg-royal-purple-50/50 text-cloud-burst-950 font-bold font-heading text-sm shadow-xs transition flex items-center justify-center gap-2">
                <x-lucide-key-round class="w-4 h-4 text-royal-purple-800" />
                <span>Entrar com Passkey</span>
            </button>

            <div class="pt-4 border-t border-magnolia-200 text-center space-y-3">
                <a href="{{ route('estudante.entrar') }}" class="cursor-pointer text-xs font-bold text-royal-purple-800 hover:underline flex items-center justify-center gap-1">
                    <x-lucide-arrow-left class="w-3.5 h-3.5" />
                    <span>É aluno? Entrar com código da Sala de Aula</span>
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
