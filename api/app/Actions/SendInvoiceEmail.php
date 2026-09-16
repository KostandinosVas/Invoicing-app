<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use DomainException;
use Illuminate\Support\Facades\Mail;

final class SendInvoiceEmail
{
    public function handle(Invoice $invoice, string $email): void
    {
        if ($invoice->number === null) {
            throw new DomainException(
                'Δεν αποστέλλεται προσχέδιο. Εκδώστε πρώτα το παραστατικό.'
            );
        }

        Mail::to($email)->queue(new InvoiceMail($invoice));
    }
}
