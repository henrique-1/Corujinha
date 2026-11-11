<x-layouts.admin title="Cadastrar Professor · Corujinha">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <a href="{{ route('admin.professores.index') }}" class="text-xs font-bold font-heading text-royal-purple-800 hover:underline mb-2 inline-flex items-center gap-1">
                <x-lucide-arrow-left class="w-3.5 h-3.5" />
                <span>Voltar para a lista de professores</span>
            </a>
            <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                Cadastrar Novo Professor
            </h1>
            <p class="text-sm text-cloud-burst-700 mt-1">
                Envie um convite de acesso para que o professor finalize o cadastro definindo sua própria senha e CPF
            </p>
        </div>

        <div class="bg-white rounded-2xl p-6 md:p-8 border border-magnolia-200 shadow-xs">
            <div class="bg-royal-purple-50 rounded-2xl p-4 border border-royal-purple-200 text-xs text-royal-purple-950 space-y-1.5 mb-6">
                <p class="font-bold font-heading flex items-center gap-1.5">
                    <x-lucide-shield class="w-4 h-4 text-royal-purple-800" />
                    <span>Protocolo Seguro de Cadastro:</span>
                </p>
                <p>O Super-Usuário <b>não cadastra</b> a senha nem o CPF do Professor.</p>
                <p>Ao salvar este formulário, um e-mail com link de ativação exclusivo será enviado para o professor preencher sua senha segundo a <b>NIST SP 800-63B-4</b> (15 a 64 caracteres) e seu CPF.</p>
            </div>

            <form action="{{ route('admin.professores.store') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Nome Completo do Professor <span class="text-shocking-pink-600">*</span>
                    </label>
                    <input id="name" name="name" type="text" required value="{{ old('name') }}" placeholder="Ex.: Maria Fernanda Silva"
                           class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-800/20 focus:outline-none transition">
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        E-mail Institucional do Professor <span class="text-shocking-pink-600">*</span>
                    </label>
                    <input id="email" name="email" type="email" required value="{{ old('email') }}" placeholder="maria.silva@sesi.org.br"
                           class="w-full px-4 py-3 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:ring-2 focus:ring-royal-purple-800/20 focus:outline-none transition">
                </div>

                <div class="pt-2 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.professores.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-300 text-cloud-burst-800 text-xs font-bold font-heading hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="cursor-pointer px-6 py-2.5 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white text-xs font-bold font-heading shadow-sm transition flex items-center gap-2">
                        <span>Enviar Convite por E-mail</span>
                        <x-lucide-arrow-right class="w-4 h-4" />
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
