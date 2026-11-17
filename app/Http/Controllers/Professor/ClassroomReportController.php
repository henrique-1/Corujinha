<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Mail\ClassroomReportMail;
use App\Models\Classroom;
use App\Services\Reports\ClassroomReportDataService;
use App\Services\Reports\ExcelReportGenerator;
use App\Services\Reports\PdfReportGenerator;
use App\Services\Reports\ReportZipBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ClassroomReportController extends Controller
{
    public function __construct(
        protected ClassroomReportDataService $dataService,
        protected PdfReportGenerator $pdfGenerator,
        protected ExcelReportGenerator $excelGenerator,
        protected ReportZipBuilder $zipBuilder
    ) {}

    /**
     * Download do relatório da sala de aula nos formatos: zip, pdf, xlsx, images.
     */
    public function download(Request $request, Classroom $sala): Response
    {
        $this->authorizeClassroom($sala);

        $type = $request->input('type', 'zip');
        $data = $this->dataService->getData($sala);
        $code = $sala->code;

        if ($type === 'pdf') {
            $binary = $this->pdfGenerator->generateClassroomPdf($data);

            return response($binary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"relatorio_{$code}.pdf\"",
            ]);
        }

        if ($type === 'xlsx') {
            $binary = $this->excelGenerator->generateClassroomXlsx($data);

            return response($binary, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"desempenho_{$code}.xlsx\"",
            ]);
        }

        if ($type === 'images') {
            $binary = $this->zipBuilder->buildImagesOnlyZip($data);

            return response($binary, 200, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => "attachment; filename=\"cards_estudantes_{$code}.zip\"",
            ]);
        }

        // Default: Pacote Completo ZIP
        $pdfBinary = $this->pdfGenerator->generateClassroomPdf($data);
        $xlsxBinary = $this->excelGenerator->generateClassroomXlsx($data);
        $zipBinary = $this->zipBuilder->buildFullPackageZip($data, $pdfBinary, $xlsxBinary);

        return response($zipBinary, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => "attachment; filename=\"relatorio_completo_{$code}.zip\"",
        ]);
    }

    /**
     * Envia o relatório por e-mail com PDF e XLSX anexados diretamente e o ZIP de imagens.
     */
    public function sendEmail(Request $request, Classroom $sala): RedirectResponse
    {
        $this->authorizeClassroom($sala);

        $targetEmail = $request->filled('email') ? $request->input('email') : Auth::user()->email;
        $data = $this->dataService->getData($sala);

        $pdfBinary = $this->pdfGenerator->generateClassroomPdf($data);
        $xlsxBinary = $this->excelGenerator->generateClassroomXlsx($data);
        $imagesZipBinary = $this->zipBuilder->buildImagesOnlyZip($data);

        Mail::to($targetEmail)->send(new ClassroomReportMail(
            $sala,
            $data,
            $pdfBinary,
            $xlsxBinary,
            $imagesZipBinary
        ));

        return back()->with('success', "Relatório da sala enviado com sucesso para {$targetEmail}!");
    }

    protected function authorizeClassroom(Classroom $classroom): void
    {
        if (Auth::user()->isSuperUser()) {
            return;
        }

        if ((int) $classroom->user_id !== (int) Auth::id()) {
            abort(403, 'Acesso não autorizado a esta sala de aula.');
        }
    }
}
