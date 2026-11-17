<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Pedagógico - {{ $classroom->name }}</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f7f3fb; color: #171026; padding: 25px; margin: 0;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; border: 1px solid #dfd3ee; overflow: hidden;">
        <div style="background-color: #250048; padding: 24px; color: #ffffff; text-align: center;">
            <h1 style="margin: 0; font-size: 20px; font-weight: bold; color: #FFDE00;">CORUJINHA</h1>
            <p style="margin: 6px 0 0 0; font-size: 14px; color: #e2d4f5;">Relatório Pedagógico da Sala</p>
        </div>

        <div style="padding: 24px;">
            <p style="font-size: 15px; font-weight: bold; margin-top: 0;">Olá, Professor(a)!</p>
            <p style="font-size: 13px; line-height: 1.6; color: #4b3d63;">
                Conforme solicitado, enviamos em anexo os relatórios consolidados de desempenho da sua sala <strong>{{ $classroom->name }}</strong> (Código: <code>{{ $classroom->code }}</code>).
            </p>

            <div style="background-color: #f7f3fb; border-radius: 12px; padding: 16px; margin: 20px 0; border: 1px solid #e5d8f3;">
                <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: bold; color: #250048; text-transform: uppercase;">Resumo Rápido da Turma:</p>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #171026; line-height: 1.8;">
                    <li><strong>Aproveitamento da Sala:</strong> {{ $reportData['classroom_accuracy'] }}%</li>
                    <li><strong>Total de Alunos:</strong> {{ $reportData['total_students'] }} ({{ $reportData['completed_count'] }} concluídos)</li>
                    <li><strong>Tempo Médio por Questão:</strong> {{ $reportData['overall_avg_response_time'] }}s</li>
                    <li><strong>Modo de Jogo:</strong> {{ $classroom->isCorujao() ? 'Modo Corujão (Hardcore)' : 'Modo Corujinha (Padrão)' }}</li>
                </ul>
            </div>

            <p style="font-size: 12px; color: #6b5887; line-height: 1.8;">
                <strong>Arquivos anexados a este e-mail:</strong><br>
                <span style="display: inline-block; padding: 2px 6px; background-color: #f1ebf9; color: #250048; border-radius: 4px; font-weight: bold; font-size: 11px;">PDF</span> <code>relatorio_{{ $classroom->code }}.pdf</code> - Relatório visual executivo em PDF.<br>
                <span style="display: inline-block; padding: 2px 6px; background-color: #f1ebf9; color: #250048; border-radius: 4px; font-weight: bold; font-size: 11px;">XLSX</span> <code>desempenho_{{ $classroom->code }}.xlsx</code> - Planilha Excel completa com tempos e respostas de cada aluno.<br>
                <span style="display: inline-block; padding: 2px 6px; background-color: #f1ebf9; color: #250048; border-radius: 4px; font-weight: bold; font-size: 11px;">ZIP</span> <code>cards_estudantes_{{ $classroom->code }}.zip</code> - Arquivo compactado contendo as imagens individuais dos alunos.
            </p>

            <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #eee; font-size: 11px; color: #9c89b8; text-align: center;">
                Este e-mail foi gerado automaticamente pelo Sistema Corujinha.
            </div>
        </div>
    </div>
</body>
</html>
