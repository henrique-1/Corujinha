<?php

namespace App\Mail;

use App\Models\Assessment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssessmentReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $reportData
     */
    public function __construct(
        public Assessment $assessment,
        public array $reportData,
        public string $pdfBinary,
        public string $xlsxBinary
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Relatório Consolidado da Avaliação {$this->assessment->title} - Corujinha",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.assessment-report',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $id = $this->assessment->id;

        return [
            Attachment::fromData(fn () => $this->pdfBinary, "relatorio_avaliacao_{$id}.pdf")
                ->withMime('application/pdf'),
            Attachment::fromData(fn () => $this->xlsxBinary, "desempenho_avaliacao_{$id}.xlsx")
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
