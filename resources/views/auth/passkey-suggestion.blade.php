<x-layouts.app title="Ativar Passkey · Corujinha AVALIA+">
    <div class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6 bg-white p-8 md:p-10 rounded-3xl shadow-xl border border-magnolia-200 text-center">
            <picture class="mb-2 block">
                <source srcset="{{ asset('images/logo/logo-icon.avif') }}" type="image/avif">
                <source srcset="{{ asset('images/logo/logo-icon.webp') }}" type="image/webp">
                <img src="{{ asset('images/logo/Corujinha_AVALIA_Mais_ICO.png') }}" alt="Corujinha" class="h-16 w-auto object-contain mx-auto">
            </picture>

            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-800 mb-1">
                Segurança Avançada
            </span>
            <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                Deseja ativar uma Passkey?
            </h1>
            <p class="text-xs text-cloud-burst-800 leading-relaxed">
                Com a Passkey, você pode acessar sua conta utilizando a biometria do seu dispositivo (Touch ID, Face ID, Windows Hello) ou uma chave de segurança física, sem precisar digitar sua senha longa toda vez.
            </p>

            <div class="bg-royal-purple-50 rounded-2xl p-4 border border-royal-purple-200 text-left text-xs text-royal-purple-950 space-y-1.5">
                <p class="font-bold font-heading flex items-center gap-1.5">
                    <x-lucide-zap class="w-4 h-4 text-royal-purple-800" />
                    <span>Acesso Instantâneo e Mais Seguro</span>
                </p>
                <p>• À prova de phishing e interceptação.</p>
                <p>• Suporta biometria facial, impressão digital ou PIN local.</p>
                <p>• Você também pode configurar TOTP depois na aba de segurança.</p>
            </div>

            <div class="space-y-3 pt-2">
                <a href="{{ route('security.index') }}" class="w-full cursor-pointer py-3.5 px-4 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-sm shadow-md transition flex items-center justify-center gap-2">
                    <x-lucide-key class="w-4 h-4" />
                    <span>Configurar Passkey / TOTP Agora</span>
                </a>
                <a href="{{ route('professor.salas.index') }}" class="w-full cursor-pointer py-3 px-4 rounded-xl bg-gray-100 hover:bg-gray-200 text-cloud-burst-800 font-bold font-heading text-xs transition block">
                    Agora não, ir direto para o Painel →
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
