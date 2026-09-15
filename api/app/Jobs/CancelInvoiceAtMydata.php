<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Exceptions\MydataNotConfigured;
use App\Models\Invoice;
use App\Services\Mydata\MydataClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CancelInvoiceAtMydata implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    public function __construct(
        public readonly Invoice $invoice,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(MydataClient $client): void
    {
        $invoice = $this->invoice->fresh();

        if ($invoice === null || $invoice->status === InvoiceStatus::Cancelled) {
            return;
        }

        $company = $invoice->company;
        $mark = $invoice->mydata_mark;

        if ($company === null || $mark === null) {
            return;
        }

        try {
            $response = $client->cancelInvoice($company, $mark);
        } catch (MydataNotConfigured $exception) {
            $this->fail($exception);

            return;
        }

        if (! $response->accepted) {
            Log::warning('Η ΑΑΔΕ απέρριψε την ακύρωση', [
                'invoice_id' => $invoice->id,
                'errors' => $response->errors,
            ]);

            return;
        }

        $invoice->mydata_cancellation_mark = $response->mark;
        $invoice->cancelled_at = now();
        $invoice->transitionTo(InvoiceStatus::Cancelled);
        $invoice->save();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Αποτυχία ακύρωσης myDATA', [
            'invoice_id' => $this->invoice->id,
            'message' => $exception?->getMessage(),
        ]);
    }
}
