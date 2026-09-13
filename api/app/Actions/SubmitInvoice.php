<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Enums\SubmissionStatus;
use App\Jobs\SubmitInvoiceToMydata;
use App\Models\Invoice;
use App\Models\Submission;
use App\Services\Mydata\InvoiceXmlBuilder;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SubmitInvoice
{
    public function __construct(
        private readonly InvoiceXmlBuilder $xmlBuilder,
    ) {}

    public function handle(Invoice $invoice): Submission
    {
        return DB::transaction(function () use ($invoice): Submission {
            $this->guardAgainstDuplicate($invoice);

            $invoice->transitionTo(InvoiceStatus::Submitting);
            $invoice->save();

            $submission = Submission::create([
                'invoice_id' => $invoice->id,
                'idempotency_key' => (string) Str::uuid(),
                'status' => SubmissionStatus::Pending,
                'attempt' => $this->nextAttempt($invoice),
                'request_payload' => $this->xmlBuilder->build($invoice),
            ]);

            SubmitInvoiceToMydata::dispatch($submission);

            return $submission;
        });
    }

    private function guardAgainstDuplicate(Invoice $invoice): void
    {
        if ($invoice->mydata_mark !== null) {
            throw new DomainException(
                'Το παραστατικό έχει ήδη διαβιβαστεί και έχει ΜΑΡΚ.'
            );
        }

        $inFlight = $invoice->submissions()
            ->whereIn('status', [SubmissionStatus::Pending, SubmissionStatus::Sent])
            ->exists();

        if ($inFlight) {
            throw new DomainException(
                'Υπάρχει απόπειρα διαβίβασης σε εξέλιξη για το παραστατικό.'
            );
        }
    }

    private function nextAttempt(Invoice $invoice): int
    {
        return (int) $invoice->submissions()->max('attempt') + 1;
    }
}
