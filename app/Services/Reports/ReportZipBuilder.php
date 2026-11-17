<?php

namespace App\Services\Reports;

use ZipArchive;

class ReportZipBuilder
{
    public function __construct(
        protected StudentCardImageGenerator $cardGenerator
    ) {}

    /**
     * Cria um ZIP contendo apenas os cards de imagem dos estudantes.
     *
     * @param  array<string, mixed>  $data
     */
    public function buildImagesOnlyZip(array $data): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cards_zip_');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $classroomCode = $data['classroom']->code ?? 'SALA';
        $meta = ['classroom_code' => $classroomCode];

        foreach ($data['students'] as $idx => $student) {
            $pngContent = $this->cardGenerator->generateCard($student, $meta);
            $cleanName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $student['student_name']);
            $filename = sprintf('%02d_%s_%s.png', $idx + 1, $student['access_code'], $cleanName);
            $zip->addFromString($filename, $pngContent);
        }

        $zip->close();
        $binary = (string) file_get_contents($tempFile);
        @unlink($tempFile);

        return $binary;
    }

    /**
     * Cria o pacote ZIP completo contendo PDF, XLSX e a pasta com os cards de imagem.
     *
     * @param  array<string, mixed>  $data
     */
    public function buildFullPackageZip(array $data, string $pdfContent, string $xlsxContent): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'relatorio_full_');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $code = $data['classroom']->code ?? 'SALA';

        // Adiciona PDF na raiz do ZIP
        $zip->addFromString("relatorio_{$code}.pdf", $pdfContent);

        // Adiciona XLSX na raiz do ZIP
        $zip->addFromString("desempenho_{$code}.xlsx", $xlsxContent);

        // Adiciona pasta com cards dos alunos
        $meta = ['classroom_code' => $code];
        foreach ($data['students'] as $idx => $student) {
            $pngContent = $this->cardGenerator->generateCard($student, $meta);
            $cleanName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $student['student_name']);
            $filename = sprintf('cards_estudantes/%02d_%s_%s.png', $idx + 1, $student['access_code'], $cleanName);
            $zip->addFromString($filename, $pngContent);
        }

        $zip->close();
        $binary = (string) file_get_contents($tempFile);
        @unlink($tempFile);

        return $binary;
    }
}
