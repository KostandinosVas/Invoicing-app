<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MydataInvoiceType;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;

final class InvoicePdf
{
    public function render(Invoice $invoice): PdfDocument
    {
        $invoice->loadMissing(['lines', 'company', 'series']);

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $invoice->company,
            'series' => $invoice->series,
            'typeLabel' => $this->typeLabel($invoice),
            'format' => fn (int $cents): string => number_format($cents / 100, 2, ',', '.').' €',
        ])->setPaper('a4');
    }

    public function filename(Invoice $invoice): string
    {
        if ($invoice->number === null) {
            return "draft-{$invoice->id}.pdf";
        }

        $series = $invoice->series;
        $code = $series !== null ? $series->code : '';

        // Ο κωδικός σειράς μπορεί να είναι ελληνικός· τον καθαρίζουμε για filename.
        $safe = preg_replace('/[^A-Za-z0-9]/', '', $code) ?: 'DOC';

        return "{$safe}-{$invoice->number}.pdf";
    }

    private function typeLabel(Invoice $invoice): string
    {
        return match ($invoice->document_type) {
            'credit_note' => 'ΠΙΣΤΩΤΙΚΟ ΤΙΜΟΛΟΓΙΟ',
            default => $invoice->mydata_invoice_type === MydataInvoiceType::ServicesInvoice
                ? 'ΤΙΜΟΛΟΓΙΟ ΠΑΡΟΧΗΣ ΥΠΗΡΕΣΙΩΝ'
                : 'ΤΙΜΟΛΟΓΙΟ ΠΩΛΗΣΗΣ',
        };
    }
}
