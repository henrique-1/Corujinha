<x-layouts.app :title="$classroom->name . ' · Corujinha'">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Header --}}
    <header class="bg-white border-b border-magnolia-200 px-4 md:px-8 py-3 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xs">
        <div class="flex items-center gap-4">
            <x-brand-logo variant="horizontal" img-class="h-10 w-auto object-contain" />
            <div class="border-l border-magnolia-300 pl-4">
                <h1 class="text-base font-bold font-heading text-cloud-burst-950 m-0 leading-tight">
                    {{ $classroom->name }}
                </h1>
                <p class="text-xs text-cloud-burst-700 mt-0.5">
                    {{ $classroom->assessment->title }}
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            @if($isTeacherPreview ?? false)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold font-heading bg-amber-100 text-amber-950 border border-amber-300 shadow-2xs">
                    <x-lucide-eye class="w-3.5 h-3.5 text-amber-800" />
                    <span>Visualização do Professor (Avanço Livre)</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-mono font-bold bg-broom-100 text-broom-950 border border-broom-300">
                    <span>Turma:</span> <span>{{ $classroom->code }}</span>
                </span>
                <a href="{{ route('professor.salas.show', $classroom) }}" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold font-heading bg-royal-purple-800 hover:bg-royal-purple-900 text-white transition shadow-2xs">
                    <x-lucide-arrow-left class="w-3.5 h-3.5" />
                    <span>Voltar à Sala</span>
                </a>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-900 border border-royal-purple-200">
                    <x-lucide-user class="w-3.5 h-3.5 text-royal-purple-800" />
                    <span>{{ $attempt?->student_name }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono font-bold bg-broom-100 text-broom-950 border border-broom-300">
                    <span>Turma:</span> <span>{{ $classroom->code }}</span>
                </span>
                {{-- Código de Retomada do Aluno --}}
                <div class="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 rounded-full text-xs font-bold bg-sky-blue-50 text-sky-blue-950 border border-sky-blue-300 shadow-2xs" title="Anote este código para voltar de onde parou em outra aula">
                    <x-lucide-key class="w-3.5 h-3.5 text-sky-blue-700 shrink-0" />
                    <span class="hidden sm:inline text-sky-blue-800 font-medium">Seu Código:</span>
                    <span class="font-mono font-black tracking-wider text-sky-blue-950 select-all">{{ $attempt?->access_code }}</span>
                    <button type="button" onclick="navigator.clipboard?.writeText('{{ $attempt?->access_code }}'); this.innerText='Copiado!'; setTimeout(() => this.innerText='Copiar', 2000)" class="cursor-pointer ml-1 px-2 py-0.5 rounded-md bg-sky-blue-200 hover:bg-sky-blue-300 text-sky-blue-900 text-[11px] font-heading font-bold transition">
                        Copiar
                    </button>
                </div>
                {{-- Botão Sair e Continuar Depois --}}
                <button type="button" onclick="document.getElementById('modal-continuar-depois')?.classList.remove('hidden')" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold font-heading bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 transition shadow-2xs">
                    <x-lucide-bookmark class="w-3.5 h-3.5 text-gray-600" />
                    <span class="hidden md:inline">Sair e Continuar Depois</span>
                    <span class="md:hidden">Sair</span>
                </button>
            @endif
        </div>
    </header>

    {{-- Questions Navigation --}}
    <div class="px-4 md:px-8 py-3 bg-white/60 border-b border-magnolia-200">
        <nav id="nav" aria-label="Navegação entre questões" class="flex flex-wrap gap-2"></nav>
    </div>

    {{-- Main Activity Layout --}}
    <main class="max-w-[1640px] w-full mx-auto px-4 md:px-8 py-6 grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_300px] gap-6 grow">
        {{-- Work Section --}}
        <section class="min-w-0 flex flex-col gap-5">
            {{-- Question Head --}}
            <div>
                <h2 id="title" class="text-2xl md:text-3xl font-bold font-heading text-royal-purple-800 tracking-tight m-0"></h2>
                <div id="meta" class="text-xs md:text-sm text-cloud-burst-700 mt-1"></div>
            </div>

            {{-- Requirement Callout --}}
            <div id="requirement-callout" class="bg-royal-purple-50 text-royal-purple-950 rounded-2xl p-4 leading-relaxed text-xs md:text-sm border border-royal-purple-200 flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <x-lucide-highlighter class="w-4 h-4 text-royal-purple-800 shrink-0" />
                    <span><b>Instruções desta questão:</b> Use as cores para marcar: <b>comando (amarelo)</b> e <b>pistas (azul)</b> no texto.</span>
                    <span id="req-badges" class="inline-flex items-center gap-1.5 ml-1"></span>
                </div>
            </div>

            {{-- Interactive Board --}}
            <div id="board" class="relative rounded-3xl p-6 md:p-8 bg-white shadow-xs border border-magnolia-200 min-h-[480px]">
                <div class="grid grid-cols-1 lg:grid-cols-[1.05fr_1fr] gap-8">
                    {{-- Supporting Text / Image --}}
                    <article class="flex flex-col">
                        <p class="text-xs font-bold font-heading tracking-wider text-royal-purple-800 mb-3 uppercase">
                            TEXTO DE APOIO
                        </p>
                        <h3 id="source-title" class="text-xl font-bold font-heading text-cloud-burst-950 leading-snug mb-4 select-text"></h3>
                        <div id="source-text" class="rich-text-content text-base md:text-lg leading-relaxed text-cloud-burst-950 mb-4 select-text"></div>
                        <img id="source-img" hidden alt="Imagem de apoio da questão" class="w-full max-h-[510px] object-contain rounded-2xl select-none border border-magnolia-200 shadow-xs">
                    </article>

                    {{-- Question Prompt, Options & Justification --}}
                    <article class="flex flex-col">
                        <p class="text-xs font-bold font-heading tracking-wider text-royal-purple-800 mb-3 uppercase">
                            COMANDO DA QUESTÃO
                        </p>
                        <p id="prompt" class="text-lg md:text-xl font-bold font-heading text-cloud-burst-950 leading-snug mb-5 select-text"></p>
                        <div id="options" class="flex flex-col gap-2.5"></div>

                        {{-- 3. Justificativa posicionada imediatamente abaixo das alternativas --}}
                        <div class="mt-6 pt-5 border-t border-magnolia-200">
                            <label for="reason" class="text-sm font-bold font-heading text-cloud-burst-950 block mb-1.5">
                                3. Justificativa da alternativa escolhida <span class="text-shocking-pink-600">*</span>
                            </label>
                            <textarea id="reason" rows="2" placeholder="Escolhi a alternativa ___ porque a pista “___” mostra que..."
                                      class="w-full px-3.5 py-2.5 text-sm text-cloud-burst-950 border border-magnolia-300 rounded-xl resize-y focus-visible:outline-3 focus-visible:outline-royal-purple-800 bg-magnolia-50/50"></textarea>
                        </div>
                    </article>
                </div>
            </div>

            {{-- Action & Navigation Bar --}}
            <section class="bg-white rounded-2xl p-5 md:p-6 shadow-xs border border-magnolia-200 flex items-center justify-between gap-3 flex-wrap">
                <button id="previous" type="button" class="cursor-pointer px-4 py-2.5 rounded-xl bg-white border border-magnolia-300 text-sm font-bold font-heading text-cloud-burst-900 hover:border-royal-purple-800 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5">
                    <x-lucide-arrow-left class="w-4 h-4" />
                    <span>Anterior</span>
                </button>
                <button id="finish" type="button" class="cursor-pointer px-6 py-3 rounded-xl bg-royal-purple-800 text-white text-sm font-bold font-heading hover:bg-royal-purple-900 transition shadow-sm flex items-center gap-2">
                    <span id="finish-text">Concluir e Ir para a Próxima Questão</span>
                    <x-lucide-arrow-right class="w-4 h-4" />
                </button>
                <button id="next" type="button" class="cursor-pointer px-4 py-2.5 rounded-xl bg-white border border-magnolia-300 text-sm font-bold font-heading text-cloud-burst-900 hover:border-royal-purple-800 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5">
                    <span>Próxima</span>
                    <x-lucide-arrow-right class="w-4 h-4" />
                </button>
            </section>
        </section>

        {{-- Sticky Side Controls --}}
        <aside class="sticky top-4 self-start flex flex-col gap-4">
            {{-- Timer Card --}}
            <section class="bg-white rounded-2xl p-5 border border-magnolia-200 shadow-xs text-center">
                <h3 class="text-xs font-bold font-heading text-cloud-burst-700 uppercase tracking-wider mb-2">
                    TEMPO DA QUESTÃO
                </h3>
                <div id="clock" role="timer" class="text-5xl font-bold font-mono tracking-tight text-royal-purple-950 leading-none my-2 transition-colors">
                    03:00
                </div>
                <div id="timer-badge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-broom-100 text-broom-950 border border-broom-300 mb-3">
                    <x-lucide-timer class="w-3.5 h-3.5 text-broom-800" />
                    <span>Cronômetro Contínuo</span>
                </div>
                <small id="timer-status" class="block text-xs text-cloud-burst-600 mb-2">
                    Tempo correndo automaticamente
                </small>
                <p id="elapsed" class="text-xs text-cloud-burst-600 m-0">
                    Tempo utilizado: 00:00
                </p>
            </section>

            {{-- Color Palette Card --}}
            <section class="bg-white rounded-2xl p-5 border border-magnolia-200 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold font-heading text-cloud-burst-800 uppercase tracking-wider m-0">
                        FAIXAS COLORIDAS · MARCA-TEXTO
                    </h3>
                </div>
                <div class="space-y-2">
                    <button data-add="command" type="button" class="cursor-pointer w-full text-left flex items-center gap-2.5 px-3 py-2 rounded-xl border border-magnolia-300 text-xs font-bold font-heading text-cloud-burst-950 hover:border-royal-purple-800 transition">
                        <span class="w-7 h-4 rounded-xs border border-black/10 shrink-0" style="background:#fff000"></span>
                        <span>Comando (Amarelo)</span>
                    </button>
                    <button data-add="evidence" type="button" class="cursor-pointer w-full text-left flex items-center gap-2.5 px-3 py-2 rounded-xl border border-magnolia-300 text-xs font-bold font-heading text-cloud-burst-950 hover:border-royal-purple-800 transition">
                        <span class="w-7 h-4 rounded-xs border border-black/10 shrink-0" style="background:#00d9ff"></span>
                        <span>Pistas (Azul)</span>
                    </button>
                </div>

                <p class="text-xs text-cloud-burst-700 leading-relaxed mt-3 mb-3">
                    Selecione um trecho do texto com o mouse ou dedo e clique na cor desejada.
                </p>

                <div class="pt-1">
                    <button id="clear-all" type="button" class="cursor-pointer w-full text-center py-2 px-3 rounded-xl border border-rose-200 text-xs font-bold font-heading text-rose-700 hover:border-rose-400 hover:bg-rose-50 transition flex items-center justify-center gap-1.5">
                        <x-lucide-trash-2 class="w-3.5 h-3.5" />
                        <span>Limpar destaques</span>
                    </button>
                </div>
            </section>
        </aside>
    </main>

    {{-- Floating Selection Toolbar --}}
    <div id="selection-toolbar" class="hidden fixed z-50 flex items-center gap-1.5 p-1.5 bg-royal-purple-950 text-white rounded-xl shadow-2xl border border-royal-purple-700 transition-all text-xs font-bold">
        <button id="toolbar-cmd" type="button" class="cursor-pointer flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-broom-500 text-broom-950 hover:bg-broom-400 font-bold transition">
            <span class="w-2.5 h-2.5 rounded-full bg-broom-700"></span>
            <span>Comando</span>
        </button>
        <button id="toolbar-evi" type="button" class="cursor-pointer flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-sky-blue-400 text-sky-blue-950 hover:bg-sky-blue-300 font-bold transition">
            <span class="w-2.5 h-2.5 rounded-full bg-sky-blue-700"></span>
            <span>Pistas</span>
        </button>
        <button id="toolbar-cancel" type="button" aria-label="Fechar" class="cursor-pointer text-gray-300 hover:text-white px-2 py-1 flex items-center justify-center">
            <x-lucide-x class="w-3.5 h-3.5" />
        </button>
    </div>

    {{-- Highlight Options Popover --}}
    <div id="highlight-popover" class="hidden fixed z-50 flex items-center gap-2 p-2 bg-royal-purple-950 text-white rounded-xl shadow-2xl border border-royal-purple-700 transition-all text-xs font-bold">
        <span id="popover-label" class="text-gray-300 text-[11px] pl-1 font-normal"></span>
        <button id="popover-change-cmd" type="button" class="cursor-pointer px-2 py-1 rounded bg-broom-500 text-broom-950 text-[11px] font-bold">Amarelo</button>
        <button id="popover-change-evi" type="button" class="cursor-pointer px-2 py-1 rounded bg-sky-blue-400 text-sky-blue-950 text-[11px] font-bold">Azul</button>
        <button id="popover-remove" type="button" class="cursor-pointer px-2.5 py-1 rounded bg-rose-600 text-white text-[11px] font-bold">Remover</button>
        <button id="popover-close" type="button" aria-label="Fechar" class="cursor-pointer text-gray-400 hover:text-white px-1 flex items-center justify-center">
            <x-lucide-x class="w-3 h-3" />
        </button>
    </div>

    @if(!($isTeacherPreview ?? false) && $attempt)
    {{-- Modal Sair e Continuar Mais Tarde --}}
    <div id="modal-continuar-depois" class="hidden fixed inset-0 z-50 overflow-y-auto bg-royal-purple-950/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 md:p-8 shadow-2xl border border-royal-purple-200 flex flex-col gap-5 text-cloud-burst-950 relative animate-in fade-in zoom-in-95 duration-150">
            <button type="button" onclick="document.getElementById('modal-continuar-depois')?.classList.add('hidden')" aria-label="Fechar" class="cursor-pointer absolute top-5 right-5 text-gray-400 hover:text-gray-700 p-1 rounded-lg">
                <x-lucide-x class="w-5 h-5" />
            </button>

            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-sky-blue-100 text-sky-blue-700 flex items-center justify-center shrink-0">
                    <x-lucide-bookmark class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-lg md:text-xl font-bold font-heading text-royal-purple-950 leading-snug">
                        Sair e Continuar Mais Tarde
                    </h3>
                    <p class="text-xs text-cloud-burst-700">
                        Suas respostas já preenchidas são salvas automaticamente!
                    </p>
                </div>
            </div>

            <div class="bg-royal-purple-50 rounded-2xl p-4 border border-royal-purple-200/80">
                <p class="text-xs font-bold uppercase tracking-wider text-royal-purple-800 mb-1">
                    Anote seu código pessoal no caderno:
                </p>
                <div class="flex items-center justify-between gap-3 bg-white px-4 py-3 rounded-xl border border-royal-purple-300 shadow-inner my-2">
                    <span id="access-code-modal" class="text-2xl md:text-3xl font-mono font-black tracking-widest text-royal-purple-950 select-all">
                        {{ $attempt->access_code }}
                    </span>
                    <button type="button" id="copy-access-code-btn" onclick="navigator.clipboard?.writeText('{{ $attempt->access_code }}'); this.innerText='Copiado!'; setTimeout(() => this.innerText='Copiar', 2000)" class="cursor-pointer px-3.5 py-1.5 rounded-lg bg-sky-blue-500 hover:bg-sky-blue-600 text-sky-blue-950 text-xs font-bold font-heading transition">
                        Copiar
                    </button>
                </div>
                <div class="flex items-center gap-2 mt-2 text-xs text-royal-purple-900">
                    <x-lucide-info class="w-4 h-4 shrink-0 text-royal-purple-700" />
                    <span>Turma: <strong class="font-mono">{{ $classroom->code }}</strong> &bull; Aluno: <strong>{{ $attempt->student_name }}</strong></span>
                </div>
            </div>

            <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200 text-xs text-gray-700 space-y-1.5">
                <p class="font-bold font-heading text-gray-900">Como voltar em uma próxima aula:</p>
                <ol class="list-decimal list-inside space-y-1 text-gray-600 pl-1">
                    <li>Acesse a página inicial de entrada (<strong>/sala</strong>).</li>
                    <li>Clique na aba <strong>"Voltar a uma sala"</strong>.</li>
                    <li>Digite o código da turma (<strong>{{ $classroom->code }}</strong>) e o seu código anotado.</li>
                </ol>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('modal-continuar-depois')?.classList.add('hidden')" class="cursor-pointer w-full sm:w-auto px-4 py-2.5 rounded-xl border border-gray-300 text-xs font-bold font-heading text-gray-700 hover:bg-gray-100 transition text-center">
                    Continuar Respondendo Agora
                </button>
                <a href="{{ route('estudante.entrar') }}" class="cursor-pointer w-full sm:w-auto px-5 py-2.5 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white text-xs font-bold font-heading transition shadow-xs text-center flex items-center justify-center gap-2">
                    <x-lucide-log-out class="w-4 h-4" />
                    <span>Sair da Avaliação</span>
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- Toast Notification --}}
    <div id="toast" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-royal-purple-950 text-white text-sm px-5 py-3 rounded-xl shadow-lg max-w-[90vw] transition-all"></div>

    {{-- Injeta dados e inicializador do Player --}}
    <script>
        window.__CLASSROOM_DATA__ = {
            broadcastKey: @json($classroom->broadcast_key),
            code: @json($classroom->code),
            saveUrl: @json(route('estudante.salvar_resposta', $classroom->code)),
            finishUrl: @json(route('estudante.concluir_atividade', $classroom->code)),
            questions: @json($questionsData),
            collectiveReviewEnabled: @json($classroom->collective_review_enabled),
            isTeacherPreview: @json($isTeacherPreview ?? false),
            backUrl: @json(route('professor.salas.show', $classroom)),
        };
    </script>
</x-layouts.app>
