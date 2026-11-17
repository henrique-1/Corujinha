<x-layouts.admin title="Editar Pergunta · Corujinha">
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <a href="{{ route('professor.perguntas.index') }}" class="text-xs font-bold font-heading text-royal-purple-800 hover:underline mb-2 inline-flex items-center gap-1">
                <x-lucide-arrow-left class="w-3.5 h-3.5" />
                <span>Voltar para o Banco de Perguntas</span>
            </a>
            <h1 class="text-2xl font-extrabold font-heading text-cloud-burst-950 tracking-tight">
                Editar Pergunta Pedagógica
            </h1>
            <p class="text-sm text-cloud-burst-700 mt-1">
                Atualize o conteúdo, pistas esperadas, alternativas e devolutivas pedagógicas
            </p>
        </div>

        <form action="{{ route('professor.perguntas.update', $question) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Bloco 1: Foco e Tipo de Apoio --}}
            <div class="bg-white rounded-2xl p-6 border border-magnolia-200 shadow-xs space-y-5">
                <h2 class="text-base font-bold font-heading text-cloud-burst-950 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-royal-purple-100 text-royal-purple-800 inline-flex items-center justify-center text-xs">1</span>
                    <span>Suporte da Questão (Texto e/ou Imagem)</span>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="area" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Área do Conhecimento / Disciplina
                        </label>
                        <input id="area" name="area" type="text" value="{{ old('area', $question->area) }}" placeholder="Ex.: Língua Portuguesa, Matemática, Ciências..."
                               class="w-full h-[42px] px-4 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                        @if(!empty($suggestedAreas) && $suggestedAreas->isNotEmpty())
                            <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                <span class="text-[10px] font-bold text-cloud-burst-700 uppercase">Sugestões:</span>
                                @foreach($suggestedAreas as $sugArea)
                                    <button type="button" onclick="setAreaValue('{{ addslashes($sugArea) }}')"
                                            class="cursor-pointer text-[11px] font-medium px-2 py-0.5 rounded-md bg-magnolia-100 hover:bg-royal-purple-100 hover:text-royal-purple-900 text-cloud-burst-800 border border-magnolia-300 transition">
                                        {{ $sugArea }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div>
                        <label for="grade_level" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Turma / Ano Escolar
                        </label>
                        <input id="grade_level" name="grade_level" type="text" value="{{ old('grade_level', $question->grade_level) }}" placeholder="Ex.: 6º Ano, 9º Ano, 3ª Série EM..."
                               class="w-full h-[42px] px-4 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                        @if(!empty($suggestedGradeLevels) && $suggestedGradeLevels->isNotEmpty())
                            <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                <span class="text-[10px] font-bold text-cloud-burst-700 uppercase">Sugestões:</span>
                                @foreach($suggestedGradeLevels as $sugGrade)
                                    <button type="button" onclick="document.getElementById('grade_level').value = '{{ addslashes($sugGrade) }}'"
                                            class="cursor-pointer text-[11px] font-medium px-2 py-0.5 rounded-md bg-magnolia-100 hover:bg-royal-purple-100 hover:text-royal-purple-900 text-cloud-burst-800 border border-magnolia-300 transition">
                                        {{ $sugGrade }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="folder_id" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span>Pasta de Perguntas (Opcional)</span>
                            <button type="button" onclick="document.getElementById('modal-quick-folder').classList.remove('hidden')"
                                    class="text-royal-purple-800 font-bold hover:underline normal-case text-xs inline-flex items-center gap-1 cursor-pointer">
                                <x-lucide-folder-plus class="w-3.5 h-3.5" />
                                <span>+ Criar Nova Pasta</span>
                            </button>
                        </label>
                        <select id="folder_id" name="folder_id" class="w-full h-[42px] px-4 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                            <option value="">Nenhuma pasta (Pergunta na Raiz)</option>
                            @foreach($folders ?? [] as $f)
                                <option value="{{ $f->uuid }}" {{ old('folder_id', $question->folder?->uuid) == $f->uuid ? 'selected' : '' }}>
                                    {{ $f->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="time_limit_seconds" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Tempo Limite por Questão <span class="text-shocking-pink-600">*</span>
                        </label>
                        <select id="time_limit_seconds" name="time_limit_seconds" class="w-full h-[42px] px-4 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                            <option value="60" {{ old('time_limit_seconds', $question->time_limit_seconds) == 60 ? 'selected' : '' }}>1 minuto (60s)</option>
                            <option value="120" {{ old('time_limit_seconds', $question->time_limit_seconds) == 120 ? 'selected' : '' }}>2 minutos (120s)</option>
                            <option value="180" {{ old('time_limit_seconds', $question->time_limit_seconds) == 180 ? 'selected' : '' }}>3 minutos (180s - Padrão)</option>
                            <option value="240" {{ old('time_limit_seconds', $question->time_limit_seconds) == 240 ? 'selected' : '' }}>4 minutos (240s)</option>
                            <option value="300" {{ old('time_limit_seconds', $question->time_limit_seconds) == 300 ? 'selected' : '' }}>5 minutos (300s)</option>
                            <option value="600" {{ old('time_limit_seconds', $question->time_limit_seconds) == 600 ? 'selected' : '' }}>10 minutos (600s)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="focus-input" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                        <span>Descritor / Foco Pedagógico / Habilidade (Opcional)</span>
                        <span class="text-[11px] font-normal text-cloud-burst-600 normal-case">Pressione <strong>Enter</strong> para adicionar cada termo</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input id="focus-input" type="text"
                               placeholder="Ex.: D04, Efeito do adjetivo, EF09LP04..."
                               class="grow h-[42px] px-4 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none bg-white">
                        <button type="button" id="btn-add-focus"
                                class="cursor-pointer h-[42px] px-4 text-xs font-bold font-heading bg-royal-purple-100 hover:bg-royal-purple-200 text-royal-purple-900 border border-royal-purple-200 rounded-xl transition inline-flex items-center gap-1.5 shrink-0">
                            <x-lucide-plus class="w-3.5 h-3.5" />
                            <span>Adicionar</span>
                        </button>
                    </div>

                    {{-- Bloco onde os termos adicionados são exibidos (abaixo do campo, igual sugestões) --}}
                    <div id="focus-chips-container" class="mt-2 flex flex-wrap items-center gap-1.5 min-h-[28px]">
                        @php
                            $initialTerms = old('focus_terms', $question->focus_terms ?? []);
                            if (empty($initialTerms) && old('focus', $question->focus)) {
                                $initialTerms = [old('focus', $question->focus)];
                            }
                        @endphp
                        @foreach($initialTerms as $term)
                            <span class="focus-chip inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-900 border border-royal-purple-200 shadow-2xs">
                                <span>{{ $term }}</span>
                                <button type="button" class="remove-focus-chip cursor-pointer text-royal-purple-700 hover:text-rose-600 transition" title="Remover termo">
                                    <x-lucide-x class="w-3.5 h-3.5" />
                                </button>
                                <input type="hidden" name="focus_terms[]" value="{{ $term }}">
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- Campos de Apoio Híbrido (Título, Texto Complexo e Imagem disponíveis simultaneamente) --}}
                <div id="text-support-fields" class="space-y-4 pt-4 border-t border-magnolia-100">
                    <div>
                        <label for="support_title" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Título da Fonte / Texto de Apoio
                        </label>
                        <input id="support_title" name="support_title" type="text" value="{{ old('support_title', $question->support_title) }}"
                               class="w-full px-4 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                    </div>

                    <div>
                        <label for="support_text" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span>Corpo do Texto de Apoio</span>
                            <span class="text-[11px] font-normal text-cloud-burst-600 normal-case math-only-toolbar hidden">Permite fórmulas LaTeX (ex.: $x^2 + y^2 = r^2$)</span>
                        </label>
                        <x-math-toolbar target-id="support_text" class="math-only-toolbar hidden mb-2" />
                        @include('professor.perguntas.partials._rich-text-editor', ['value' => old('support_text', $question->support_text ?? '')])
                        <div id="math-support-preview" class="math-only-toolbar hidden mt-2 p-3 bg-magnolia-50/70 border border-magnolia-200 rounded-xl text-sm"></div>
                    </div>
                </div>

                <div id="image-support-fields" class="space-y-4 pt-4 border-t border-magnolia-100">
                    @if($question->hasImageSupport())
                        <div class="p-4 rounded-xl bg-magnolia-50/70 border border-magnolia-200 space-y-3">
                            <span class="text-xs font-bold font-heading text-cloud-burst-800 block">Imagem Atual:</span>
                            <img src="{{ $question->image_url }}" alt="Imagem de apoio" class="max-h-48 rounded-xl border border-magnolia-200 shadow-xs">
                            <label class="inline-flex items-center gap-2 text-xs font-bold font-heading text-rose-700 cursor-pointer">
                                <input type="checkbox" name="remove_support_image" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                                <span>Remover imagem atual ao salvar</span>
                            </label>
                        </div>
                    @endif
                    <div>
                        <label for="support_image" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            {{ $question->hasImageSupport() ? 'Substituir Imagem de Apoio (opcional · JPG, PNG ou WEBP até 5MB)' : 'Imagem de Apoio (Opcional · JPG, PNG ou WEBP até 5MB)' }}
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
                    <span>Comando da Questão</span>
                </h2>

                <div>
                    <label for="prompt" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                        <span>Pergunta / Comando que o aluno deve responder <span class="text-shocking-pink-600">*</span></span>
                        <span class="text-[11px] font-normal text-cloud-burst-600 normal-case math-only-toolbar hidden">Permite fórmulas LaTeX</span>
                    </label>
                    <x-math-toolbar target-id="prompt" class="math-only-toolbar hidden mb-2" />
                    <textarea id="prompt" name="prompt" rows="3" required
                              class="w-full px-4 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">{{ old('prompt', $question->prompt) }}</textarea>
                    <div id="math-prompt-preview" class="math-only-toolbar hidden mt-2 p-3 bg-magnolia-50/70 border border-magnolia-200 rounded-xl text-sm"></div>
                </div>
            </div>

            {{-- Bloco 3: Configuração de Marca-Texto & Devolutivas de Comando/Pistas (Exibido apenas em Língua Portuguesa) --}}
            <div id="bloco-marca-texto" class="bg-white rounded-2xl p-6 border border-magnolia-200 shadow-xs space-y-5">
                <h2 class="text-base font-bold font-heading text-cloud-burst-950 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-royal-purple-100 text-royal-purple-800 inline-flex items-center justify-center text-xs">3</span>
                    <span>Mecanismo de Marca-Texto & Pistas Esperadas</span>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex items-start gap-3 p-3.5 border rounded-xl bg-gray-50/50 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="require_command_highlight" value="1" {{ old('require_command_highlight', $question->require_command_highlight) ? 'checked' : '' }} class="mt-0.5 rounded text-broom-500 focus:ring-broom-400">
                        <div>
                            <span class="text-xs font-bold font-heading text-cloud-burst-950 block">Exigir Marca-Texto de Comando (Amarelo)</span>
                            <span class="text-[11px] text-cloud-burst-700">O aluno só pode avançar após marcar trecho de comando</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3.5 border rounded-xl bg-gray-50/50 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="require_clue_highlight" value="1" {{ old('require_clue_highlight', $question->require_clue_highlight) ? 'checked' : '' }} class="mt-0.5 rounded text-sky-blue-600 focus:ring-sky-blue-500">
                        <div>
                            <span class="text-xs font-bold font-heading text-cloud-burst-950 block">Exigir Marca-Texto de Pistas (Azul)</span>
                            <span class="text-[11px] text-cloud-burst-700">O aluno só pode avançar após marcar trechos de pistas</span>
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
                                Título da Fonte / Texto de Apoio (Área Selecionável):
                            </span>
                            <div id="preview-support-title" class="p-3.5 rounded-xl bg-white border border-magnolia-300 text-sm font-bold font-heading text-cloud-burst-950 leading-relaxed min-h-[44px] select-text"></div>
                        </div>

                        <div>
                            <span class="text-[11px] font-bold font-heading text-royal-purple-900 uppercase tracking-wider block mb-1">
                                Texto de Apoio (Área Selecionável):
                            </span>
                            <div id="preview-support-text" class="rich-text-content p-4 rounded-xl bg-white border border-magnolia-300 text-sm text-cloud-burst-950 leading-relaxed min-h-[70px] select-text"></div>
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
                        <input id="expected_commands" name="expected_commands" type="hidden" value="{{ old('expected_commands', $question->expected_commands) }}">
                        <div>
                            <label for="command_feedback" class="block text-xs font-bold font-heading text-cloud-burst-800 uppercase tracking-wider mb-1">
                                Devolutiva do Comando
                            </label>
                            <textarea id="command_feedback" name="command_feedback" rows="2"
                                      class="w-full px-3.5 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">{{ old('command_feedback', $question->command_feedback) }}</textarea>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <input id="expected_clues" name="expected_clues" type="hidden" value="{{ old('expected_clues', $question->expected_clues) }}">
                        <div>
                            <label for="clue_feedback" class="block text-xs font-bold font-heading text-cloud-burst-800 uppercase tracking-wider mb-1">
                                Devolutiva das Pistas
                            </label>
                            <textarea id="clue_feedback" name="clue_feedback" rows="2"
                                      class="w-full px-3.5 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">{{ old('clue_feedback', $question->clue_feedback) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bloco 4: Configuração de Resposta / Alternativas --}}
            <div class="space-y-6">
                {{-- Seletor de Tipo de Resposta exclusivo para Matemática --}}
                <div id="math-answer-type-selector" class="hidden bg-royal-purple-50/80 rounded-2xl p-5 border border-royal-purple-200 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider block">
                            Formato de Resposta (Matemática)
                        </span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-royal-purple-100 text-royal-purple-800 font-bold font-heading">
                            Escolha o método de resposta do estudante
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="cursor-pointer flex items-start gap-3 p-3.5 bg-white rounded-xl border border-magnolia-300 hover:border-royal-purple-600 transition shadow-2xs">
                            <input type="radio" name="answer_type" value="multiple_choice" {{ old('answer_type', $question->answer_type ?? 'multiple_choice') === 'multiple_choice' ? 'checked' : '' }}
                                   class="mt-0.5 text-royal-purple-800 focus:ring-royal-purple-600" onchange="applyAreaMode()">
                            <div>
                                <span class="text-xs font-bold font-heading text-cloud-burst-950 block">Alternativas (Múltipla Escolha A a E)</span>
                                <span class="text-[11px] text-cloud-burst-600">O estudante escolhe entre as 5 opções com gabarito automático.</span>
                            </div>
                        </label>
                        <label class="cursor-pointer flex items-start gap-3 p-3.5 bg-white rounded-xl border border-magnolia-300 hover:border-royal-purple-600 transition shadow-2xs">
                            <input type="radio" name="answer_type" value="direct_input" {{ old('answer_type', $question->answer_type ?? '') === 'direct_input' ? 'checked' : '' }}
                                   class="mt-0.5 text-royal-purple-800 focus:ring-royal-purple-600" onchange="applyAreaMode()">
                            <div>
                                <span class="text-xs font-bold font-heading text-cloud-burst-950 block">Campo de Resposta Direta / Fórmula</span>
                                <span class="text-[11px] text-cloud-burst-600">O aluno digita a resposta em fórmula e anexa a foto da resolução manuscrita.</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Bloco para Resposta Direta (Matemática) --}}
                <div id="bloco-resposta-direta" class="hidden bg-white rounded-2xl p-6 border border-magnolia-200 shadow-xs space-y-5">
                    <h2 class="text-base font-bold font-heading text-cloud-burst-950 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-royal-purple-100 text-royal-purple-800 inline-flex items-center justify-center text-xs">4</span>
                        <span>Resposta Esperada e Devolutiva Pedagógica</span>
                    </h2>

                    <div>
                        <label for="expected_answer" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span>Resposta Esperada / Gabarito da Questão (Número, Expressão ou Fórmula) <span class="text-shocking-pink-600">*</span></span>
                            <span class="text-[11px] font-normal text-cloud-burst-600 normal-case">Suporta números decimais, frações e fórmulas LaTeX</span>
                        </label>
                        <x-math-toolbar target-id="expected_answer" class="mb-2" />
                        <input id="expected_answer" name="expected_answer" type="text" value="{{ old('expected_answer', $question->expected_answer) }}"
                               placeholder="Ex.: 42, 3.5, 3/4, \frac{1}{2}, x = 5"
                               class="w-full px-4 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none font-mono">
                        <div id="math-expected-preview" class="mt-2 p-3 bg-magnolia-50/70 border border-magnolia-200 rounded-xl text-sm"></div>
                    </div>

                    <div>
                        <label for="expected_answer_explanation" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5">
                            Resolução Esperada / Comentário Pedagógico (Exibido na correção e devolutiva ao aluno)
                        </label>
                        <x-math-toolbar target-id="expected_answer_explanation" class="mb-2" />
                        <textarea id="expected_answer_explanation" name="expected_answer_explanation" rows="3"
                                  placeholder="Descreva o passo a passo da resolução esperada..."
                                  class="w-full px-4 py-2.5 text-sm border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">{{ old('expected_answer_explanation', $question->expected_answer_explanation) }}</textarea>
                        <div id="math-explanation-preview" class="mt-2 p-3 bg-magnolia-50/70 border border-magnolia-200 rounded-xl text-sm"></div>
                    </div>

                    <div>
                        <label for="expected_answer_image" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <x-lucide-camera class="w-4 h-4 text-royal-purple-800" />
                                <span>Foto / Anexo da Resolução Esperada (Folha de cálculos ou esquema manuscrito)</span>
                            </span>
                            <span class="text-[11px] font-normal text-cloud-burst-600 normal-case">Opcional · JPG, PNG ou WEBP até 5 MB</span>
                        </label>

                        @if($question->hasExpectedAnswerImage())
                            <div class="mb-3 p-3 bg-magnolia-50 rounded-xl border border-magnolia-200 flex items-center justify-between gap-4 flex-wrap">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $question->expected_answer_image_url }}" alt="Foto da Resolução Atual" class="h-16 w-16 object-cover rounded-lg border border-magnolia-300 shadow-2xs">
                                    <div>
                                        <span class="text-xs font-bold font-heading text-cloud-burst-950 block">Foto da Resolução Atual Cadastrada</span>
                                        <span class="text-[11px] text-cloud-burst-600">Selecione um novo arquivo abaixo para substituir</span>
                                    </div>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold font-heading text-rose-700">
                                    <input type="checkbox" name="remove_expected_answer_image" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                                    <span>Remover foto da resolução</span>
                                </label>
                            </div>
                        @endif

                        <div class="flex items-center gap-4 flex-wrap">
                            <input id="expected_answer_image" name="expected_answer_image" type="file" accept="image/*"
                                   class="text-xs text-cloud-burst-700 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:font-heading file:bg-royal-purple-100 file:text-royal-purple-800 hover:file:bg-royal-purple-200 cursor-pointer">
                        </div>
                        <div id="expected-answer-image-preview-container" class="mt-3 hidden">
                            <span class="text-[11px] font-bold text-cloud-burst-700 block mb-1">Pré-visualização da Nova Foto Selecionada:</span>
                            <div class="relative inline-block">
                                <img id="expected-answer-image-preview" src="" alt="Prévia da Resolução" class="max-h-56 rounded-xl border border-magnolia-300 shadow-2xs">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bloco para Alternativas (A a E) --}}
                <div id="bloco-alternativas" class="bg-white rounded-2xl p-6 border border-magnolia-200 shadow-xs space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold font-heading text-cloud-burst-950 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-royal-purple-100 text-royal-purple-800 inline-flex items-center justify-center text-xs">4</span>
                            <span>Alternativas e Devolutivas Pedagógicas (A a E)</span>
                        </h2>
                        <span class="text-xs text-cloud-burst-700 font-medium">Marque a bolinha da alternativa correta</span>
                    </div>

                    @php
                        $letters = ['A', 'B', 'C', 'D', 'E'];
                        $optMap = $question->options->keyBy('letter');
                    @endphp

                    <div class="space-y-5">
                        @foreach($letters as $idx => $letter)
                            @php $currentOpt = $optMap->get($letter); @endphp
                            <div class="p-4 rounded-xl border border-magnolia-200 bg-magnolia-50/30 space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="correct_option" value="{{ $letter }}" required {{ old('correct_option', $question->correct_option) === $letter ? 'checked' : '' }}
                                               class="option-correct-radio text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                        <span class="font-extrabold font-heading text-sm text-cloud-burst-950">Alternativa {{ $letter }}</span>
                                        <span class="text-xs text-emerald-800 font-bold font-heading ml-1 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                            Clique para definir como Correta
                                        </span>
                                    </label>
                                    <span class="text-xs text-cloud-burst-600 font-bold font-heading uppercase tracking-wider">Opção {{ $idx + 1 }} de 5</span>
                                </div>

                                <input type="hidden" name="options[{{ $idx }}][letter]" value="{{ $letter }}">

                                <div>
                                    <x-math-toolbar target-id="option_text_{{ $idx }}" class="math-option-toolbar mb-1.5 hidden" />
                                    <input id="option_text_{{ $idx }}" type="text" name="options[{{ $idx }}][text]" required value="{{ old("options.{$idx}.text", $currentOpt?->text) }}"
                                           placeholder="Texto ou fórmula da alternativa {{ $letter }} (ex: \frac{1}{2}, x^2 + 1)..."
                                           class="option-text-input w-full px-3.5 py-2.5 text-sm bg-white border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                                    <div id="option-preview-{{ $idx }}" class="option-math-preview mt-1.5 p-2 bg-white border border-magnolia-200 rounded-xl text-xs hidden font-mono"></div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold font-heading text-cloud-burst-700 uppercase tracking-wider mb-1">
                                        Devolutiva Pedagógica da Alternativa {{ $letter }}:
                                    </label>
                                    <textarea name="options[{{ $idx }}][feedback]" rows="2"
                                              class="w-full px-3 py-2 text-xs bg-white border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">{{ old("options.{$idx}.feedback", $currentOpt?->feedback) }}</textarea>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('professor.perguntas.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-300 text-cloud-burst-800 text-xs font-bold font-heading hover:bg-gray-50 transition">
                    Cancelar
                </a>
                <button type="submit" class="cursor-pointer px-6 py-3 rounded-xl bg-royal-purple-800 hover:bg-royal-purple-900 text-white text-sm font-bold font-heading shadow-md transition flex items-center gap-2">
                    <span>Salvar Alterações</span>
                    <x-lucide-arrow-right class="w-4 h-4" />
                </button>
            </div>
        </form>

        {{-- Modal Criar Pasta Rápida --}}
        <div id="modal-quick-folder" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-cloud-burst-950/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-magnolia-200 space-y-4 animate-scale-in">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-lucide-folder-plus class="w-5 h-5 text-royal-purple-800" />
                        <h3 class="text-base font-extrabold font-heading text-cloud-burst-950">Criar Nova Pasta</h3>
                    </div>
                    <button type="button" onclick="document.getElementById('modal-quick-folder').classList.add('hidden')"
                            class="cursor-pointer text-cloud-burst-400 hover:text-cloud-burst-700 p-1">
                        <x-lucide-x class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label for="quick_folder_name" class="block text-xs font-bold font-heading text-cloud-burst-950 uppercase tracking-wider mb-1">
                            Nome da Pasta
                        </label>
                        <input id="quick_folder_name" type="text" placeholder="Ex.: Simulado 1º Bimestre"
                               class="w-full px-3 py-2 text-xs border border-magnolia-300 rounded-xl focus:border-royal-purple-800 focus:outline-none">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('modal-quick-folder').classList.add('hidden')"
                            class="cursor-pointer px-3.5 py-1.5 border border-magnolia-300 rounded-xl text-xs font-bold font-heading text-cloud-burst-800 hover:bg-magnolia-50 transition">
                        Cancelar
                    </button>
                    <button type="button" id="btn-save-quick-folder"
                            class="cursor-pointer px-4 py-1.5 bg-royal-purple-800 hover:bg-royal-purple-900 text-white font-bold font-heading text-xs rounded-xl shadow-2xs transition">
                        Salvar e Selecionar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('btn-save-quick-folder')?.addEventListener('click', async () => {
                const input = document.getElementById('quick_folder_name');
                const name = input ? input.value.trim() : '';
                if (!name) return;

                try {
                    const response = await fetch('{{ route('professor.pastas.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify({ name }),
                    });

                    const data = await response.json();
                    if (data.success && data.folder) {
                        const select = document.getElementById('folder_id');
                        const opt = document.createElement('option');
                        opt.value = data.folder.uuid;
                        opt.textContent = data.folder.name;
                        opt.selected = true;
                        select.appendChild(opt);

                        input.value = '';
                        document.getElementById('modal-quick-folder').classList.add('hidden');
                    }
                } catch (e) {
                    alert('Erro ao criar pasta. Tente novamente.');
                }
            });

            // Gestão de Termos: Descritor / Foco Pedagógico / Habilidade
            const focusInput = document.getElementById('focus-input');
            const btnAddFocus = document.getElementById('btn-add-focus');
            const focusChipsContainer = document.getElementById('focus-chips-container');
            const form = focusInput ? focusInput.closest('form') : null;

            function escapeHtml(str) {
                return str
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            function addFocusTerm() {
                if (!focusInput || !focusChipsContainer) return;
                const val = focusInput.value.trim();
                if (!val) return;

                const existingInputs = focusChipsContainer.querySelectorAll('input[name="focus_terms[]"]');
                for (let inp of existingInputs) {
                    if (inp.value.toLowerCase() === val.toLowerCase()) {
                        focusInput.value = '';
                        return;
                    }
                }

                const chip = document.createElement('span');
                chip.className = 'focus-chip inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold font-heading bg-royal-purple-100 text-royal-purple-900 border border-royal-purple-200 shadow-2xs';
                chip.innerHTML = `
                    <span>${escapeHtml(val)}</span>
                    <button type="button" class="remove-focus-chip cursor-pointer text-royal-purple-700 hover:text-rose-600 transition" title="Remover termo">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                    <input type="hidden" name="focus_terms[]" value="${escapeHtml(val)}">
                `;

                focusChipsContainer.appendChild(chip);
                focusInput.value = '';
                focusInput.focus();
            }

            if (focusInput) {
                focusInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addFocusTerm();
                    }
                });
            }

            if (btnAddFocus) {
                btnAddFocus.addEventListener('click', (e) => {
                    e.preventDefault();
                    addFocusTerm();
                });
            }

            if (focusChipsContainer) {
                focusChipsContainer.addEventListener('click', (e) => {
                    const btn = e.target.closest('.remove-focus-chip');
                    if (btn) {
                        e.preventDefault();
                        btn.closest('.focus-chip')?.remove();
                    }
                });
            }

            if (form) {
                form.addEventListener('submit', () => {
                    if (focusInput && focusInput.value.trim()) {
                        addFocusTerm();
                    }
                });
            }

            // Modo dinâmico por Área do Conhecimento
            window.setAreaValue = function (val) {
                const areaInput = document.getElementById('area');
                if (areaInput) {
                    areaInput.value = val;
                    applyAreaMode();
                }
            };

            window.applyAreaMode = function () {
                const areaInput = document.getElementById('area');
                const areaVal = (areaInput ? areaInput.value : '').toLowerCase().trim();

                let mode = 'other';
                if (areaVal.includes('matemát') || areaVal.includes('matemat')) {
                    mode = 'math';
                } else if (areaVal !== '' && (areaVal.includes('portugu') || areaVal.includes('língua portuguesa'))) {
                    mode = 'portuguese';
                }

                const blocoMarcaTexto = document.getElementById('bloco-marca-texto');
                const mathAnswerTypeSelector = document.getElementById('math-answer-type-selector');
                const blocoRespostaDireta = document.getElementById('bloco-resposta-direta');
                const blocoAlternativas = document.getElementById('bloco-alternativas');
                const mathToolbars = document.querySelectorAll('.math-only-toolbar');

                const optionInputs = document.querySelectorAll('.option-text-input');
                const optionRadios = document.querySelectorAll('.option-correct-radio');
                const expectedAnswerInput = document.getElementById('expected_answer');

                if (mode === 'portuguese') {
                    if (blocoMarcaTexto) blocoMarcaTexto.classList.remove('hidden');
                    if (mathAnswerTypeSelector) mathAnswerTypeSelector.classList.add('hidden');
                    if (blocoRespostaDireta) blocoRespostaDireta.classList.add('hidden');
                    if (blocoAlternativas) blocoAlternativas.classList.remove('hidden');
                    mathToolbars.forEach(el => el.classList.add('hidden'));

                    optionInputs.forEach(i => i.setAttribute('required', 'required'));
                    optionRadios.forEach(r => r.setAttribute('required', 'required'));
                    if (expectedAnswerInput) expectedAnswerInput.removeAttribute('required');
                } else if (mode === 'math') {
                    if (blocoMarcaTexto) blocoMarcaTexto.classList.add('hidden');
                    if (mathAnswerTypeSelector) mathAnswerTypeSelector.classList.remove('hidden');
                    mathToolbars.forEach(el => el.classList.remove('hidden'));

                    const answerTypeRadio = document.querySelector('input[name="answer_type"]:checked');
                    const answerType = answerTypeRadio ? answerTypeRadio.value : 'multiple_choice';

                    if (answerType === 'direct_input') {
                        if (blocoRespostaDireta) blocoRespostaDireta.classList.remove('hidden');
                        if (blocoAlternativas) blocoAlternativas.classList.add('hidden');
                        document.querySelectorAll('.math-option-toolbar').forEach(el => el.classList.add('hidden'));

                        optionInputs.forEach(i => i.removeAttribute('required'));
                        optionRadios.forEach(r => r.removeAttribute('required'));
                        if (expectedAnswerInput) expectedAnswerInput.setAttribute('required', 'required');
                    } else {
                        if (blocoRespostaDireta) blocoRespostaDireta.classList.add('hidden');
                        if (blocoAlternativas) blocoAlternativas.classList.remove('hidden');
                        document.querySelectorAll('.math-option-toolbar').forEach(el => el.classList.remove('hidden'));

                        optionInputs.forEach(i => i.setAttribute('required', 'required'));
                        optionRadios.forEach(r => r.setAttribute('required', 'required'));
                        if (expectedAnswerInput) expectedAnswerInput.removeAttribute('required');
                    }

                    if (window.MathHelper?.updateSupportMathPreview) {
                        window.MathHelper.updateSupportMathPreview();
                    }
                } else {
                    // Outras disciplinas: sem marca-texto, com alternativas A-E
                    if (blocoMarcaTexto) blocoMarcaTexto.classList.add('hidden');
                    if (mathAnswerTypeSelector) mathAnswerTypeSelector.classList.add('hidden');
                    if (blocoRespostaDireta) blocoRespostaDireta.classList.add('hidden');
                    if (blocoAlternativas) blocoAlternativas.classList.remove('hidden');
                    mathToolbars.forEach(el => el.classList.add('hidden'));
                    document.querySelectorAll('.math-option-toolbar').forEach(el => el.classList.add('hidden'));

                    optionInputs.forEach(i => i.setAttribute('required', 'required'));
                    optionRadios.forEach(r => r.setAttribute('required', 'required'));
                    if (expectedAnswerInput) expectedAnswerInput.removeAttribute('required');
                }
            };

            const areaField = document.getElementById('area');
            if (areaField) {
                areaField.addEventListener('input', applyAreaMode);
                areaField.addEventListener('change', applyAreaMode);
            }

            // Preview da Foto da Resolução Esperada
            const expImgInput = document.getElementById('expected_answer_image');
            const expImgPreview = document.getElementById('expected-answer-image-preview');
            const expImgContainer = document.getElementById('expected-answer-image-preview-container');
            if (expImgInput && expImgPreview && expImgContainer) {
                expImgInput.addEventListener('change', () => {
                    const file = expImgInput.files && expImgInput.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            expImgPreview.src = e.target.result;
                            expImgContainer.classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        expImgContainer.classList.add('hidden');
                        expImgPreview.src = '';
                    }
                });
            }

            // Inicializa módulo KaTeX e barras de ferramentas matemáticas
            if (window.MathHelper && typeof window.MathHelper.initMathModule === 'function') {
                window.MathHelper.initMathModule();
            }

            // Executa modo inicial
            applyAreaMode();
        });
    </script>
</x-layouts.admin>
