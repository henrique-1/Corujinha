<?php

namespace App\Services\Reports;

class StudentCardImageGenerator
{
    /**
     * Gera o card em PNG de um estudante como string binária com a identidade visual do Corujinha.
     *
     * @param  array<string, mixed>  $student
     * @param  array<string, mixed>  $meta
     */
    public function generateCard(array $student, array $meta): string
    {
        $width = 850;
        $height = 240;

        $im = imagecreatetruecolor($width, $height);
        imagealphablending($im, true);

        // Paleta oficial de cores do Corujinha
        $bg = imagecolorallocate($im, 255, 255, 255);
        $cardBorder = imagecolorallocate($im, 223, 220, 248); // #dfdcf8 (Royal Purple 200)
        $headerBg = imagecolorallocate($im, 37, 0, 72); // #250048 (Royal Purple 950)
        $headerText = imagecolorallocate($im, 255, 255, 255);
        $broomYellow = imagecolorallocate($im, 255, 222, 0); // #FFDE00 (Broom Yellow 500)
        $textDark = imagecolorallocate($im, 36, 48, 74); // #24304a (Cloud Burst 950)
        $textMuted = imagecolorallocate($im, 108, 65, 192); // #6c41c0 (Royal Purple 700)
        $metricBg = imagecolorallocate($im, 246, 245, 253); // #f6f5fd (Royal Purple 50)
        $metricBorder = imagecolorallocate($im, 223, 220, 248); // #dfdcf8

        $statusGreenBg = imagecolorallocate($im, 209, 250, 229); // #d1fae5
        $statusGreenText = imagecolorallocate($im, 6, 95, 70); // #065f46
        $statusYellowBg = imagecolorallocate($im, 254, 243, 199); // #fef3c7
        $statusYellowText = imagecolorallocate($im, 133, 77, 14); // #854d0e

        $alertRedBg = imagecolorallocate($im, 254, 226, 226); // #fee2e2
        $alertRedText = imagecolorallocate($im, 153, 27, 27); // #991b1b

        // Fontes Poppins TrueType
        $fontBold = public_path('fonts/Poppins/Poppins-Bold.ttf');
        $fontSemiBold = public_path('fonts/Poppins/Poppins-SemiBold.ttf');
        $fontRegular = public_path('fonts/Poppins/Poppins-Regular.ttf');
        $hasTtf = file_exists($fontBold) && function_exists('imagettftext');

        // 1. Fundo do Card
        imagefilledrectangle($im, 0, 0, $width, $height, $bg);
        imagerectangle($im, 0, 0, $width - 1, $height - 1, $cardBorder);

        // 2. Faixa Superior (Roxo Royal 950 + Faixa Amarela Broom)
        imagefilledrectangle($im, 0, 0, $width, 42, $headerBg);
        imagefilledrectangle($im, 0, 42, $width, 45, $broomYellow);

        // 3. Logo Ícone do Corujinha (se existir)
        $iconPath = public_path('images/logo/corujinha-icone.png');
        if (file_exists($iconPath)) {
            $srcIcon = @imagecreatefrompng($iconPath);
            if ($srcIcon) {
                imagecopyresampled($im, $srcIcon, 18, 6, 0, 0, 26, 31, imagesx($srcIcon), imagesy($srcIcon));
            }
        }

        // 4. Título do Cabeçalho
        if ($hasTtf) {
            imagettftext($im, 10, 0, 52, 27, $headerText, $fontBold, 'CORUJINHA');
            imagettftext($im, 8.5, 0, 132, 26, $headerText, $fontRegular, '|  PREPARA AVALIA+ · CARD PEDAGÓGICO');

            $turmaText = 'TURMA: '.($meta['classroom_code'] ?? 'SALA');
            imagettftext($im, 8.5, 0, $width - 170, 26, $broomYellow, $fontBold, $turmaText);
        } else {
            imagestring($im, 4, 52, 13, 'CORUJINHA  |  CARD PEDAGOGICO', $headerText);
            imagestring($im, 4, $width - 170, 13, 'TURMA: '.($meta['classroom_code'] ?? 'SALA'), $broomYellow);
        }

        // 5. Nome do Estudante (Tipografia Poppins)
        $studentName = mb_strtoupper($student['student_name'] ?? 'ESTUDANTE');
        if ($hasTtf) {
            imagettftext($im, 15, 0, 25, 78, $textDark, $fontBold, $studentName);
        } else {
            imagestring($im, 5, 25, 62, $studentName, $textDark);
        }

        // 6. Código do Estudante
        $codeText = 'CÓDIGO: '.($student['access_code'] ?? 'N/A');
        if ($hasTtf) {
            imagettftext($im, 9.5, 0, 25, 100, $textMuted, $fontSemiBold, $codeText);
        } else {
            imagestring($im, 4, 25, 88, $codeText, $textMuted);
        }

        // 7. Status Badge
        $isCompleted = ($student['raw_status'] ?? '') === 'completed';
        $statusLabel = $isCompleted ? 'CONCLUIDO' : 'EM ANDAMENTO';
        $sBg = $isCompleted ? $statusGreenBg : $statusYellowBg;
        $sTxt = $isCompleted ? $statusGreenText : $statusYellowText;

        $this->drawRoundedRect($im, 25, 114, 180, 140, 5, $sBg);
        if ($hasTtf) {
            imagettftext($im, 8.5, 0, 36, 132, $sTxt, $fontBold, $statusLabel);
        } else {
            imagestring($im, 3, 35, 120, $statusLabel, $sTxt);
        }

        // 8. Informações de Tempo e Trocas de Aba
        $inicio = 'Início: '.($student['started_at'] ?? '—');
        $fim = 'Conclusão: '.($student['finished_at'] ?? '—');
        $switchesCount = (int) ($student['tab_switches_count'] ?? 0);

        if ($hasTtf) {
            imagettftext($im, 8, 0, 25, 165, $textDark, $fontRegular, $inicio);
            imagettftext($im, 8, 0, 25, 184, $textDark, $fontRegular, $fim);

            if ($switchesCount > 0) {
                $this->drawRoundedRect($im, 220, 152, 380, 186, 5, $alertRedBg);
                imagettftext($im, 8, 0, 230, 173, $alertRedText, $fontBold, "Trocas de Aba: {$switchesCount}x");
            } else {
                imagettftext($im, 8, 0, 220, 173, $statusGreenText, $fontSemiBold, 'Foco mantido (0 trocas)');
            }
        } else {
            imagestring($im, 2, 25, 160, $inicio, $textMuted);
            imagestring($im, 2, 25, 180, $fim, $textMuted);
            imagestring($im, 2, 220, 160, 'Trocas de Aba: '.$switchesCount, $textMuted);
        }

        // 9. Bloco de Métricas à Direita
        // 9.1 Quantidade de Acertos
        $this->drawRoundedRect($im, 490, 58, 655, 205, 8, $metricBg);
        imagerectangle($im, 490, 58, 655, 205, $metricBorder);

        $acertosNum = ($student['correct_count'] ?? 0).' / '.($student['total_questions'] ?? 0);
        if ($hasTtf) {
            imagettftext($im, 7.5, 0, 505, 80, $textMuted, $fontBold, 'QUANTIDADE DE ACERTOS');
            imagettftext($im, 18, 0, 515, 126, $headerBg, $fontBold, $acertosNum);
            imagettftext($im, 8, 0, 515, 165, $textMuted, $fontRegular, 'questões respondidas');
        } else {
            imagestring($im, 2, 505, 75, 'QTD ACERTOS', $textMuted);
            imagestring($im, 5, 520, 115, $acertosNum, $headerBg);
        }

        // 9.2 Aproveitamento Geral (%)
        $this->drawRoundedRect($im, 670, 58, 830, 205, 8, $metricBg);
        imagerectangle($im, 670, 58, 830, 205, $metricBorder);

        $aprovNum = ($student['accuracy_percentage'] ?? 0).'%';
        $tempoMedioText = ($student['student_avg_time_seconds'] ?? 0).'s / questão';
        if ($hasTtf) {
            imagettftext($im, 7.5, 0, 685, 80, $textMuted, $fontBold, 'APROVEITAMENTO GERAL');
            imagettftext($im, 20, 0, 700, 126, $headerBg, $fontBold, $aprovNum);
            imagettftext($im, 8, 0, 700, 165, $textMuted, $fontRegular, $tempoMedioText);
        } else {
            imagestring($im, 2, 685, 75, 'APROVEITAMENTO', $textMuted);
            imagestring($im, 5, 710, 115, $aprovNum, $headerBg);
        }

        // 10. Rodapé Institucional
        $footerText = 'Gerado em '.date('d/m/Y \à\s H:i').' · Prepara Avalia+ / Sistema Corujinha';
        if ($hasTtf) {
            imagettftext($im, 7.5, 0, 25, 222, $textMuted, $fontRegular, $footerText);
        } else {
            imagestring($im, 2, 25, 215, $footerText, $textMuted);
        }

        ob_start();
        imagepng($im);

        return (string) ob_get_clean();
    }

    /**
     * Desenha um retângulo com cantos arredondados no recurso GD.
     */
    private function drawRoundedRect($im, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

        imagefilledellipse($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }
}
