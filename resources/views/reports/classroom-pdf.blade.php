<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Pedagógico · {{ $classroom->name }} ({{ $classroom->code }})</title>
    @php
        $logoPath = public_path('images/logo/corujinha-horizontal-300dpi.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;

        $iconPath = public_path('images/logo/corujinha-icone.png');
        $iconBase64 = file_exists($iconPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($iconPath)) : null;

        $corujaoIconPath = public_path('images/logo/CORUJAO_300_DPI.png');
        $corujaoIconBase64 = file_exists($corujaoIconPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($corujaoIconPath)) : null;

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

        .badge-mode-corujao {
            display: inline-block;
            background-color: #ff20b5; /* Shocking Pink 500 */
            color: #ffffff;
            font-weight: 700;
            font-size: 8.5px;
            padding: 4px 10px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-mode-corujinha {
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

        .badge-pill-green {
            background-color: #d1fae5;
            color: #065f46;
            font-weight: 700;
            font-size: 7.5px;
            padding: 1.5px 5px;
            border-radius: 4px;
            display: inline-block;
        }

        .badge-pill-yellow {
            background-color: #fef3c7;
            color: #854d0e;
            font-weight: 700;
            font-size: 7.5px;
            padding: 1.5px 5px;
            border-radius: 4px;
            display: inline-block;
        }

        .badge-switch-alert {
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: 700;
            font-size: 8px;
            padding: 1.5px 5px;
            border-radius: 4px;
            display: inline-block;
        }

        .badge-switch-ok {
            color: #059669;
            font-size: 8px;
            font-weight: 600;
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
                <td style="width: 58%;">
                    <div style="margin-bottom: 4px;">
                        @if($logoBase64)
                            <img src="{{ $logoBase64 }}" height="32" style="max-height: 32px; vertical-align: middle;" alt="Corujinha">
                        @else
                            <span style="font-size: 18px; font-weight: 700; color: #FFDE00;">CORUJINHA</span>
                        @endif
                    </div>
                    <div class="inst-title">Prepara Avalia+ · Diagnóstico & Aprendizagem Formativa</div>
                    <div class="report-title">Relatório de Desempenho da Sala</div>
                    <div class="meta-line">
                        <strong>Turma:</strong> {{ $classroom->name }} &nbsp;|&nbsp;
                        <strong>Código:</strong> <span style="color: #FFDE00; font-weight: bold;">{{ $classroom->code }}</span> &nbsp;|&nbsp;
                        <strong>Professor(a):</strong> {{ $classroom->user?->name ?? 'Não Informado' }}
                    </div>
                    <div class="meta-line" style="font-size: 8px; color: #e2d4f5;">
                        <strong>Avaliação:</strong> {{ $assessment?->title }} &nbsp;|&nbsp;
                        <strong>Período:</strong> {{ $assessment?->academic_period ?? 'Ano Letivo Vigente' }}
                    </div>
                </td>
                <td class="badges-container" style="width: 42%;">
                    @if($classroom->isCorujao())
                        <div class="badge-mode-corujao">
                            @if($corujaoIconBase64)
                                <img src="{{ $corujaoIconBase64 }}" width="11" height="11" style="vertical-align: middle; margin-right: 3px;" alt="Corujão">
                            @endif
                            Modo Corujão · Hardcore
                        </div>
                    @else
                        <div class="badge-mode-corujinha">
                            @if($iconBase64)
                                <img src="{{ $iconBase64 }}" width="11" height="11" style="vertical-align: middle; margin-right: 3px;" alt="Corujinha">
                            @endif
                            Modo Corujinha · Padrão
                        </div>
                    @endif
                    <br>
                    <span class="badge-official">Documento Oficial de Avaliação</span>
                    <div style="font-size: 7.5px; color: #dfdcf8; margin-top: 4px;">
                        Aplicação: {{ $classroom->applicationDateFormatted() }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Summary Metrics Grid --}}
    <table class="metrics-table">
        <tr>
            <td class="metric-card" style="width: 25%;">
                <div class="metric-label">Aproveitamento Geral</div>
                <div class="metric-number">{{ $classroom_accuracy }}%</div>
                <div class="metric-help">Média de acertos da turma</div>
            </td>
            <td class="metric-card" style="width: 25%;">
                <div class="metric-label">Participação da Turma</div>
                <div class="metric-number">{{ $completed_count }}/{{ $total_students }}</div>
                <div class="metric-help">Estudantes concluíram a prova</div>
            </td>
            <td class="metric-card" style="width: 25%;">
                <div class="metric-label">Tempo Médio p/ Item</div>
                <div class="metric-number">{{ $overall_avg_response_time }}s</div>
                <div class="metric-help">Ritmo de resolução por questão</div>
            </td>
            <td class="metric-card" style="width: 25%;">
                <div class="metric-label">Total de Questões</div>
                <div class="metric-number">{{ count($questions) }}</div>
                <div class="metric-help">Itens avaliados no caderno</div>
            </td>
        </tr>
    </table>

    {{-- Formative Pedagogical Highlights --}}
    <div class="section-heading">Destaques Pedagógicos & Intervenção</div>
    <table class="highlights-table">
        <tr>
            <td class="highlight-card highlight-success" style="width: 50%;">
                <div class="highlight-tag">✓ Habilidade em Destaque (Maior Índice de Acertos)</div>
                @if($most_correct_question)
                    <div class="highlight-prompt">
                        Item {{ $most_correct_question['index'] }}: {{ \Illuminate\Support\Str::limit($most_correct_question['prompt'], 85) }}
                    </div>
                    <div style="color: #047857; font-weight: 700; font-size: 8.5px;">
                        {{ $most_correct_question['correct_count'] }} acertos (Taxa de Sucesso: {{ $most_correct_question['accuracy_rate'] }}%)
                    </div>
                    <div class="highlight-guide" style="color: #065f46;">
                        <strong>Guia Pedagógico:</strong> Conteúdo bem consolidado pela turma; utilize como ponto de ancoragem para aprofundamento.
                    </div>
                @else
                    <div style="color: #6b7280; font-size: 8.5px;">Dados de respostas ainda não computados.</div>
                @endif
            </td>
            <td class="highlight-card highlight-alert" style="width: 50%;">
                <div class="highlight-tag">⚠ Intervenção Recomendada (Maior Índice de Erros)</div>
                @if($most_wrong_question)
                    <div class="highlight-prompt">
                        Item {{ $most_wrong_question['index'] }}: {{ \Illuminate\Support\Str::limit($most_wrong_question['prompt'], 85) }}
                    </div>
                    <div style="color: #be123c; font-weight: 700; font-size: 8.5px;">
                        {{ $most_wrong_question['wrong_count'] }} erros (Taxa de Acerto: {{ $most_wrong_question['accuracy_rate'] }}%)
                    </div>
                    <div class="highlight-guide" style="color: #9f1239;">
                        <strong>Guia Pedagógico:</strong> Ponto prioritário de revisão em sala de aula; retome o descritor e as causas de distração dos alunos.
                    </div>
                @else
                    <div style="color: #6b7280; font-size: 8.5px;">Dados de respostas ainda não computados.</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Question Performance Table --}}
    <div class="section-heading">Desempenho Detalhado por Questão</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;" class="text-center">#</th>
                <th style="width: 50%;">Enunciado / Descritor da Questão</th>
                <th style="width: 11%;" class="text-center">Acertos</th>
                <th style="width: 11%;" class="text-center">Erros</th>
                <th style="width: 11%;" class="text-center">Taxa (%)</th>
                <th style="width: 11%;" class="text-center">Tempo Médio</th>
            </tr>
        </thead>
        <tbody>
            @foreach($question_stats as $stat)
                <tr>
                    <td class="text-center" style="font-weight: 700; color: #250048;">{{ $stat['index'] }}</td>
                    <td>
                        <strong>{{ \Illuminate\Support\Str::limit($stat['prompt'], 90) }}</strong>
                        @if($stat['support_title'])
                            <br><span style="color: #6c41c0; font-size: 7.5px;">Texto-Base / Suporte: {{ $stat['support_title'] }}</span>
                        @endif
                    </td>
                    <td class="text-center" style="color: #047857; font-weight: 700;">{{ $stat['correct_count'] }}</td>
                    <td class="text-center" style="color: #be123c; font-weight: 700;">{{ $stat['wrong_count'] }}</td>
                    <td class="text-center" style="font-weight: 700; color: {{ $stat['accuracy_rate'] >= 70 ? '#047857' : ($stat['accuracy_rate'] >= 50 ? '#854d0e' : '#be123c') }};">
                        {{ $stat['accuracy_rate'] }}%
                    </td>
                    <td class="text-center" style="color: #553280;">{{ $stat['avg_time_seconds'] }}s</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Individual Students Results Table --}}
    <div class="section-heading">Resultados Individuais dos Estudantes</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">Pos.</th>
                <th style="width: 30%;">Nome do Estudante</th>
                <th style="width: 13%;" class="text-center">Código</th>
                <th style="width: 11%;" class="text-center">Status</th>
                <th style="width: 10%;" class="text-center">Acertos</th>
                <th style="width: 11%;" class="text-center">Aproveitamento</th>
                <th style="width: 10%;" class="text-center">Tempo Médio</th>
                <th style="width: 10%;" class="text-center">Trocas de Aba</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $pos => $student)
                <tr>
                    <td class="text-center" style="font-weight: 700; color: #250048;">{{ $pos + 1 }}º</td>
                    <td><strong style="color: #24304a;">{{ $student['student_name'] }}</strong></td>
                    <td class="text-center"><span class="code-box">{{ $student['access_code'] }}</span></td>
                    <td class="text-center">
                        <span class="{{ $student['raw_status'] === 'completed' ? 'badge-pill-green' : 'badge-pill-yellow' }}">
                            {{ $student['status'] }}
                        </span>
                    </td>
                    <td class="text-center" style="font-weight: 700; color: #250048;">
                        {{ $student['correct_count'] }} / {{ $student['total_questions'] }}
                    </td>
                    <td class="text-center" style="font-weight: 700; color: {{ $student['accuracy_percentage'] >= 70 ? '#047857' : ($student['accuracy_percentage'] >= 50 ? '#854d0e' : '#be123c') }};">
                        {{ $student['accuracy_percentage'] }}%
                    </td>
                    <td class="text-center" style="color: #553280;">{{ $student['student_avg_time_seconds'] }}s</td>
                    <td class="text-center">
                        @if(($student['tab_switches_count'] ?? 0) > 0)
                            <span class="badge-switch-alert" title="{{ $student['tab_switches_count'] }} saída(s) de aba detectada(s)">
                                ⚠ {{ $student['tab_switches_count'] }}x
                            </span>
                        @else
                            <span class="badge-switch-ok">0</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 16px; color: #6b7280;">Nenhum estudante respondeu a esta sala até o momento.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="page-footer">
        Relatório gerado em {{ now()->format('d/m/Y \à\s H:i') }} pelo <strong>Sistema Corujinha · Prepara Avalia+</strong><br>
        Documento pedagógico oficial para uso diagnóstico e formativo institucional.
    </div>

</body>
</html>
