<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Mail\AssessmentReportMail;
use App\Models\Assessment;
use App\Services\Reports\AssessmentReportDataService;
use App\Services\Reports\ExcelReportGenerator;
use App\Services\Reports\PdfReportGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use ZipArchive;

class AssessmentReportController extends Controller
{
    public function __construct(
        protected AssessmentReportDataService $dataService,
        protected PdfReportGenerator $pdfGenerator,
        protected ExcelReportGenerator $excelGenerator
    ) {}

    /**
     * Download do relatório consolidado da avaliação nos formatos: zip, pdf, xlsx.
     */
    public function download(Request $request, Assessment $avaliacao): Response
    {
        $this->authorizeAssessment($avaliacao);

        $type = $request->input('type', 'zip');
        $data = $this->dataService->getData($avaliacao);
        $id = $avaliacao->id;

        if ($type === 'pdf') {
            $binary = $this->pdfGenerator->generateAssessmentPdf($data);

            return response($binary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"relatorio_avaliacao_{$id}.pdf\"",
            ]);
        }

        if ($type === 'xlsx') {
            $binary = $this->excelGenerator->generateAssessmentXlsx($data);

            return response($binary, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"desempenho_avaliacao_{$id}.xlsx\"",
            ]);
        }

        // Default: Pacote ZIP com PDF e XLSX
        $pdfBinary = $this->pdfGenerator->generateAssessmentPdf($data);
        $xlsxBinary = $this->excelGenerator->generateAssessmentXlsx($data);

        $tempFile = tempnam(sys_get_temp_dir(), 'avaliacao_zip_');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString("relatorio_avaliacao_{$id}.pdf", $pdfBinary);
        $zip->addFromString("desempenho_avaliacao_{$id}.xlsx", $xlsxBinary);
        $zip->close();

        $binary = (string) file_get_contents($tempFile);
        @unlink($tempFile);

        return response($binary, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => "attachment; filename=\"relatorio_avaliacao_{$id}.zip\"",
        ]);
    }

    /**
     * Envia o relatório consolidado por e-mail com PDF e XLSX anexados.
     */
    public function sendEmail(Request $request, Assessment $avaliacao): RedirectResponse
    {
        $this->authorizeAssessment($avaliacao);

        $targetEmail = $request->filled('email') ? $request->input('email') : Auth::user()->email;
        $data = $this->dataService->getData($avaliacao);

        $pdfBinary = $this->pdfGenerator->generateAssessmentPdf($data);
        $xlsxBinary = $this->excelGenerator->generateAssessmentXlsx($data);

        Mail::to($targetEmail)->send(new AssessmentReportMail(
            $avaliacao,
            $data,
            $pdfBinary,
            $xlsxBinary
        ));

        return back()->with('success', "Relatório consolidado enviado com sucesso para {$targetEmail}!");
    }

    protected function authorizeAssessment(Assessment $assessment): void
    {
        if (Auth::user()->isSuperUser()) {
            return;
        }

        if ((int) $assessment->user_id !== (int) Auth::id()) {
            // Verifica se tem permissão compartilhada
            if (method_exists($assessment, 'canView') && $assessment->canView(Auth::user())) {
                return;
            }
            abort(403, 'Acesso não autorizado a esta avaliação.');
        }
    }
}
