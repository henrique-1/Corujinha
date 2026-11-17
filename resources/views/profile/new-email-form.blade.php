<x-layouts.admin title="Definir Novo E-mail · Corujinha">
    <div class="max-w-xl mx-auto space-y-6">
        <div>
            <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-royal-purple-800 hover:text-royal-purple-900 hover:underline mb-2 transition">
                <x-lucide-arrow-left class="w-4 h-4" />
                <span>Voltar ao Perfil</span>
            </a>
            <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                Definir Novo Endereço de E-mail
            </h1>
            <p class="text-sm text-cloud-burst-700 mt-1">
                Etapa 2 de 2: Informe o novo endereço de e-mail que deseja vincular à sua conta
            </p>
        </div>

        <div class="bg-white rounded-3xl p-6 md:p-8 border border-magnolia-200 shadow-xs space-y-5">
            <div class="p-3.5 rounded-2xl bg-royal-purple-50 border border-royal-purple-200 text-xs text-royal-purple-950 space-y-1">
                <p><strong>E-mail atual confirmado:</strong> {{ $changeRequest->current_email }}</p>
                <p class="text-cloud-burst-600">Após informar o novo endereço abaixo, enviaremos um link de confirmação para ele a fim de verificar sua propriedade.</p>
            </div>

            <form action="{{ route('profile.email.change.submit', ['token' => $changeRequest->step1_token]) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="new_email" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Novo Endereço de E-mail <span class="text-shocking-pink-600">*</span>
                    </label>
                    <input id="new_email" name="new_email" type="email" required value="{{ old('new_email') }}" placeholder="novo.email@escola.com"
                           class="w-full px-4 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-200 focus:outline-none transition bg-white">
                </div>

                <div class="pt-2 flex items-center justify-between">
                    <a href="{{ route('profile.edit') }}" class="text-xs font-bold font-heading text-cloud-burst-600 hover:text-cloud-burst-900 transition">
                        Cancelar
                    </a>

                    <button type="submit" class="cursor-pointer px-5 py-2.5 bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-xs rounded-xl shadow-2xs transition inline-flex items-center gap-1.5">
                        <x-lucide-send class="w-4 h-4" />
                        <span>Enviar Link para o Novo E-mail</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
