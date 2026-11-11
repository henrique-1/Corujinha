<x-layouts.app title="Esqueci Minha Senha · Corujinha">
    <div class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6 bg-white p-8 md:p-10 rounded-3xl shadow-xl border border-magnolia-200">
            <div class="text-center flex flex-col items-center">
                <x-brand-logo variant="horizontal" picture-class="mb-4 block" img-class="h-16 w-auto object-contain mx-auto" />

                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-800 mb-1">
                    Recuperação de Senha
                </span>
                <h1 class="text-xl font-extrabold font-heading text-cloud-burst-950 tracking-tight mt-1">
                    Esqueceu sua senha?
                </h1>
                <p class="mt-1 text-xs text-cloud-burst-700">
                    Informe seu e-mail cadastrado e enviaremos um link de redefinição com validade de 30 minutos.
                </p>
            </div>

            @if(session('status'))
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-800 flex items-start gap-2">
                    <x-lucide-check-circle class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                    <span>{{ session('status') }}</span>
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

            <form class="space-y-4" action="{{ route('password.email') }}" method="POST">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Seu E-mail Cadastrado
                    </label>
                    <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" autofocus
                           class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-800/20 focus:outline-none transition"
                           placeholder="seu.email@escola.com">
                </div>

                <button type="submit" class="w-full cursor-pointer py-3.5 px-4 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-sm shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                    <x-lucide-mail class="w-4 h-4" />
                    <span>Enviar Link de Recuperação</span>
                </button>
            </form>

            <div class="pt-4 border-t border-magnolia-200 text-center">
                <a href="{{ route('login') }}" class="text-xs font-bold font-heading text-royal-purple-800 hover:underline inline-flex items-center gap-1">
                    <x-lucide-arrow-left class="w-3.5 h-3.5" />
                    <span>Lembrou da senha? Voltar ao Login</span>
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
