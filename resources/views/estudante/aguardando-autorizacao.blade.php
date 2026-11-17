<x-layouts.app title="Aguardando Autorização do Professor · Corujinha">
    <main class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6 bg-white p-8 md:p-10 rounded-3xl shadow-xl border border-magnolia-200 text-center">
            <x-brand-logo variant="horizontal" picture-class="mb-3 block" img-class="h-14 w-auto object-contain mx-auto" />

            <div class="w-16 h-16 mx-auto rounded-3xl bg-amber-50 border-2 border-amber-300 flex items-center justify-center text-amber-700 animate-pulse">
                <x-lucide-shield-alert class="w-8 h-8 text-amber-600" />
            </div>

            <div class="space-y-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold font-heading bg-amber-100 text-amber-900 border border-amber-300">
                    Dispositivo Compartilhado Detectado
                </span>
                <h1 class="text-xl font-extrabold font-heading text-cloud-burst-950">
                    Aguardando Autorização
                </h1>
                <p class="text-xs text-cloud-burst-700 leading-relaxed">
                    Olá, <strong>{{ $attempt->student_name }}</strong>! Este computador ou navegador já foi utilizado nesta sala de avaliação.
                </p>
                <p class="text-xs text-cloud-burst-700 leading-relaxed">
                    Para garantir a integridade da turma, <strong>avise seu professor</strong> para que ele autorize o seu acesso diretamente no painel da sala de aula.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-royal-purple-50/60 border border-royal-purple-200 text-left space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-cloud-burst-600 font-medium">Turma / Sala:</span>
                    <strong class="text-cloud-burst-950 font-heading">{{ $classroom->name }} ({{ $classroom->code }})</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-cloud-burst-600 font-medium">Seu Código de Aluno:</span>
                    <strong class="font-mono text-royal-purple-900 bg-white px-2 py-0.5 rounded border border-royal-purple-200">{{ $attempt->access_code }}</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-cloud-burst-600 font-medium">Status da Liberação:</span>
                    <span id="status-label" class="font-bold text-amber-800 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                        <span>Aguardando o professor...</span>
                    </span>
                </div>
            </div>

            <div class="pt-2 text-center text-[11px] text-cloud-burst-500">
                Esta página será atualizada automaticamente assim que o professor autorizar sua entrada.
            </div>
        </div>
    </main>

    @push('scripts')
        <script>
            (function () {
                const checkUrl = @json(route('estudante.status_autorizacao', ['code' => $classroom->code]));
                const statusLabel = document.getElementById('status-label');

                function pollStatus() {
                    fetch(checkUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.authorized && data.redirect) {
                            if (statusLabel) {
                                statusLabel.innerHTML = '<span class="text-emerald-700 font-bold">Autorizado! Redirecionando...</span>';
                            }
                            window.location.href = data.redirect;
                        }
                    })
                    .catch(() => {});
                }

                // Polling a cada 3 segundos
                const interval = setInterval(pollStatus, 3000);

                // WebSocket listener se Echo estiver ativo
                if (window.Echo) {
                    const monitorChannel = @json('classroom.monitor.' . $classroom->uuid);
                    const attemptId = @json($attempt->uuid);

                    window.Echo.private(monitorChannel)
                        .listen('.student.updated', (e) => {
                            if (e.attempt && e.attempt.id === attemptId && e.attempt.is_device_authorized) {
                                clearInterval(interval);
                                window.location.href = @json(route('estudante.player', ['code' => $classroom->code]));
                            }
                        });
                }
            })();
        </script>
    @endpush
</x-layouts.app>
