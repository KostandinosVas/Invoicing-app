<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Jobs\CancelInvoiceAtMydata;
use App\Models\Invoice;
use DomainException;

final class CancelInvoice
{
    public function handle(Invoice $invoice): void
    {
        if ($invoice->mydata_mark === null) {
            throw new DomainException(
                'Δεν ακυρώνεται παραστατικό που δεν έχει διαβιβαστεί στο myDATA.'
            );
        }

        if ($invoice->status === InvoiceStatus::Cancelled) {
            throw new DomainException('Το παραστατικό είναι ήδη ακυρωμένο.');
        }

        if ($invoice->status !== InvoiceStatus::Submitted) {
            throw new DomainException(
                'Ακυρώνονται μόνο παραστατικά που έχουν γίνει δεκτά από την ΑΑΔΕ.'
            );
        }

        CancelInvoiceAtMydata::dispatch($invoice);
    }
}
