<x-layouts.app :title="$classroom->name . ' · Corujinha'">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Header --}}
    <header class="bg-white border-b border-magnolia-200 px-4 md:px-8 py-3 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xs">
        <div class="flex items-center gap-4">
            <picture>
                <source srcset="{{ asset('images/logo/logo-horizontal.avif') }}" type="image/avif">
                <source srcset="{{ asset('images/logo/logo-horizontal.webp') }}" type="image/webp">
                <img src="{{ asset('images/logo/logo-horizontal.webp') }}" alt="Corujinha" class="h-10 w-auto object-contain">
            </picture>
            <div class="border-l border-magnolia-300 pl-4">
                <h1 class="text-base font-bold font-heading text-cloud-burst-950 m-0 leading-tight">
                    {{ $classroom->name }}
                </h1>
                <p class="text-xs text-cloud-burst-700 mt-0.5">
                    {{ $classroom->assessment->title }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-900 border border-royal-purple-200">
                <x-lucide-user class="w-3.5 h-3.5 text-royal-purple-800" />
                <span>{{ $attempt->student_name }}</span>
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono font-bold bg-broom-100 text-broom-950 border border-broom-300">
                <span>Turma:</span> <span>{{ $classroom->code }}</span>
            </span>
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

    {{-- Toast Notification --}}
    <div id="toast" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-royal-purple-950 text-white text-sm px-5 py-3 rounded-xl shadow-lg max-w-[90vw] transition-all"></div>

    {{-- Injeta dados e inicializador do Player --}}
    <script>
        window.__CLASSROOM_DATA__ = {
            code: @json($classroom->code),
            saveUrl: @json(route('estudante.salvar_resposta', $classroom->code)),
            finishUrl: @json(route('estudante.concluir_atividade', $classroom->code)),
            questions: @json($questionsData),
            collectiveReviewEnabled: @json($classroom->collective_review_enabled),
        };
    </script>
</x-layouts.app>
