<x-layouts.admin title="Nova Pergunta · Corujinha AVALIA+">
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <a href="{{ route('professor.perguntas.index') }}" class="text-xs font-bold font-heading text-royal-purple-800 hover:underline mb-2 inline-flex items-center gap-1">
                <x-lucide-arrow-left class="w-3.5 h-3.5" />
                <span>Voltar para o Banco de Perguntas</span>
            </a>
            <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                Criar Nova Pergunta Pedagógica
            </h1>
            <p class="text-sm text-cloud-burst-700 mt-1">
                Cadastre o texto ou imagem de apoio, o comando da questão, a exigência de marca-texto e as devolutivas
            </p>
        </div>

        <form action="{{ route('professor.perguntas.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- Bloco 1: Foco e Tipo de Apoio --}}
            <div class="bg-white rounded-2xl p-6 border border-magnolia-200 shadow-xs space-y-5">
                <h2 class="text-base font-bold font-heading text-cloud-burst-950 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-royal-purple-100 text-royal-purple-800 inline-flex items-center justify-center text-xs">1</span>
                    <span>Suporte da Questão (Texto ou Imagem)</span>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="focus" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Foco Pedagógico / Habilidade (Opcional)
                        </label>
                        <input id="focus" name="focus" type="text" value="{{ old('focus') }}" placeholder="Ex.: Efeito do adjetivo, Finalidade do texto..."
                               class="w-full px-4 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Tipo de Apoio <span class="text-shocking-pink-600">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2 p-2.5 border rounded-xl cursor-pointer hover:bg-royal-purple-50/50 has-checked:border-royal-purple-800 has-checked:bg-royal-purple-50">
                                <input type="radio" name="support_type" value="text" {{ old('support_type', 'text') === 'text' ? 'checked' : '' }} onchange="toggleSupportType('text')" class="text-royal-purple-800 focus:ring-royal-purple-800">
                                <span class="text-xs font-bold font-heading text-cloud-burst-950 inline-flex items-center gap-1.5">
                                    <x-lucide-file-text class="w-3.5 h-3.5 text-royal-purple-800" />
                                    <span>Texto de Apoio</span>
                                </span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 border rounded-xl cursor-pointer hover:bg-royal-purple-50/50 has-checked:border-royal-purple-800 has-checked:bg-royal-purple-50">
                                <input type="radio" name="support_type" value="image" {{ old('support_type') === 'image' ? 'checked' : '' }} onchange="toggleSupportType('image')" class="text-royal-purple-800 focus:ring-royal-purple-800">
                                <span class="text-xs font-bold font-heading text-cloud-burst-950 inline-flex items-center gap-1.5">
                                    <x-lucide-image class="w-3.5 h-3.5 text-royal-purple-800" />
                                    <span>Imagem de Apoio</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Campos para Texto de Apoio --}}
                <div id="text-support-fields" class="space-y-4 pt-2 border-t border-magnolia-100">
                    <div>
                        <label for="support_title" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Título da Fonte / Texto de Apoio
                        </label>
                        <input id="support_title" name="support_title" type="text" value="{{ old('support_title') }}" placeholder="Ex.: Tarzan é livro surpreendente que vai além dos filmes"
                               class="w-full px-4 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                    </div>

                    <div>
                        <label for="support_text" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Corpo do Texto de Apoio
                        </label>
                        <textarea id="support_text" name="support_text" rows="5" placeholder="Insira o texto que o estudante lerá e onde fará as marcações com marca-texto..."
                                  class="w-full px-4 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">{{ old('support_text') }}</textarea>
                    </div>
                </div>

                {{-- Campo para Imagem de Apoio --}}
                <div id="image-support-fields" class="space-y-4 pt-2 border-t border-magnolia-100 hidden">
                    <div>
                        <label for="support_image" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Arquivo da Imagem de Apoio (JPG, PNG ou WEBP até 5MB)
                        </label>
                        <input id="support_image" name="support_image" type="file" accept="image/*"
                               class="w-full px-4 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:font-heading file:bg-royal-purple-100 file:text-royal-purple-800 hover:file:bg-royal-purple-200">
                    </div>
                </div>
            </div>

            {{-- Bloco 2: Comando da Questão --}}
            <div class="bg-white rounded-2xl p-6 border border-magnolia-200 shadow-xs space-y-4">
                <h2 class="text-base font-bold font-heading text-cloud-burst-950 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-royal-purple-100 text-royal-purple-800 inline-flex items-center justify-center text-xs">2</span>
                    <span>Comando da Questão (Enunciado)</span>
                </h2>

                <div>
                    <label for="prompt" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                        Pergunta / Comando que o aluno deve responder <span class="text-shocking-pink-600">*</span>
                    </label>
                    <textarea id="prompt" name="prompt" rows="3" required placeholder="Ex.: Na manchete, o adjetivo “surpreendente” contribui para"
                              class="w-full px-4 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">{{ old('prompt') }}</textarea>
                </div>
            </div>

            {{-- Bloco 3: Configuração de Marca-Texto & Devolutivas de Comando/Pistas --}}
            <div class="bg-white rounded-2xl p-6 border border-magnolia-200 shadow-xs space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <h2 class="text-base font-bold font-heading text-cloud-burst-950 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-royal-purple-100 text-royal-purple-800 inline-flex items-center justify-center text-xs">3</span>
                        <span>Mecanismo de Marca-Texto & Pistas Esperadas</span>
                    </h2>
                    <span class="text-xs px-2.5 py-1 rounded-full bg-broom-100 text-broom-950 border border-broom-300 font-bold font-heading">
                        Padrão: Exigir Marcação
                    </span>
                </div>

                <div class="bg-royal-purple-50/80 p-4 rounded-xl border border-royal-purple-200 text-xs text-royal-purple-950 space-y-1">
                    <p class="font-bold font-heading flex items-center gap-1.5">
                        <x-lucide-lightbulb class="w-4 h-4 text-broom-700" />
                        <span>Lembrete Pedagógico:</span>
                    </p>
                    <p>Ao exigir o marca-texto, o estudante será obrigado a destacar o comando ou pistas antes de avançar.</p>
                    <p>Cadastre abaixo os trechos esperados e as justificativas pedagógicas ("Por que este é o comando?", "Por que estas são as pistas?") para apoiar a correção coletiva.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex items-start gap-3 p-3.5 border rounded-xl bg-gray-50/50 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="require_command_highlight" value="1" {{ old('require_command_highlight', '1') ? 'checked' : '' }} class="mt-0.5 rounded text-broom-500 focus:ring-broom-400">
                        <div>
                            <span class="text-xs font-bold font-heading text-cloud-burst-950 block">Exigir Marca-Texto de Comando (Amarelo)</span>
                            <span class="text-[11px] text-cloud-burst-700">O aluno só pode avançar após marcar trecho de comando</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3.5 border rounded-xl bg-gray-50/50 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="require_clue_highlight" value="1" {{ old('require_clue_highlight', '1') ? 'checked' : '' }} class="mt-0.5 rounded text-sky-blue-600 focus:ring-sky-blue-500">
                        <div>
                            <span class="text-xs font-bold font-heading text-cloud-burst-950 block">Exigir Marca-Texto de Pistas (Azul)</span>
                            <span class="text-[11px] text-cloud-burst-700">O aluno só pode avançar após marcar trechos de pistas no texto</span>
                        </div>
                    </label>
                </div>

                {{-- Pré-visualização Interativa com Marca-Texto para o Professor --}}
                <div class="border border-royal-purple-200 rounded-2xl p-5 bg-magnolia-50/50 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-royal-purple-100">
                        <div>
                            <span class="text-xs font-extrabold font-heading text-cloud-burst-950 uppercase tracking-wider block">
                                Marca-Texto Interativo do Professor
                            </span>
                            <span class="text-[11px] text-cloud-burst-700">
                                Selecione com o mouse qualquer palavra ou trecho abaixo e clique na cor desejada:
                            </span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button id="btn-mark-command" type="button" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-broom-500 hover:bg-broom-400 text-broom-950 font-bold font-heading text-xs shadow-2xs transition">
                                <span class="w-2.5 h-2.5 rounded-full bg-broom-700"></span>
                                <span>Marcar Comando (Amarelo)</span>
                            </button>
                            <button id="btn-mark-clue" type="button" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-sky-blue-400 hover:bg-sky-blue-300 text-sky-blue-950 font-bold font-heading text-xs shadow-2xs transition">
                                <span class="w-2.5 h-2.5 rounded-full bg-sky-blue-700"></span>
                                <span>Marcar Pistas (Azul)</span>
                            </button>
                            <button id="btn-clear-highlights" type="button" class="cursor-pointer inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl border border-rose-300 text-rose-700 hover:bg-rose-50 font-bold font-heading text-xs transition">
                                <x-lucide-trash-2 class="w-3.5 h-3.5" />
                                <span>Limpar</span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <span class="text-[11px] font-bold font-heading text-royal-purple-900 uppercase tracking-wider block mb-1">
                                Texto de Apoio (Área Selecionável):
                            </span>
                            <div id="preview-support-text" class="p-4 rounded-xl bg-white border border-magnolia-300 text-sm text-cloud-burst-950 leading-relaxed min-h-[70px] select-text"></div>
                        </div>

                        <div>
                            <span class="text-[11px] font-bold font-heading text-royal-purple-900 uppercase tracking-wider block mb-1">
                                Enunciado / Pergunta (Área Selecionável):
                            </span>
                            <div id="preview-prompt-text" class="p-4 rounded-xl bg-white border border-magnolia-300 text-sm font-heading text-cloud-burst-950 leading-relaxed min-h-[50px] select-text"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-3 border-t border-royal-purple-100">
                        <div>
                            <span class="text-xs font-bold font-heading text-broom-950 uppercase tracking-wider block mb-1.5">
                                Trechos Demarcados como Comando:
                            </span>
                            <div id="marked-commands-list" class="flex flex-wrap gap-1.5 min-h-[32px] p-2 bg-white rounded-xl border border-broom-300"></div>
                        </div>
                        <div>
                            <span class="text-xs font-bold font-heading text-sky-blue-950 uppercase tracking-wider block mb-1.5">
                                Trechos Demarcados como Pistas:
                            </span>
                            <div id="marked-clues-list" class="flex flex-wrap gap-1.5 min-h-[32px] p-2 bg-white rounded-xl border border-sky-blue-300"></div>
                        </div>
                    </div>
                </div>

                {{-- Inputs Ocultos/Sincronizados de Trechos e Devolutivas --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <div class="space-y-3">
                        <input id="expected_commands" name="expected_commands" type="hidden" value="{{ old('expected_commands') }}">
                        <div>
                            <label for="command_feedback" class="block text-xs font-bold font-heading text-cloud-burst-800 uppercase tracking-wider mb-1">
                                Devolutiva do Comando (Por que este é o comando?)
                            </label>
                            <textarea id="command_feedback" name="command_feedback" rows="2" placeholder="Explique por que esse trecho delimita a tarefa que o aluno precisa realizar..."
                                      class="w-full px-3.5 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">{{ old('command_feedback') }}</textarea>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <input id="expected_clues" name="expected_clues" type="hidden" value="{{ old('expected_clues') }}">
                        <div>
                            <label for="clue_feedback" class="block text-xs font-bold font-heading text-cloud-burst-800 uppercase tracking-wider mb-1">
                                Devolutiva das Pistas (Por que estas são as pistas?)
                            </label>
                            <textarea id="clue_feedback" name="clue_feedback" rows="2" placeholder="Explique como essas palavras-chave do texto sustentam a alternativa correta..."
                                      class="w-full px-3.5 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">{{ old('clue_feedback') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bloco 4: Alternativas e Devolutivas Pedagógicas --}}
            <div class="bg-white rounded-2xl p-6 border border-magnolia-200 shadow-xs space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold font-heading text-cloud-burst-950 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-royal-purple-100 text-royal-purple-800 inline-flex items-center justify-center text-xs">4</span>
                        <span>Alternativas e Devolutivas Pedagógicas (A a E)</span>
                    </h2>
                    <span class="text-xs text-cloud-burst-700 font-medium">Marque a bolinha da alternativa correta</span>
                </div>

                @php $letters = ['A', 'B', 'C', 'D', 'E']; @endphp

                <div class="space-y-5">
                    @foreach($letters as $idx => $letter)
                        <div class="p-4 rounded-xl border border-magnolia-200 hover:border-royal-purple-300 transition bg-magnolia-50/30 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="correct_option" value="{{ $letter }}" required {{ old('correct_option', 'B') === $letter ? 'checked' : '' }}
                                           class="text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                    <span class="font-extrabold font-heading text-sm text-cloud-burst-950">Alternativa {{ $letter }}</span>
                                    <span class="text-xs text-emerald-800 font-bold font-heading ml-1 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        Clique para definir como Correta
                                    </span>
                                </label>
                                <span class="text-xs text-cloud-burst-600 font-bold font-heading uppercase tracking-wider">Opção {{ $idx + 1 }} de 5</span>
                            </div>

                            <input type="hidden" name="options[{{ $idx }}][letter]" value="{{ $letter }}">

                            <div>
                                <input type="text" name="options[{{ $idx }}][text]" required value="{{ old("options.{$idx}.text") }}"
                                       placeholder="Texto da alternativa {{ $letter }}..."
                                       class="w-full px-3.5 py-2.5 text-sm bg-white border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold font-heading text-cloud-burst-700 uppercase tracking-wider mb-1">
                                    Devolutiva Pedagógica da Alternativa {{ $letter }} (Feedback exibido na revisão/resultado):
                                </label>
                                <textarea name="options[{{ $idx }}][feedback]" rows="2"
                                          placeholder="Ex.: Esta alternativa está correta/incorreta porque..."
                                          class="w-full px-3 py-2 text-xs bg-white border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">{{ old("options.{$idx}.feedback") }}</textarea>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('professor.perguntas.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-300 text-cloud-burst-800 text-xs font-bold font-heading hover:bg-gray-50 transition">
                    Cancelar
                </a>
                <button type="submit" class="cursor-pointer px-6 py-3 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white text-sm font-bold font-heading shadow-md transition flex items-center gap-2">
                    <span>Salvar Pergunta no Banco</span>
                    <x-lucide-arrow-right class="w-4 h-4" />
                </button>
            </div>
        </form>
    </div>

    <script>
        function toggleSupportType(type) {
            const textFields = document.getElementById('text-support-fields');
            const imgFields = document.getElementById('image-support-fields');
            if (type === 'image') {
                textFields.classList.add('hidden');
                imgFields.classList.remove('hidden');
            } else {
                imgFields.classList.add('hidden');
                textFields.classList.remove('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const checked = document.querySelector('input[name="support_type"]:checked');
            if (checked) {
                toggleSupportType(checked.value);
            }
        });
    </script>
</x-layouts.admin>
