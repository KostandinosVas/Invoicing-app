<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Series;
use DomainException;
use Illuminate\Support\Facades\DB;

final class IssueInvoice
{
    public function handle(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            if ($invoice->lines()->doesntExist()) {
                throw new DomainException('Δεν μπορεί να εκδοθεί παραστατικό χωρίς γραμμές.');
            }

            $series = Series::query()
                ->whereKey($invoice->series_id)
                ->lockForUpdate()
                ->firstOrFail();

            $next = $series->last_number + 1;

            $invoice->transitionTo(InvoiceStatus::Issued);
            $invoice->recalculateTotals();
            $invoice->number = $next;
            $invoice->save();

            $series->last_number = $next;
            $series->save();

            return $invoice;
        });
    }
}
