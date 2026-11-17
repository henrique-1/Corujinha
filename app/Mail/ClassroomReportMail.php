<?php

namespace App\Mail;

use App\Models\Classroom;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClassroomReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $reportData
     */
    public function __construct(
        public Classroom $classroom,
        public array $reportData,
        public string $pdfBinary,
        public string $xlsxBinary,
        public string $imagesZipBinary
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Relatório Pedagógico da Sala {$this->classroom->name} ({$this->classroom->code}) - Corujinha",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.classroom-report',
        );
    }

    /**
     * Anexa o PDF e XLSX diretamente fora do ZIP, e as imagens dentro do ZIP.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $code = $this->classroom->code;

        return [
            Attachment::fromData(fn () => $this->pdfBinary, "relatorio_{$code}.pdf")
                ->withMime('application/pdf'),
            Attachment::fromData(fn () => $this->xlsxBinary, "desempenho_{$code}.xlsx")
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            Attachment::fromData(fn () => $this->imagesZipBinary, "cards_estudantes_{$code}.zip")
                ->withMime('application/zip'),
        ];
    }
}
