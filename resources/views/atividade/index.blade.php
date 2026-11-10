<x-layouts.app title="PREPARA AVALIA+ · 7º ano">
    {{-- Header --}}
    <header class="bg-white border-b border-[#e1dced] px-4 md:px-8 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 m-0">
                PREPARA AVALIA<span class="text-[#bd2388]">+</span> · 7º ano
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Leia o comando. Encontre pistas. Defenda sua escolha.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button id="help" type="button" class="cursor-pointer px-4 py-2.5 rounded-xl bg-white border border-[#d8cee9] text-sm font-bold text-[#30214a] hover:border-brand-purple transition focus-visible:outline-3 focus-visible:outline-brand-purple">
                Como usar
            </button>
            <button id="new-attempt" type="button" class="cursor-pointer px-4 py-2.5 rounded-xl bg-white border border-[#d8cee9] text-sm font-bold text-[#30214a] hover:border-brand-purple transition focus-visible:outline-3 focus-visible:outline-brand-purple">
                Nova tentativa
            </button>
            <button id="export" type="button" class="cursor-pointer px-4 py-2.5 rounded-xl bg-white border border-[#d8cee9] text-sm font-bold text-[#30214a] hover:border-brand-purple transition focus-visible:outline-3 focus-visible:outline-brand-purple">
                Baixar registros
            </button>
            <button id="print" type="button" class="cursor-pointer px-4 py-2.5 rounded-xl bg-white border border-[#d8cee9] text-sm font-bold text-[#30214a] hover:border-brand-purple transition focus-visible:outline-3 focus-visible:outline-brand-purple">
                Imprimir registros
            </button>
        </div>
    </header>

    {{-- Review Mode Banner (Visible only after completing all 10 questions) --}}
    <div id="review-banner" class="hidden mx-4 md:mx-8 mt-4 p-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3">
            <span class="text-3xl">🎉</span>
            <div>
                <h4 class="font-bold text-base m-0 text-white">Teste Concluído! Modo de Revisão e Correção Coletiva Ativo</h4>
                <p class="text-xs text-emerald-100 m-0">Navegue pelas 10 questões livremente e preencha o item "4. Depois da correção coletiva".</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button id="btn-export-top" type="button" class="cursor-pointer px-3.5 py-2 rounded-xl bg-white text-emerald-900 text-xs font-bold hover:bg-emerald-50 transition shadow-xs">
                Baixar Registros
            </button>
            <button id="btn-print-top" type="button" class="cursor-pointer px-3.5 py-2 rounded-xl bg-emerald-800/80 border border-white/30 text-white text-xs font-bold hover:bg-emerald-800 transition">
                Imprimir / PDF
            </button>
        </div>
    </div>

    {{-- Student & Group Identity Bar --}}
    <div class="px-4 md:px-8 py-4 flex flex-wrap items-center justify-between gap-4 border-b border-[#e1dced]/60 bg-white/50 backdrop-blur-xs">
        <div class="flex flex-wrap items-center gap-4">
            <label class="text-xs font-bold text-gray-700 flex flex-col sm:flex-row sm:items-center gap-1.5">
                Nome do estudante
                <input id="student" autocomplete="name" placeholder="Nome do estudante" readonly class="px-3 py-2 text-sm text-[#24304a] bg-gray-50 border border-[#c6bfd6] rounded-lg w-64 max-w-full font-medium">
            </label>

            <label class="text-xs font-bold text-gray-700 flex flex-col sm:flex-row sm:items-center gap-1.5">
                Turma
                <input id="group" placeholder="Turma" readonly class="px-3 py-2 text-sm text-[#24304a] bg-gray-50 border border-[#c6bfd6] rounded-lg w-28 max-w-full font-medium">
            </label>

            <span id="test-status-pill" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                Aguardando início
            </span>
        </div>

        <p class="text-xs text-gray-500 max-w-md m-0">
            Cada abertura começa sem respostas e sem marcações. Baixe seus registros antes de fechar ou atualizar a página.
        </p>
    </div>

    {{-- Questions Navigation --}}
    <div class="px-4 md:px-8 py-4">
        <nav id="nav" aria-label="Navegação entre questões" class="flex flex-wrap gap-2"></nav>
    </div>

    {{-- Main Activity Layout --}}
    <main class="max-w-[1640px] w-full mx-auto px-4 md:px-8 pb-10 grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_280px] gap-6 grow">
        {{-- Work Section --}}
        <section class="min-w-0 flex flex-col gap-5">
            {{-- Question Head --}}
            <div>
                <h2 id="title" class="text-2xl md:text-3xl font-bold text-brand-purple tracking-tight m-0"></h2>
                <div id="meta" class="text-xs md:text-sm text-gray-500 mt-1"></div>
            </div>

            {{-- Intro Callout --}}
            <div id="intro-callout" class="bg-[#e7f8f5] text-[#006053] rounded-2xl p-4 leading-relaxed text-sm border border-[#c4ede5]">
                Você tem <b>3 minutos por questão</b>. O cronômetro inicia automaticamente e <b>não pode ser pausado</b>. Use as cores para marcar: <b>comando</b>, <b>pistas</b> e <b>resposta</b>. Ao clicar em concluir, seu tempo será registrado e você avançará para a próxima questão.
            </div>

            {{-- Interactive Board --}}
            <div id="board" class="relative rounded-2xl p-6 md:p-8 bg-white shadow-sm border border-[#e1d8ef] min-h-[480px] overflow-hidden select-none">
                <div class="grid grid-cols-1 lg:grid-cols-[1.05fr_1fr] gap-8">
                    {{-- Supporting Text / Image --}}
                    <article class="flex flex-col">
                        <p class="text-xs font-bold tracking-wider text-brand-purple mb-3 uppercase">
                            TEXTO DE APOIO
                        </p>
                        <h3 id="source-title" class="text-xl font-bold text-gray-900 leading-snug mb-4"></h3>
                        <p id="source-text" class="text-base md:text-lg leading-relaxed text-gray-800 whitespace-pre-line m-0 select-text"></p>
                        <img id="source-img" hidden alt="Campanha contra o desmatamento com floresta e madeira cortada compondo a palavra desmatar" class="w-full max-h-[510px] object-contain rounded-xl select-none">
                    </article>

                    {{-- Question Prompt & Options --}}
                    <article class="flex flex-col">
                        <p class="text-xs font-bold tracking-wider text-brand-purple mb-3 uppercase">
                            COMANDO DA QUESTÃO
                        </p>
                        <p id="prompt" class="text-lg md:text-xl font-bold text-gray-900 leading-snug mb-5 select-text"></p>
                        <div id="options" class="flex flex-col gap-2"></div>
                    </article>
                </div>
            </div>

            {{-- Reading Record Section --}}
            <section class="bg-white rounded-2xl p-6 md:p-7 shadow-sm border border-[#e1d8ef]">
                <h3 class="text-lg font-bold text-gray-900 m-0">Meu registro de leitura</h3>
                <p class="text-xs md:text-sm text-gray-500 mt-1 mb-4">
                    Explique sua escolha: que palavra do comando orientou a busca e qual pista sustenta a alternativa?
                </p>

                <div class="space-y-4">
                    <div>
                        <label for="keywords" class="text-sm font-bold text-gray-800 block mb-1.5">
                            1. Palavras-chave do comando <span class="text-rose-500">*</span>
                        </label>
                        <input id="keywords" placeholder="Quais palavras mostram o que a questão pede?" class="w-full px-3.5 py-2.5 text-sm text-[#24304a] border border-[#c9c1d7] rounded-xl focus-visible:outline-3 focus-visible:outline-brand-purple">
                    </div>

                    <div>
                        <label for="evidence" class="text-sm font-bold text-gray-800 block mb-1.5">
                            2. Pista encontrada e localização <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="evidence" rows="2" placeholder="Copie um trecho curto ou descreva um detalhe da imagem. Indique onde está." class="w-full px-3.5 py-2.5 text-sm text-[#24304a] border border-[#c9c1d7] rounded-xl resize-y focus-visible:outline-3 focus-visible:outline-brand-purple"></textarea>
                    </div>

                    <div>
                        <label for="reason" class="text-sm font-bold text-gray-800 block mb-1.5">
                            3. Justificativa da alternativa escolhida <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="reason" rows="2" placeholder="Escolhi a alternativa ___ porque a pista “___” mostra que..." class="w-full px-3.5 py-2.5 text-sm text-[#24304a] border border-[#c9c1d7] rounded-xl resize-y focus-visible:outline-3 focus-visible:outline-brand-purple"></textarea>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="reflection" class="text-sm font-bold text-gray-800">
                                4. Depois da correção coletiva
                            </label>
                            <span id="reflection-badge" class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200">
                                🔒 Preencher na revisão pós-teste
                            </span>
                        </div>
                        <textarea id="reflection" rows="2" placeholder="Mantive ou revi minha resposta? O que me confundiu? O que aprendi?" class="w-full px-3.5 py-2.5 text-sm text-[#24304a] border border-[#c9c1d7] rounded-xl resize-y focus-visible:outline-3 focus-visible:outline-brand-purple disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed transition"></textarea>
                    </div>
                </div>

                <div id="record-status" class="text-xs text-gray-500 mt-4" aria-live="polite"></div>

                <div class="flex items-center justify-between gap-3 mt-6 flex-wrap pt-4 border-t border-gray-100">
                    <button id="previous" type="button" class="cursor-pointer px-4 py-2.5 rounded-xl bg-white border border-[#d8cee9] text-sm font-bold text-[#30214a] hover:border-brand-purple transition disabled:opacity-40 disabled:cursor-not-allowed">
                        ← Anterior
                    </button>
                    <button id="finish" type="button" class="cursor-pointer px-6 py-3 rounded-xl bg-brand-purple text-white text-sm font-bold hover:bg-brand-purple-hover transition shadow-sm flex items-center gap-2">
                        <span id="finish-text">Concluir e Ir para a Próxima Questão</span>
                        <span>→</span>
                    </button>
                    <button id="next" type="button" class="cursor-pointer px-4 py-2.5 rounded-xl bg-white border border-[#d8cee9] text-sm font-bold text-[#30214a] hover:border-brand-purple transition disabled:opacity-40 disabled:cursor-not-allowed">
                        Próxima →
                    </button>
                </div>
            </section>
        </section>

        {{-- Sticky Side Controls --}}
        <aside class="sticky top-4 self-start flex flex-col gap-4">
            {{-- Timer Card --}}
            <section class="bg-white rounded-2xl p-5 border border-[#e1d8ef] shadow-sm text-center">
                <h3 class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                    TEMPO DA QUESTÃO
                </h3>
                <div id="clock" role="timer" class="text-5xl font-bold font-mono tracking-tight text-[#38215f] leading-none my-2 transition-colors">
                    03:00
                </div>
                <div id="timer-badge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 mb-3">
                    ⏱️ Cronômetro Contínuo
                </div>
                <small id="timer-status" class="block text-xs text-gray-500 mb-2">
                    Tempo correndo automaticamente
                </small>
                <p id="elapsed" class="text-xs text-gray-500 m-0">
                    Tempo utilizado: 00:00
                </p>
            </section>

            {{-- Color Palette Card --}}
            <section class="bg-white rounded-2xl p-5 border border-[#e1d8ef] shadow-sm">
                <h3 class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                    FAIXAS COLORIDAS
                </h3>
                <div class="space-y-2">
                    <button data-add="command" type="button" class="cursor-pointer w-full text-left flex items-center gap-2.5 px-3 py-2 rounded-xl border border-[#d8cee9] text-xs font-bold text-gray-800 hover:border-brand-purple transition">
                        <span class="w-7 h-4 rounded-xs border border-black/10 shrink-0" style="background:#fff000"></span>
                        Comando (Amarelo)
                    </button>
                    <button data-add="evidence" type="button" class="cursor-pointer w-full text-left flex items-center gap-2.5 px-3 py-2 rounded-xl border border-[#d8cee9] text-xs font-bold text-gray-800 hover:border-brand-purple transition">
                        <span class="w-7 h-4 rounded-xs border border-black/10 shrink-0" style="background:#00d9ff"></span>
                        Pistas (Azul)
                    </button>
                    <button data-add="answer" type="button" class="cursor-pointer w-full text-left flex items-center gap-2.5 px-3 py-2 rounded-xl border border-[#d8cee9] text-xs font-bold text-gray-800 hover:border-brand-purple transition">
                        <span class="w-7 h-4 rounded-xs border border-black/10 shrink-0" style="background:#ff20b5"></span>
                        Resposta (Rosa)
                    </button>
                </div>

                <p class="text-xs text-gray-500 leading-relaxed mt-3 mb-3">
                    Clique em uma cor para criar uma faixa sobre o texto. Arraste e puxe a borda direita para ajustar a largura.
                </p>

                <div class="flex items-center gap-1.5 flex-wrap">
                    <button id="duplicate" type="button" class="cursor-pointer px-2.5 py-1.5 rounded-lg border border-[#d8cee9] text-[11px] font-bold text-gray-700 hover:border-brand-purple transition">
                        Duplicar
                    </button>
                    <button id="remove" type="button" class="cursor-pointer px-2.5 py-1.5 rounded-lg border border-[#d8cee9] text-[11px] font-bold text-gray-700 hover:border-brand-purple transition">
                        Excluir faixa
                    </button>
                    <button id="undo" type="button" class="cursor-pointer px-2.5 py-1.5 rounded-lg border border-[#d8cee9] text-[11px] font-bold text-gray-700 hover:border-brand-purple transition">
                        Desfazer
                    </button>
                </div>

                <p class="text-[11px] text-gray-400 leading-relaxed mt-3 m-0">
                    Com a faixa selecionada: setas movem; Shift + setas move mais; Delete exclui.
                </p>
            </section>
        </aside>
    </main>

    {{-- Footer --}}
    <footer class="mt-auto py-5 text-center text-xs text-gray-500 border-t border-[#e1dced]/80 bg-white/40">
        Atividade de contraturno · AVALIA+ 2026 · Sem envio automático de dados
    </footer>

    {{-- Toast Notification --}}
    <div id="toast" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-[#302148] text-white text-sm px-5 py-3 rounded-xl shadow-lg max-w-[90vw] transition-all" role="status"></div>

    {{-- Start Test Gatekeeper Dialog (Mandatory to fill Name and Turma) --}}
    <dialog id="start-dialog" class="rounded-3xl p-6 md:p-8 max-w-lg w-[92vw] text-[#24304a] shadow-2xl border border-purple-100 m-auto">
        <div class="text-center mb-6">
            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-brand-purple mb-2">
                SESI · AVALIA+ 2026
            </span>
            <h2 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
                PREPARA AVALIA+ · 7º ano
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Preencha seus dados para iniciar o teste cronometrado
            </p>
        </div>

        <form id="start-form" class="space-y-4">
            <div>
                <label for="init-student" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                    Nome completo do estudante <span class="text-rose-500">*</span>
                </label>
                <input id="init-student" required placeholder="Digite seu nome completo" class="w-full px-4 py-3 text-sm text-[#24304a] border border-[#c6bfd6] rounded-xl focus:border-brand-purple focus:outline-none focus:ring-2 focus:ring-brand-purple/20 transition">
            </div>

            <div>
                <label for="init-group" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                    Turma / Classe <span class="text-rose-500">*</span>
                </label>
                <input id="init-group" required placeholder="Ex.: 7º B" class="w-full px-4 py-3 text-sm text-[#24304a] border border-[#c6bfd6] rounded-xl focus:border-brand-purple focus:outline-none focus:ring-2 focus:ring-brand-purple/20 transition">
            </div>

            <div class="bg-purple-50/80 rounded-2xl p-4 border border-purple-100 text-xs text-purple-950 space-y-2">
                <div class="flex items-start gap-2">
                    <span class="text-base">⏱️</span>
                    <span><b>Cronômetro contínuo:</b> 3 minutos por questão. Inicia assim que você clicar no botão e <b>não pode ser pausado</b>.</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-base">🎨</span>
                    <span><b>Faixas coloridas:</b> use as faixas para destacar o comando, as pistas e a resposta.</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-base">⏩</span>
                    <span><b>Avanço automático:</b> ao concluir a questão, seu tempo é registrado e a próxima questão começa imediatamente.</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-base">🔍</span>
                    <span><b>Revisão final:</b> após finalizar as 10 questões, você poderá revisar e preencher o item "Depois da correção coletiva".</span>
                </div>
            </div>

            <button id="btn-start-test" type="submit" class="w-full cursor-pointer py-3.5 px-6 rounded-xl bg-brand-purple hover:bg-brand-purple-hover text-white font-bold text-base shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                <span>Iniciar Teste</span>
                <span class="text-lg">→</span>
            </button>
        </form>
    </dialog>

    {{-- Finish Confirmation Dialog --}}
    <dialog id="finish-dialog" class="rounded-3xl p-6 md:p-8 max-w-lg w-[92vw] text-[#24304a] shadow-2xl border border-emerald-100 m-auto">
        <div class="text-center mb-6">
            <span class="text-5xl block mb-2">🎉</span>
            <h2 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">
                Parabéns!
            </h2>
            <p id="finish-dialog-subtitle" class="text-sm text-gray-600 mt-1">
                Você concluiu todas as 10 questões do PREPARA AVALIA+.
            </p>
        </div>

        <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-200 text-xs text-emerald-950 space-y-2 mb-6">
            <p class="font-bold text-sm text-emerald-900 m-0">Próximos passos:</p>
            <p class="m-0">1. O teste agora está no <b>Modo de Revisão</b>.</p>
            <p class="m-0">2. Durante a correção coletiva com a professora, navegue pelas questões e preencha o campo <b>"4. Depois da correção coletiva"</b>.</p>
            <p class="m-0">3. Não se esqueça de <b>Baixar seus Registros</b> ao final da aula.</p>
        </div>

        <div class="flex items-center justify-end gap-3">
            <button id="close-finish-dialog" type="button" class="w-full cursor-pointer py-3.5 px-6 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-md transition text-center">
                Começar Revisão e Correção Coletiva →
            </button>
        </div>
    </dialog>

    {{-- Instructions Modal Dialog --}}
    <dialog id="instructions" class="rounded-2xl p-6 md:p-8 max-w-xl w-[90vw] text-[#24304a] shadow-2xl border-0 m-auto">
        <h2 class="text-2xl font-bold text-brand-purple mb-4">Como fazer a atividade</h2>
        <ol class="list-decimal pl-5 space-y-2 text-sm leading-relaxed text-gray-700">
            <li>Preencha seu nome e turma para iniciar o teste.</li>
            <li>Em cada questão, você tem 3 minutos contínuos para ler, marcar e registrar. <b>O cronômetro não pausa</b>.</li>
            <li>Clique em uma cor para criar uma faixa e arraste-a sobre o comando, uma pista ou a resposta. Você pode criar várias faixas.</li>
            <li>Marque a alternativa correta e escreva as palavras-chave, pistas e sua justificativa.</li>
            <li>Clique em <b>Concluir e Ir para a Próxima Questão</b>. O tempo é registrado e a próxima questão inicia automaticamente.</li>
            <li>Após responder as 10 questões, o <b>Modo de Revisão</b> é liberado para você registrar o que aprendeu no item 4 com a professora.</li>
            <li>Use <b>Baixar registros</b> para salvar as respostas em um arquivo de texto. Use <b>Imprimir registros</b> para imprimir ou salvar em PDF pelo navegador.</li>
        </ol>
        <div class="text-right mt-6">
            <button id="close-help" type="button" class="cursor-pointer px-5 py-2.5 rounded-xl bg-brand-purple text-white text-sm font-bold hover:bg-brand-purple-hover transition shadow-sm">
                Entendi
            </button>
        </div>
    </dialog>

    {{-- Question Data --}}
    <script>
        window.__ATIVIDADE_DATA__ = @json($questions);
    </script>
</x-layouts.app>
