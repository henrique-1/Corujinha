<x-layouts.app title="Entrar na Sala de Aula · Corujinha">
    <div class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6 bg-white p-8 md:p-10 rounded-3xl shadow-xl border border-magnolia-200">
            <div class="text-center flex flex-col items-center">
                <picture class="mb-4 block">
                    <source srcset="{{ asset('images/logo/logo-horizontal.avif') }}" type="image/avif">
                    <source srcset="{{ asset('images/logo/logo-horizontal.webp') }}" type="image/webp">
                    <img src="{{ asset('images/logo/logo-horizontal.webp') }}" alt="Corujinha" class="h-16 w-auto object-contain mx-auto">
                </picture>

                <h1 class="text-xl font-bold font-heading text-cloud-burst-950 tracking-tight">
                    Sala de Avaliação
                </h1>
                <p class="mt-1 text-xs text-cloud-burst-800">
                    Digite o código fornecido pelo seu professor para iniciar
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

            <form action="{{ route('estudante.iniciar') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="code" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Código da Sala de Aula <span class="text-shocking-pink-600">*</span>
                    </label>
                    <input id="code" name="code" type="text" required value="{{ old('code', $code) }}"
                           placeholder="Ex.: AVAL-XXXX" autofocus maxlength="12"
                           class="w-full px-4 py-3.5 text-lg font-mono font-bold tracking-widest text-center uppercase border-2 border-royal-purple-200 rounded-2xl focus:border-royal-purple-800 focus:ring-4 focus:ring-royal-purple-800/20 focus:outline-none transition text-royal-purple-900 bg-magnolia-50">
                    <p class="text-[11px] text-cloud-burst-600 mt-1 text-center">Formato com hífen aplicado automaticamente ao digitar.</p>
                </div>

                <div>
                    <label for="student_name" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Seu Nome Completo <span class="text-shocking-pink-600">*</span>
                    </label>
                    <input id="student_name" name="student_name" type="text" required value="{{ old('student_name') }}"
                           placeholder="Digite seu nome completo"
                           class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-800/20 focus:outline-none transition">
                </div>

                <div class="bg-royal-purple-50 rounded-2xl p-4 border border-royal-purple-200 text-xs text-royal-purple-950 space-y-2">
                    <div class="flex items-start gap-2">
                        <x-lucide-timer class="w-4 h-4 text-royal-purple-800 shrink-0 mt-0.5" />
                        <span><b>Cronômetro contínuo:</b> tempo corrido por questão.</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <x-lucide-highlighter class="w-4 h-4 text-royal-purple-800 shrink-0 mt-0.5" />
                        <span><b>Marca-texto:</b> destaque comando (amarelo) e pistas (azul) no texto.</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <x-lucide-edit-3 class="w-4 h-4 text-royal-purple-800 shrink-0 mt-0.5" />
                        <span><b>Justificativa:</b> defenda sua escolha em cada alternativa.</span>
                    </div>
                </div>

                <button type="submit" class="w-full cursor-pointer py-3.5 px-4 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-base shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                    <span>Entrar na Sala e Iniciar</span>
                    <x-lucide-arrow-right class="w-4 h-4" />
                </button>
            </form>

            <div class="pt-4 border-t border-magnolia-200 text-center">
                <a href="{{ route('login') }}" class="cursor-pointer inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl text-xs font-bold font-heading text-royal-purple-900 bg-royal-purple-50 hover:bg-royal-purple-100 border border-royal-purple-200 transition shadow-2xs">
                    <x-lucide-shield class="w-4 h-4 text-royal-purple-800 shrink-0" />
                    <span>Área do Professor e Administrador</span>
                    <x-lucide-arrow-right class="w-3.5 h-3.5 text-royal-purple-700 shrink-0" />
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const codeInput = document.getElementById('code');
            if (codeInput) {
                codeInput.addEventListener('input', (e) => {
                    let val = e.target.value.toUpperCase();
                    // Remove caracteres não alfanuméricos e traços múltiplos
                    val = val.replace(/[^A-Z0-9]/g, '');

                    // Aplica máscara automática se tiver pelo menos 4 caracteres (ex: PREFIX-SUFIX)
                    if (val.length > 4) {
                        val = val.slice(0, 4) + '-' + val.slice(4, 9);
                    }
                    e.target.value = val;
                });
            }
        });
    </script>
</x-layouts.app>
