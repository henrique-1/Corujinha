<x-layouts.app title="Finalizar Cadastro de Professor · Corujinha">
    <div class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-lg w-full space-y-6 bg-white p-8 md:p-10 rounded-3xl shadow-xl border border-magnolia-200">
            <div class="text-center flex flex-col items-center">
                <picture class="mb-3 block">
                    <source srcset="{{ asset('images/logo/logo-horizontal.avif') }}" type="image/avif">
                    <source srcset="{{ asset('images/logo/logo-horizontal.webp') }}" type="image/webp">
                    <img src="{{ asset('images/logo/logo-horizontal.webp') }}" alt="Corujinha" class="h-14 w-auto object-contain mx-auto">
                </picture>

                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold font-heading bg-emerald-100 text-emerald-800 mb-2">
                    Convite de Professor
                </span>
                <h1 class="text-2xl md:text-3xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                    Finalize seu Cadastro
                </h1>
                <p class="mt-1 text-xs text-cloud-burst-800">
                    Defina seu CPF e sua senha de acesso para começar a utilizar o painel
                </p>
            </div>

            @if($errors->any())
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-medium text-rose-800 space-y-1">
                    @foreach($errors->all() as $error)
                        <p class="flex items-center gap-1.5">
                            <x-lucide-alert-circle class="w-3.5 h-3.5 text-rose-600 shrink-0" />
                            <span>{{ $error }}</span>
                        </p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('invitation.accept', ['token' => $token]) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold font-heading text-cloud-burst-800 uppercase tracking-wider mb-1">
                        Nome do Professor
                    </label>
                    <input type="text" disabled value="{{ $user->name }}"
                           class="w-full px-4 py-2.5 text-sm bg-gray-100 border border-gray-200 rounded-xl text-gray-600 font-medium cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-bold font-heading text-cloud-burst-800 uppercase tracking-wider mb-1">
                        E-mail
                    </label>
                    <input type="email" disabled value="{{ $user->email }}"
                           class="w-full px-4 py-2.5 text-sm bg-gray-100 border border-gray-200 rounded-xl text-gray-600 font-medium cursor-not-allowed">
                </div>

                <div>
                    <label for="cpf" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                        CPF <span class="text-rose-500">*</span>
                    </label>
                    <input id="cpf" name="cpf" type="text" required value="{{ old('cpf') }}" maxlength="14"
                           placeholder="000.000.000-00"
                           class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-800/20 focus:outline-none transition">
                    <p class="text-[11px] text-cloud-burst-600 mt-1">Digite apenas números ou com pontuação.</p>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                        Senha (NIST SP 800-63B-4) <span class="text-rose-500">*</span>
                    </label>
                    <input id="password" name="password" type="password" required minlength="15" maxlength="64"
                           placeholder="Mínimo de 15 e máximo de 64 caracteres"
                           class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-800/20 focus:outline-none transition">

                    {{-- Indicador de tamanho e padrão NIST --}}
                    <div class="mt-2 text-xs flex items-center justify-between">
                        <span id="char-count" class="text-cloud-burst-700 font-mono">0 / 15 caracteres mínimos</span>
                        <span id="nist-status" class="font-bold font-heading text-amber-600">Requer 15+ caracteres</span>
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                        Confirmar Senha <span class="text-rose-500">*</span>
                    </label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required minlength="15" maxlength="64"
                           placeholder="Repita a senha digitada"
                           class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-800/20 focus:outline-none transition">
                </div>

                <div class="bg-royal-purple-50 rounded-2xl p-3.5 border border-royal-purple-200 text-[11px] text-royal-purple-950 space-y-1">
                    <p class="font-bold font-heading flex items-center gap-1">
                        <x-lucide-shield class="w-3.5 h-3.5 text-royal-purple-800" />
                        <span>Norma NIST SP 800-63B-4:</span>
                    </p>
                    <p>• A senha deve conter entre 15 e 64 caracteres.</p>
                    <p>• Você pode usar frases completas com espaços, números e símbolos.</p>
                    <p>• A senha não deve conter seu nome, prefixo de e-mail ou CPF.</p>
                </div>

                <button type="submit" class="w-full cursor-pointer py-3.5 px-4 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-sm shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                    <span>Concluir Cadastro e Entrar</span>
                    <x-lucide-arrow-right class="w-4 h-4" />
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const pwd = document.getElementById('password');
            const countEl = document.getElementById('char-count');
            const statusEl = document.getElementById('nist-status');
            const cpfInput = document.getElementById('cpf');

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

            if (cpfInput) {
                cpfInput.addEventListener('input', (e) => {
                    let v = e.target.value.replace(/\D/g, '').slice(0, 11);
                    if (v.length > 9) {
                        v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
                    } else if (v.length > 6) {
                        v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
                    } else if (v.length > 3) {
                        v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
                    }
                    e.target.value = v;
                });
            }
        });
    </script>
</x-layouts.app>
