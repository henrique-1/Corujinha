<x-layouts.app title="Redefinir Senha · Corujinha">
    <div class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6 bg-white p-8 md:p-10 rounded-3xl shadow-xl border border-magnolia-200">
            <div class="text-center flex flex-col items-center">
                <x-brand-logo variant="horizontal" picture-class="mb-4 block" img-class="h-16 w-auto object-contain mx-auto" />

                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-800 mb-1">
                    Definição de Nova Senha
                </span>
                <h1 class="text-xl font-extrabold font-heading text-cloud-burst-950 tracking-tight mt-1">
                    Crie sua Nova Senha
                </h1>
                <p class="mt-1 text-xs text-cloud-burst-700">
                    Defina sua nova credencial em conformidade com as diretrizes de segurança da NIST SP 800-63B-4.
                </p>
            </div>

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

            <form class="space-y-4" action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Confirme seu E-mail
                    </label>
                    <input id="email" name="email" type="email" required value="{{ old('email', $email) }}"
                           class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-800/20 focus:outline-none transition"
                           placeholder="seu.email@escola.com">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider">
                            Nova Senha (15 a 64 caracteres) <span class="text-shocking-pink-600">*</span>
                        </label>
                        <span id="char-count" class="text-[11px] font-mono font-bold text-royal-purple-800">
                            0 / 15 caracteres mínimos
                        </span>
                    </div>
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                           class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-800/20 focus:outline-none transition"
                           placeholder="Mínimo 15 caracteres (ex.: frase de segurança)">

                    <div class="mt-2 p-3 rounded-xl bg-royal-purple-50/70 border border-royal-purple-200 text-xs space-y-1 text-royal-purple-950">
                        <span class="font-bold font-heading block text-royal-purple-900">Regras de Segurança (NIST SP 800-63B-4):</span>
                        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-cloud-burst-800">
                            <li>Comprimento entre 15 e 64 caracteres.</li>
                            <li>Não deve conter seu nome ou parte do e-mail.</li>
                            <li>Recomendado: utilize frases longas e fáceis de lembrar.</li>
                        </ul>
                        <div class="pt-1">
                            <span id="pwd-status" class="font-bold font-heading text-amber-600 text-xs">Aguardando preenchimento</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Confirme a Nova Senha <span class="text-shocking-pink-600">*</span>
                    </label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                           class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-800/20 focus:outline-none transition"
                           placeholder="Repita a mesma senha com exatidão">
                </div>

                <button type="submit" class="w-full cursor-pointer py-3.5 px-4 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-sm shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                    <x-lucide-key class="w-4 h-4" />
                    <span>Salvar Nova Senha</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const pwd = document.getElementById('password');
            const countEl = document.getElementById('char-count');
            const statusEl = document.getElementById('pwd-status');

            if (pwd && countEl && statusEl) {
                pwd.addEventListener('input', () => {
                    const len = pwd.value.length;
                    countEl.textContent = `${len} / 15 caracteres mínimos`;

                    if (len >= 15 && len <= 64) {
                        statusEl.textContent = 'Comprimento Válido (NIST)';
                        statusEl.className = 'font-bold font-heading text-emerald-600';
                    } else if (len > 64) {
                        statusEl.textContent = 'Excedeu limite de 64 caracteres';
                        statusEl.className = 'font-bold font-heading text-rose-600';
                    } else {
                        statusEl.textContent = `Faltam ${15 - len} caracteres`;
                        statusEl.className = 'font-bold font-heading text-amber-600';
                    }
                });
            }
        });
    </script>
</x-layouts.app>
