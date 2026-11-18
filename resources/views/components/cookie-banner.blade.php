<div id="cookie-banner" class="hidden fixed bottom-4 left-4 right-4 md:left-auto md:right-6 md:max-w-md z-50 animate-slide-up">
    <div class="bg-royal-purple-950 text-white p-5 rounded-3xl border border-royal-purple-800/80 shadow-2xl backdrop-blur-md space-y-3.5">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-2xl bg-royal-purple-900/90 border border-royal-purple-700/60 flex items-center justify-center shrink-0 text-broom-400">
                <x-lucide-cookie class="w-5 h-5 text-broom-400" />
            </div>
            <div class="space-y-1 grow min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold font-heading uppercase tracking-wider text-broom-400">Privacidade & Cookies (LGPD)</span>
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-royal-purple-200 bg-royal-purple-900/80 px-2 py-0.5 rounded-full border border-royal-purple-700/50">
                        <x-lucide-shield-check class="w-3 h-3 text-emerald-400" />
                        <span>Lei 13.709/18</span>
                    </span>
                </div>
                <p class="text-xs text-royal-purple-100/90 leading-relaxed">
                    Utilizamos cookies e identificadores de dispositivo estritamente necessários para manter sua sessão segura, autenticar respostas e garantir a integridade pedagógica das salas de avaliação.
                </p>
            </div>
        </div>

        {{-- Detalhes expansíveis --}}
        <details class="text-[11px] text-royal-purple-200 group font-sans">
            <summary class="cursor-pointer text-broom-300 hover:text-broom-200 font-semibold flex items-center gap-1 select-none py-0.5">
                <x-lucide-chevron-right class="w-3 h-3 transition group-open:rotate-90 shrink-0" />
                <span>Ver detalhes das categorias de dados</span>
            </summary>
            <div class="mt-2 pl-3 border-l-2 border-broom-400/40 space-y-1 text-royal-purple-200/80 leading-normal">
                <p>• <strong>Sessão & Segurança:</strong> Cookies de autenticação CSRF e token de sessão do usuário.</p>
                <p>• <strong>Identificador de Dispositivo:</strong> Identificação de máquina/navegador para controle de respostas únicas e turmas com laboratório compartilhado.</p>
                <p>• <strong>Métricas & Usabilidade (Microsoft Clarity):</strong> Análise anônima de navegação e mapa de calor para aprimoramento da experiência pedagógica.</p>
            </div>
        </details>

        <div class="flex items-center justify-end gap-2 pt-1 border-t border-royal-purple-900/60">
            <button id="btn-accept-cookies" type="button" class="cursor-pointer w-full sm:w-auto px-5 py-2 rounded-xl bg-broom-400 hover:bg-broom-300 text-royal-purple-950 font-bold font-heading text-xs shadow-sm transition flex items-center justify-center gap-1.5">
                <x-lucide-check class="w-3.5 h-3.5" />
                <span>Aceitar e Continuar</span>
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        const banner = document.getElementById('cookie-banner');
        const btnAccept = document.getElementById('btn-accept-cookies');
        const storageKey = 'corujinha_lgpd_consent';

        if (!localStorage.getItem(storageKey)) {
            banner?.classList.remove('hidden');
        }

        btnAccept?.addEventListener('click', function () {
            localStorage.setItem(storageKey, 'accepted');
            document.cookie = storageKey + '=accepted; path=/; max-age=31536000; SameSite=Lax';
            banner?.classList.add('hidden');
        });
    })();
</script>
