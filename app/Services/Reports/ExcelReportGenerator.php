<?php

namespace App\Services\Reports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelReportGenerator
{
    /**
     * Gera o arquivo XLSX do relatório da sala de aula como string binária.
     *
     * @param  array<string, mixed>  $data
     */
    public function generateClassroomXlsx(array $data): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Corujinha - Sistema Pedagógico')
            ->setTitle('Relatório de Sala de Aula - '.$data['classroom']->name);

        // Aba 1: Resumo Pedagógico
        $sheetOverview = $spreadsheet->getActiveSheet();
        $sheetOverview->setTitle('Resumo da Sala');
        $this->buildClassroomOverviewSheet($sheetOverview, $data);

        // Aba 2: Detalhes dos Estudantes e Questões
        $sheetStudents = $spreadsheet->createSheet();
        $sheetStudents->setTitle('Respostas e Tempos');
        $this->buildClassroomStudentsSheet($sheetStudents, $data);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    /**
     * Gera o arquivo XLSX consolidado da avaliação como string binária.
     *
     * @param  array<string, mixed>  $data
     */
    public function generateAssessmentXlsx(array $data): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Corujinha - Sistema Pedagógico')
            ->setTitle('Relatório Consolidado - '.$data['assessment']->title);

        $sheetOverview = $spreadsheet->getActiveSheet();
        $sheetOverview->setTitle('Consolidado Geral');
        $this->buildAssessmentOverviewSheet($sheetOverview, $data);

        $sheetStudents = $spreadsheet->createSheet();
        $sheetStudents->setTitle('Quadro de Estudantes');
        $this->buildAssessmentStudentsSheet($sheetStudents, $data);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    protected function buildClassroomOverviewSheet($sheet, array $data): void
    {
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'RELATÓRIO DE SALA DE AULA - CORUJINHA');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF250048');

        $rows = [
            ['Turma / Sala:', $data['classroom']->name],
            ['Código da Sala:', $data['classroom']->code],
            ['Modo de Jogo:', ($data['classroom']->game_mode ?? 'corujinha') === 'corujao' ? 'Modo Corujão (Hardcore)' : 'Modo Corujinha (Padrão)'],
            ['Avaliação:', $data['assessment']?->title ?? 'N/A'],
            ['Aproveitamento da Sala (%):', $data['classroom_accuracy'].'%'],
            ['Tempo Médio Geral do Questionário (s):', $data['overall_avg_response_time'].' segundos'],
            ['Total de Estudantes:', $data['total_students']],
            ['Concluíram a Avaliação:', $data['completed_count']],
            ['Questão com Mais Acertos:', $data['most_correct_question'] ? 'Questão '.$data['most_correct_question']['index'].' ('.$data['most_correct_question']['correct_count'].' acertos - '.$data['most_correct_question']['accuracy_rate'].'%)' : 'N/A'],
            ['Questão com Mais Erros:', $data['most_wrong_question'] ? 'Questão '.$data['most_wrong_question']['index'].' ('.$data['most_wrong_question']['wrong_count'].' erros - '.$data['most_wrong_question']['accuracy_rate'].'%)' : 'N/A'],
        ];

