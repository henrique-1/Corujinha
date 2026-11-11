@props(['value' => ''])

<div class="rounded-xl border border-magnolia-300 bg-white focus-within:border-royal-purple-800 transition overflow-hidden">
    <div id="support-text-toolbar" role="toolbar" aria-label="Formatação do Corpo do Texto de Apoio" class="flex flex-wrap items-center gap-1.5 px-3 py-2 bg-magnolia-50/70 border-b border-magnolia-200">
        <button type="button" data-rte-action="bold" aria-pressed="false" title="Negrito (Ctrl+B)"
                class="cursor-pointer inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-magnolia-300 bg-white text-cloud-burst-800 text-xs font-bold hover:border-royal-purple-500 transition">
            <x-lucide-bold class="w-3.5 h-3.5" />
            <span class="sr-only">Negrito</span>
        </button>

        <button type="button" data-rte-action="italic" aria-pressed="false" title="Itálico (Ctrl+I)"
                class="cursor-pointer inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-magnolia-300 bg-white text-cloud-burst-800 text-xs font-bold hover:border-royal-purple-500 transition">
            <x-lucide-italic class="w-3.5 h-3.5" />
            <span class="sr-only">Itálico</span>
        </button>

        <button type="button" data-rte-action="underline" aria-pressed="false" title="Sublinhado (Ctrl+U)"
                class="cursor-pointer inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-magnolia-300 bg-white text-cloud-burst-800 text-xs font-bold hover:border-royal-purple-500 transition">
            <x-lucide-underline class="w-3.5 h-3.5" />
            <span class="sr-only">Sublinhado</span>
        </button>

        <button type="button" data-rte-action="strike" aria-pressed="false" title="Taxado"
                class="cursor-pointer inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-magnolia-300 bg-white text-cloud-burst-800 text-xs font-bold hover:border-royal-purple-500 transition">
            <x-lucide-strikethrough class="w-3.5 h-3.5" />
            <span class="sr-only">Taxado</span>
        </button>

        <span class="w-px h-5 bg-magnolia-300 mx-1" aria-hidden="true"></span>

        <button type="button" data-rte-action="link" aria-pressed="false" title="Inserir ou editar hyperlink"
                class="cursor-pointer inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-magnolia-300 bg-white text-cloud-burst-800 text-xs font-bold font-heading hover:border-royal-purple-500 transition">
            <x-lucide-link class="w-3.5 h-3.5" />
            <span>Hyperlink</span>
        </button>
    </div>

    <div id="support-text-editor" class="bg-white"></div>

    <textarea id="support_text" name="support_text" class="hidden">{{ $value }}</textarea>
</div>

<dialog id="rte-link-dialog" aria-labelledby="rte-link-dialog-title" closedby="any"
        class="m-auto rounded-2xl border border-magnolia-200 bg-white p-6 shadow-2xl w-full max-w-md text-left">
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 id="rte-link-dialog-title" class="text-base font-bold font-heading text-cloud-burst-950 m-0">
                Inserir Hyperlink
            </h3>
        </div>

        <div>
            <label for="rte-link-url" class="block text-xs font-bold font-heading text-cloud-burst-900 uppercase tracking-wider mb-1.5">
                Endereço (URL)
            </label>
            <input id="rte-link-url" type="url" placeholder="https://exemplo.com.br/artigo"
                   class="w-full px-3.5 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
            <p id="rte-link-error" class="hidden text-xs text-rose-600 font-medium mt-1.5"></p>
        </div>

        <div class="flex items-center justify-between gap-2 pt-2">
            <button id="rte-link-remove" type="button" class="hidden cursor-pointer px-3 py-2 rounded-xl border border-rose-300 text-rose-700 hover:bg-rose-50 text-xs font-bold font-heading transition">
                Remover Link
            </button>
            <div class="flex items-center gap-2 ml-auto">
                <button id="rte-link-cancel" type="button" class="cursor-pointer px-4 py-2 rounded-xl border border-gray-300 text-cloud-burst-800 text-xs font-bold font-heading hover:bg-gray-50 transition">
                    Cancelar
                </button>
                <button id="rte-link-apply" type="button" class="cursor-pointer px-4 py-2 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white text-xs font-bold font-heading transition">
                    Aplicar Link
                </button>
            </div>
        </div>
    </div>
</dialog>
