<?php

namespace App\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfReportGenerator
{
    /**
     * Gera o PDF da sala de aula como string binária.
     *
     * @param  array<string, mixed>  $data
     */
    public function generateClassroomPdf(array $data): string
    {
        $pdf = Pdf::loadView('reports.classroom-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true);

        return $pdf->output();
    }

    /**
     * Gera o PDF consolidado da avaliação como string binária.
     *
     * @param  array<string, mixed>  $data
     */
    public function generateAssessmentPdf(array $data): string
    {
        $pdf = Pdf::loadView('reports.assessment-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true);

        return $pdf->output();
    }
}
