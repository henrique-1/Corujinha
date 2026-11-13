<x-layouts.app title="Entrar na Sala de Aula · Corujinha">
    @php
        $resumeActive = old('resume_code') !== null || $errors->has('access_code') || request()->query('modo') === 'retomar';
    @endphp

    <main id="main-content" role="main" class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6 bg-white p-8 md:p-10 rounded-3xl shadow-xl border border-magnolia-200">
            <div class="text-center flex flex-col items-center">
                <x-brand-logo variant="horizontal" picture-class="mb-4 block" img-class="h-16 w-auto object-contain mx-auto" />

                <h1 class="text-xl font-bold font-heading text-cloud-burst-950 tracking-tight">
                    Sala de Avaliação
                </h1>
                <p id="entry-subtitle" class="mt-1 text-xs text-cloud-burst-800">
                    {{ $resumeActive ? 'Informe o código da sala e o seu código de aluno para continuar de onde parou' : 'Digite o código fornecido pelo seu professor para iniciar' }}
                </p>
            </div>

            {{-- Alternador de modo --}}
            <div role="tablist" aria-label="Modo de entrada" class="grid grid-cols-2 gap-1 p-1 rounded-2xl bg-magnolia-100 border border-magnolia-200">
                <button type="button" role="tab" id="tab-new" data-entry-tab="new" aria-selected="{{ $resumeActive ? 'false' : 'true' }}" aria-controls="panel-new"
                        class="cursor-pointer py-2 px-3 rounded-xl text-xs font-bold font-heading transition">
                    Primeira vez
                </button>
                <button type="button" role="tab" id="tab-resume" data-entry-tab="resume" aria-selected="{{ $resumeActive ? 'true' : 'false' }}" aria-controls="panel-resume"
                        class="cursor-pointer py-2 px-3 rounded-xl text-xs font-bold font-heading transition">
                    Voltar a uma sala
                </button>
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

            @if(session('info'))
                <div class="p-4 rounded-xl bg-sky-blue-50 border border-sky-blue-200 text-xs font-medium text-sky-blue-950">
                    {{ session('info') }}
                </div>
            @endif

            {{-- Painel: Primeira vez --}}
            <div id="panel-new" role="tabpanel" aria-labelledby="tab-new" class="{{ $resumeActive ? 'hidden' : '' }}">
                <form action="{{ route('estudante.iniciar') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="code" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Código da Sala de Aula <span class="text-shocking-pink-600">*</span>
                        </label>
                        <input id="code" name="code" type="text" required value="{{ old('code', $code) }}"
                               placeholder="AVAL-XXXXXX" autofocus maxlength="12" data-room-code
                               class="w-full px-4 py-3.5 text-lg font-mono font-bold tracking-widest text-center uppercase border-2 border-royal-purple-200 rounded-2xl focus:border-royal-purple-800 focus:ring-4 focus:ring-royal-purple-800/20 focus:outline-none transition text-royal-purple-900 bg-magnolia-50">
                        <p class="text-[11px] text-cloud-burst-800 mt-1 text-center font-medium">Formato com hífen aplicado automaticamente ao digitar.</p>
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
                        <div class="flex items-start gap-2">
                            <x-lucide-bookmark class="w-4 h-4 text-royal-purple-800 shrink-0 mt-0.5" />
                            <span><b>Precisa parar?</b> Anote o seu código de aluno exibido na tela para continuar depois.</span>
                        </div>
                    </div>

                    <button type="submit" class="w-full cursor-pointer py-3.5 px-4 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-base shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                        <span>Entrar na Sala e Iniciar</span>
                        <x-lucide-arrow-right class="w-4 h-4" />
                    </button>
                </form>
            </div>

            {{-- Painel: Voltar a uma sala --}}
            <div id="panel-resume" role="tabpanel" aria-labelledby="tab-resume" class="{{ $resumeActive ? '' : 'hidden' }}">
                <form action="{{ route('estudante.retomar') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="resume_code" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Código da Sala de Aula <span class="text-shocking-pink-600">*</span>
                        </label>
                        <input id="resume_code" name="resume_code" type="text" required value="{{ old('resume_code', $code) }}"
                               placeholder="AVAL-XXXXXX" maxlength="12" data-room-code autocomplete="off"
                               class="w-full px-4 py-3.5 text-lg font-mono font-bold tracking-widest text-center uppercase border-2 border-royal-purple-200 rounded-2xl focus:border-royal-purple-800 focus:ring-4 focus:ring-royal-purple-800/20 focus:outline-none transition text-royal-purple-900 bg-magnolia-50">
                    </div>

                    <div>
                        <label for="access_code" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Seu Código de Aluno <span class="text-shocking-pink-600">*</span>
                        </label>
                        <input id="access_code" name="access_code" type="text" required value="{{ old('access_code') }}"
                               placeholder="ALU-XXXXXXXXXX" maxlength="14" data-access-code autocomplete="off" autocapitalize="characters"
                               class="w-full px-4 py-3.5 text-lg font-mono font-bold tracking-widest text-center uppercase border-2 border-royal-purple-200 rounded-2xl focus:border-royal-purple-800 focus:ring-4 focus:ring-royal-purple-800/20 focus:outline-none transition text-royal-purple-900 bg-magnolia-50">
                        <p class="text-[11px] text-cloud-burst-800 mt-1 text-center font-medium">Digite o código completo iniciado por <b>ALU-</b> que apareceu na sua tela.</p>
                    </div>

                    <button type="submit" class="w-full cursor-pointer py-3.5 px-4 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-base shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                        <span>Continuar de Onde Parei</span>
                        <x-lucide-arrow-right class="w-4 h-4" />
                    </button>
                </form>
            </div>

            <div class="pt-4 border-t border-magnolia-200 text-center">
                <a href="{{ route('login') }}" class="cursor-pointer inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl text-xs font-bold font-heading text-royal-purple-900 bg-royal-purple-50 hover:bg-royal-purple-100 border border-royal-purple-200 transition shadow-2xs">
                    <x-lucide-shield class="w-4 h-4 text-royal-purple-800 shrink-0" />
                    <span>Área do Professor e Administrador</span>
                    <x-lucide-arrow-right class="w-3.5 h-3.5 text-royal-purple-700 shrink-0" />
                </a>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Formatação do código da sala: AVAL-XXXXXX
            document.querySelectorAll('[data-room-code]').forEach((input) => {
                input.addEventListener('input', (e) => {
                    let val = e.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
                    if (val.startsWith('AVAL') && val.length > 4 && val[4] !== '-') {
                        val = 'AVAL-' + val.slice(4);
                    }
                    e.target.value = val.slice(0, 11);
                });
            });

            // Formatação do código de aluno: o aluno digita ALU- e os 10 caracteres
            document.querySelectorAll('[data-access-code]').forEach((input) => {
                input.addEventListener('input', (e) => {
                    let val = e.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
                    if (val.startsWith('ALU') && val.length > 3 && val[3] !== '-') {
                        val = 'ALU-' + val.slice(3);
                    }
                    e.target.value = val.slice(0, 14);
                });
            });

            // Alternador de modo (Primeira vez / Voltar a uma sala)
            const tabs = document.querySelectorAll('[data-entry-tab]');
            const panels = {
                new: document.getElementById('panel-new'),
                resume: document.getElementById('panel-resume'),
            };
            const subtitle = document.getElementById('entry-subtitle');
            const subtitles = {
                new: 'Digite o código fornecido pelo seu professor para iniciar',
                resume: 'Informe o código da sala e o seu código de aluno para continuar de onde parou',
            };

            const activate = (mode) => {
                tabs.forEach((tab) => {
                    const active = tab.dataset.entryTab === mode;
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                    tab.classList.toggle('bg-white', active);
                    tab.classList.toggle('shadow-xs', active);
                    tab.classList.toggle('text-royal-purple-900', active);
                    tab.classList.toggle('text-cloud-burst-700', !active);
                });
                Object.entries(panels).forEach(([key, panel]) => panel?.classList.toggle('hidden', key !== mode));
                if (subtitle) {
                    subtitle.textContent = subtitles[mode];
                }
            };

            tabs.forEach((tab) => tab.addEventListener('click', () => activate(tab.dataset.entryTab)));
            activate(panels.resume && !panels.resume.classList.contains('hidden') ? 'resume' : 'new');
        });
    </script>
</x-layouts.app>
