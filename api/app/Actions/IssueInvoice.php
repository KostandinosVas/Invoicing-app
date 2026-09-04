<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Series;
use Illuminate\Support\Facades\DB;

final class IssueInvoice
{
    public function handle(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
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
