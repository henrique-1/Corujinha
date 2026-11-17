<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Consolidado de Avaliação · {{ $assessment->title }}</title>
    @php
        $logoPath = public_path('images/logo/corujinha-horizontal-300dpi.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;

        $iconPath = public_path('images/logo/corujinha-icone.png');
        $iconBase64 = file_exists($iconPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($iconPath)) : null;

        $poppinsRegular = public_path('fonts/Poppins/Poppins-Regular.ttf');
        $poppinsSemiBold = public_path('fonts/Poppins/Poppins-SemiBold.ttf');
        $poppinsBold = public_path('fonts/Poppins/Poppins-Bold.ttf');
    @endphp
    <style>
        @page {
            margin: 20px 25px 30px 25px;
            size: a4 portrait;
        }

        @if(file_exists($poppinsRegular))
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 400;
            src: url('{{ $poppinsRegular }}') format('truetype');
        }
        @endif

        @if(file_exists($poppinsSemiBold))
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 600;
            src: url('{{ $poppinsSemiBold }}') format('truetype');
        }
        @endif

        @if(file_exists($poppinsBold))
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 700;
            src: url('{{ $poppinsBold }}') format('truetype');
        }
        @endif

        body {
            font-family: 'Poppins', DejaVu Sans, sans-serif;
            font-size: 9.5px;
            color: #24304a; /* Cloud Burst 950 */
            margin: 0;
            padding: 0;
            line-height: 1.35;
            background-color: #ffffff;
        }

        /* Top Header Banner */
        .header-banner {
            background-color: #250048; /* Royal Purple 950 */
            border-bottom: 3.5px solid #FFDE00; /* Broom Yellow 500 */
            padding: 14px 18px 12px 18px;
            border-radius: 8px;
            margin-bottom: 14px;
            color: #ffffff;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
            background: transparent;
        }

        .inst-title {
            font-size: 8px;
            font-weight: 600;
            color: #FFDE00;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .report-title {
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 2px 0;
            line-height: 1.2;
        }

        .meta-line {
            font-size: 9px;
            color: #eeecfb;
            margin-top: 4px;
        }

        .meta-line strong {
            color: #ffffff;
        }

        .badges-container {
            text-align: right;
        }

        .badge-consolidated {
            display: inline-block;
            background-color: #FFDE00; /* Broom Yellow */
            color: #250048;
            font-weight: 700;
            font-size: 8.5px;
            padding: 4px 10px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-official {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 7.5px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 8px;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Summary Metrics Grid */
        .metrics-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 12px;
        }

        .metric-card {
            background-color: #f6f5fd; /* Royal Purple 50 */
            border: 1px solid #dfdcf8; /* Royal Purple 200 */
            border-radius: 7px;
            padding: 8px 10px;
            text-align: center;
            vertical-align: top;
        }

        .metric-label {
            font-size: 8px;
            font-weight: 700;
            color: #623bae; /* Royal Purple 800 */
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .metric-number {
            font-size: 19px;
            font-weight: 700;
            color: #250048;
            line-height: 1.1;
        }

        .metric-help {
            font-size: 7.5px;
            color: #553280;
            margin-top: 2px;
        }

        /* Section Title */
        .section-heading {
            font-size: 10.5px;
            font-weight: 700;
            color: #250048;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #FFDE00;
            padding-bottom: 3px;
            margin: 14px 0 8px 0;
        }

        /* Pedagogical Highlight Boxes */
        .highlights-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 12px;
        }

        .highlight-card {
            vertical-align: top;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 8.5px;
        }

        .highlight-success {
            background-color: #ecfdf5;
            border-left: 4px solid #10b981;
            border-top: 1px solid #a7f3d0;
            border-right: 1px solid #a7f3d0;
            border-bottom: 1px solid #a7f3d0;
        }

        .highlight-alert {
            background-color: #fff1f2;
            border-left: 4px solid #f43f5e;
            border-top: 1px solid #fecdd3;
            border-right: 1px solid #fecdd3;
            border-bottom: 1px solid #fecdd3;
        }

        .highlight-tag {
            font-weight: 700;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .highlight-success .highlight-tag {
            color: #047857;
        }

        .highlight-alert .highlight-tag {
            color: #be123c;
        }

        .highlight-prompt {
            font-size: 9px;
            font-weight: 600;
            color: #1f2937;
            margin: 3px 0;
            line-height: 1.3;
        }

        .highlight-guide {
            font-size: 7.5px;
            margin-top: 3px;
            line-height: 1.25;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .data-table th {
            background-color: #eeecfb; /* Royal Purple 100 */
            color: #250048;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 7px;
            border: 1px solid #dfdcf8;
            text-align: left;
        }

        .data-table td {
            padding: 5px 7px;
            border: 1px solid #eeecfb;
            font-size: 8.5px;
            color: #24304a;
        }

        .data-table tr:nth-child(even) td {
            background-color: #faf9fd;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .code-box {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 8px;
            background-color: #f6f5fd;
            border: 1px solid #dfdcf8;
            padding: 1px 4px;
            border-radius: 4px;
            color: #250048;
            font-weight: bold;
        }

        .badge-pill-mode {
            padding: 1.5px 5px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 7.5px;
            display: inline-block;
        }

        .badge-mode-corujao-sm {
            background-color: #fee5f8;
            color: #8f0c57;
            border: 1px solid #ffcaf1;
        }

        .badge-mode-corujinha-sm {
            background-color: #fdffc2;
            color: #402e03;
            border: 1px solid #fffd45;
        }

        /* Footer */
        .page-footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #dfdcf8;
            text-align: center;
            font-size: 7.5px;
            color: #6c41c0;
        }
    </style>
</head>
<body>

    {{-- Top Header Banner --}}
    <div class="header-banner">
        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    <div style="margin-bottom: 4px;">
                        @if($logoBase64)
                            <img src="{{ $logoBase64 }}" height="32" style="max-height: 32px; vertical-align: middle;" alt="Corujinha">
                        @else
                            <span style="font-size: 18px; font-weight: 700; color: #FFDE00;">CORUJINHA</span>
                        @endif
                    </div>
                    <div class="inst-title">Prepara Avalia+ · Diagnóstico & Aprendizagem Formativa</div>
                    <div class="report-title">Relatório Consolidado de Avaliação</div>
                    <div class="meta-line">
                        <strong>Avaliação:</strong> {{ $assessment->title }} &nbsp;|&nbsp;
                        <strong>Período:</strong> {{ $assessment->academic_period ?? 'Geral' }}
                    </div>
                    <div class="meta-line" style="font-size: 8px; color: #e2d4f5;">
                        <strong>Criador(a):</strong> {{ $assessment->user?->name ?? 'Não Informado' }} &nbsp;|&nbsp;
                        <strong>Salas Computadas:</strong> {{ $total_classrooms }} turma(s)
                    </div>
                </td>
                <td class="badges-container" style="width: 40%;">
                    <div class="badge-consolidated">
                        @if($iconBase64)
                            <img src="{{ $iconBase64 }}" width="11" height="11" style="vertical-align: middle; margin-right: 3px;" alt="Corujinha">
                        @endif
                        Consolidação Multi-Salas
                    </div>
                    <br>
                    <span class="badge-official">Documento Oficial de Avaliação</span>
                    <div style="font-size: 7.5px; color: #dfdcf8; margin-top: 4px;">
                        Emitido em: {{ now()->format('d/m/Y \à\s H:i') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Summary Metrics Grid --}}
    <table class="metrics-table">
        <tr>
            <td class="metric-card" style="width: 25%;">
                <div class="metric-label">Aproveitamento Médio</div>
                <div class="metric-number">{{ $overall_accuracy }}%</div>
                <div class="metric-help">Média geral de todas as turmas</div>
            </td>
            <td class="metric-card" style="width: 25%;">
                <div class="metric-label">Salas / Turmas</div>
                <div class="metric-number">{{ $total_classrooms }}</div>
                <div class="metric-help">Turmas com aplicação ativa/concluída</div>
            </td>
            <td class="metric-card" style="width: 25%;">
                <div class="metric-label">Total de Estudantes</div>
                <div class="metric-number">{{ $total_completed }}/{{ $total_students }}</div>
                <div class="metric-help">Participações concluídas</div>
            </td>
            <td class="metric-card" style="width: 25%;">
                <div class="metric-label">Total de Questões</div>
                <div class="metric-number">{{ count($questions) }}</div>
                <div class="metric-help">Itens avaliados no caderno</div>
            </td>
        </tr>
    </table>

    {{-- Formative Pedagogical Highlights --}}
    <div class="section-heading">Destaques Pedagógicos Consolidados</div>
    <table class="highlights-table">
        <tr>
            <td class="highlight-card highlight-success" style="width: 50%;">
                <div class="highlight-tag">✓ Questão com Maior Sucesso Institucional</div>
                @if($most_correct_question)
                    <div class="highlight-prompt">
                        Item {{ $most_correct_question['index'] }}: {{ \Illuminate\Support\Str::limit($most_correct_question['prompt'], 85) }}
                    </div>
                    <div style="color: #047857; font-weight: 700; font-size: 8.5px;">
                        {{ $most_correct_question['correct_count'] }} acertos no total (Taxa de Sucesso: {{ $most_correct_question['accuracy_rate'] }}%)
                    </div>
                    <div class="highlight-guide" style="color: #065f46;">
                        <strong>Guia Pedagógico:</strong> Habilidade amplamente dominada em nível institucional entre as turmas avaliadas.
                    </div>
                @else
                    <div style="color: #6b7280; font-size: 8.5px;">Dados consolidados ainda não disponíveis.</div>
                @endif
            </td>
            <td class="highlight-card highlight-alert" style="width: 50%;">
                <div class="highlight-tag">⚠ Questão com Maior Necessidade de Intervenção</div>
                @if($most_wrong_question)
                    <div class="highlight-prompt">
                        Item {{ $most_wrong_question['index'] }}: {{ \Illuminate\Support\Str::limit($most_wrong_question['prompt'], 85) }}
                    </div>
                    <div style="color: #be123c; font-weight: 700; font-size: 8.5px;">
                        {{ $most_wrong_question['wrong_count'] }} erros no total (Taxa de Acerto: {{ $most_wrong_question['accuracy_rate'] }}%)
                    </div>
                    <div class="highlight-guide" style="color: #9f1239;">
                        <strong>Guia Pedagógico:</strong> Recomenda-se alinhamento pedagógico entre os docentes para reforçar esse descritor em todas as turmas.
                    </div>
                @else
                    <div style="color: #6b7280; font-size: 8.5px;">Dados consolidados ainda não disponíveis.</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Classrooms Comparison Table --}}
    <div class="section-heading">Desempenho Comparativo por Sala / Turma</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 35%;">Nome da Sala / Turma</th>
                <th style="width: 14%;" class="text-center">Código</th>
                <th style="width: 16%;" class="text-center">Modo de Jogo</th>
                <th style="width: 17%;" class="text-center">Estudantes</th>
                <th style="width: 18%;" class="text-center">Aproveitamento</th>
            </tr>
        </thead>
        <tbody>
            @forelse($classroom_summaries as $c)
                <tr>
                    <td><strong style="color: #24304a;">{{ $c['name'] }}</strong></td>
                    <td class="text-center"><span class="code-box">{{ $c['code'] }}</span></td>
                    <td class="text-center">
                        <span class="badge-pill-mode {{ $c['game_mode'] === 'corujao' ? 'badge-mode-corujao-sm' : 'badge-mode-corujinha-sm' }}">
                            {{ $c['game_mode'] === 'corujao' ? 'Corujão' : 'Corujinha' }}
                        </span>
                    </td>
                    <td class="text-center">{{ $c['completed_count'] }} / {{ $c['total_students'] }}</td>
                    <td class="text-center" style="font-weight: 700; color: {{ $c['accuracy'] >= 70 ? '#047857' : ($c['accuracy'] >= 50 ? '#854d0e' : '#be123c') }};">
                        {{ $c['accuracy'] }}%
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 16px; color: #6b7280;">Nenhuma sala de aula vinculada a esta avaliação até o momento.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- General Student Performance Table --}}
    <div class="section-heading">Quadro Geral de Estudantes (Todas as Turmas)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;" class="text-center">Pos.</th>
                <th style="width: 36%;">Nome do Estudante</th>
                <th style="width: 14%;" class="text-center">Turma</th>
                <th style="width: 14%;" class="text-center">Código Aluno</th>
                <th style="width: 15%;" class="text-center">Acertos</th>
                <th style="width: 15%;" class="text-center">Aproveitamento</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $idx => $st)
                <tr>
                    <td class="text-center" style="font-weight: 700; color: #250048;">{{ $idx + 1 }}º</td>
                    <td><strong style="color: #24304a;">{{ $st['student_name'] }}</strong></td>
                    <td class="text-center"><span class="code-box">{{ $st['classroom_code'] }}</span></td>
                    <td class="text-center font-mono">{{ $st['access_code'] }}</td>
                    <td class="text-center" style="font-weight: 700; color: #250048;">
                        {{ $st['correct_count'] }} / {{ $st['total_questions'] }}
                    </td>
                    <td class="text-center" style="font-weight: 700; color: {{ $st['accuracy_percentage'] >= 70 ? '#047857' : ($st['accuracy_percentage'] >= 50 ? '#854d0e' : '#be123c') }};">
                        {{ $st['accuracy_percentage'] }}%
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 16px; color: #6b7280;">Nenhum estudante registrado nas turmas avaliadas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="page-footer">
        Relatório Consolidado gerado em {{ now()->format('d/m/Y \à\s H:i') }} pelo <strong>Sistema Corujinha · Prepara Avalia+</strong><br>
        Documento pedagógico oficial para uso diagnóstico e formativo institucional.
    </div>

</body>
</html>