        $currRow = 3;
        foreach ($rows as $item) {
            $sheet->setCellValue('A'.$currRow, $item[0]);
            $sheet->setCellValue('B'.$currRow, $item[1]);
            $sheet->getStyle('A'.$currRow)->getFont()->setBold(true)->getColor()->setARGB('FF38215F');
            $currRow++;
        }

        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    protected function buildClassroomStudentsSheet($sheet, array $data): void
    {
        $headers = [
            'Nome do Aluno',
            'Código do Aluno',
            'Status',
            'Trocas de Aba / Foco',
            'Quantidade de Acertos',
            'Aproveitamento do Aluno (%)',
            'Aproveitamento da Sala (%)',
            'Tempo Médio de Resposta do Aluno (s)',
            'Tempo Médio Total do Questionário (s)',
        ];

        $questions = $data['questions'];
        foreach ($questions as $idx => $q) {
            $headers[] = 'Q'.($idx + 1).' - Resposta';
            $headers[] = 'Q'.($idx + 1).' - Tempo (s)';
        }

        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF250048']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $rowIdx = 2;
        foreach ($data['students'] as $student) {
            $row = [
                $student['student_name'],
                $student['access_code'],
                $student['status'],
                $student['tab_switches_count'] ?? 0,
                $student['correct_count'].' / '.$student['total_questions'],
                $student['accuracy_percentage'].'%',
                $data['classroom_accuracy'].'%',
                $student['student_avg_time_seconds'],
                $data['overall_avg_response_time'],
            ];

            foreach ($questions as $q) {
                $ansInfo = $student['answers_per_question'][$q->id] ?? null;
                $row[] = $ansInfo ? $ansInfo['answer'] : 'N/A';
                $row[] = $ansInfo ? $ansInfo['time_seconds'] : 0;
            }

            $sheet->fromArray([$row], null, 'A'.$rowIdx);
            $rowIdx++;
        }

        foreach (range(1, count($headers)) as $cIdx) {
            $colLetter = Coordinate::stringFromColumnIndex($cIdx);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }

    protected function buildAssessmentOverviewSheet($sheet, array $data): void
    {
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'RELATÓRIO CONSOLIDADO DA AVALIAÇÃO - CORUJINHA');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF250048');

        $rows = [
            ['Avaliação:', $data['assessment']->title],
            ['Período Letivo:', $data['assessment']->academic_period ?? 'Geral'],
            ['Aproveitamento Geral Consolidado (%):', $data['overall_accuracy'].'%'],
            ['Total de Turmas / Salas:', $data['total_classrooms']],
            ['Total de Estudantes Registrados:', $data['total_students']],
            ['Total de Participações Concluídas:', $data['total_completed']],
            ['Questão com Maior Taxa de Acertos:', $data['most_correct_question'] ? 'Questão '.$data['most_correct_question']['index'].' ('.$data['most_correct_question']['correct_count'].' acertos - '.$data['most_correct_question']['accuracy_rate'].'%)' : 'N/A'],
            ['Questão com Maior Taxa de Erros:', $data['most_wrong_question'] ? 'Questão '.$data['most_wrong_question']['index'].' ('.$data['most_wrong_question']['wrong_count'].' erros - '.$data['most_wrong_question']['accuracy_rate'].'%)' : 'N/A'],
        ];

        $currRow = 3;
        foreach ($rows as $item) {
            $sheet->setCellValue('A'.$currRow, $item[0]);
            $sheet->setCellValue('B'.$currRow, $item[1]);
            $sheet->getStyle('A'.$currRow)->getFont()->setBold(true)->getColor()->setARGB('FF38215F');
            $currRow++;
        }

        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    protected function buildAssessmentStudentsSheet($sheet, array $data): void
    {
        $headers = [
            'Estudante',
            'Turma / Sala',
            'Código da Turma',
            'Código do Aluno',
            'Status',
            'Trocas de Aba / Foco',
            'Quantidade de Acertos',
            'Aproveitamento Individual (%)',
            'Tempo Médio (s)',
        ];

        $sheet->fromArray([$headers], null, 'A1');
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF250048']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $rowIdx = 2;
        foreach ($data['students'] as $student) {
            $row = [
                $student['student_name'],
                $student['classroom_name'] ?? 'N/A',
                $student['classroom_code'] ?? 'N/A',
                $student['access_code'],
                $student['status'],
                $student['tab_switches_count'] ?? 0,
                $student['correct_count'].' / '.$student['total_questions'],
                $student['accuracy_percentage'].'%',
                $student['student_avg_time_seconds'],
            ];

            $sheet->fromArray([$row], null, 'A'.$rowIdx);
            $rowIdx++;
        }

        foreach (range(1, count($headers)) as $cIdx) {
            $colLetter = Coordinate::stringFromColumnIndex($cIdx);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }
}
