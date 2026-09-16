<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoicePdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        $company = $this->invoice->company;
        $series = $this->invoice->series;

        $reference = $this->invoice->number !== null && $series !== null
            ? "{$series->code}-{$this->invoice->number}"
            : 'προσχέδιο';

        $companyName = $company !== null ? $company->name : '';

        return new Envelope(
            subject: "Παραστατικό {$reference} — {$companyName}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.invoice');
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        $pdf = app(InvoicePdf::class);

        return [
            Attachment::fromData(
                fn (): string => $pdf->render($this->invoice)->output(),
                $pdf->filename($this->invoice),
            )->withMime('application/pdf'),
        ];
    }
}
