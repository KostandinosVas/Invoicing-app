<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Enums\SubmissionStatus;
use App\Exceptions\MydataNotConfigured;
use App\Models\Invoice;
use App\Models\Submission;
use App\Services\Mydata\MydataClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SubmitInvoiceToMydata implements ShouldQueue
{
    use Queueable;

    /** Μέγιστες απόπειρες πριν το job θεωρηθεί νεκρό. */
    public int $tries = 5;

    /** Χρόνος ζωής του job συνολικά (δευτερόλεπτα). */
    public int $timeout = 60;

    public function __construct(
        public readonly Submission $submission,
    ) {}

    /**
     * Exponential backoff: 30s, 2m, 10m, 30m.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(MydataClient $client): void
    {
        $submission = $this->submission->fresh();

        if ($submission === null || $submission->status->isFinal()) {
            return;
        }

        $invoice = $submission->invoice;

        if ($invoice === null) {
            return;
        }

        $company = $invoice->company;

        if ($company === null) {
            return;
        }

        $submission->status = SubmissionStatus::Sent;
        $submission->sent_at = now();
        $submission->save();

        try {
            $response = $client->sendInvoice($company, $submission->request_payload);
        } catch (MydataNotConfigured $exception) {
            // Μόνιμη συνθήκη: το retry δεν θα τη λύσει.
            $this->markPermanentFailure($submission, $invoice, $exception->getMessage());
            $this->fail($exception);

            return;
        }

        if (! $response->accepted) {
            $submission->status = SubmissionStatus::Rejected;
            $submission->errors = $response->errors;
            $submission->completed_at = now();
            $submission->save();

            $invoice->transitionTo(InvoiceStatus::Rejected);
            $invoice->save();

            return;
        }

        $submission->status = SubmissionStatus::Accepted;
        $submission->mydata_mark = $response->mark;
        $submission->mydata_uid = $response->uid;
        $submission->mydata_authentication_code = $response->authenticationCode;
        $submission->completed_at = now();
        $submission->save();

        $invoice->mydata_mark = $response->mark;
        $invoice->mydata_submitted_at = now();
        $invoice->transitionTo(InvoiceStatus::Submitted);
        $invoice->save();
    }

    public function failed(?Throwable $exception): void
    {
        $submission = $this->submission->fresh();

        if ($submission === null || $submission->status->isFinal()) {
            return;
        }

        $submission->status = SubmissionStatus::Failed;
        $submission->errors = [$exception?->getMessage() ?? 'Άγνωστο σφάλμα'];
        $submission->completed_at = now();
        $submission->save();

        $invoice = $submission->invoice;

        // Το παραστατικό δεν πρέπει να μείνει κολλημένο σε submitting.
        if ($invoice !== null && $invoice->status === InvoiceStatus::Submitting) {
            $invoice->transitionTo(InvoiceStatus::Rejected);
            $invoice->save();
        }

        Log::error('Αποτυχία διαβίβασης myDATA', [
            'submission_id' => $submission->id,
            'invoice_id' => $submission->invoice_id,
            'attempt' => $submission->attempt,
        ]);
    }

    private function markPermanentFailure(
        Submission $submission,
        Invoice $invoice,
        string $message,
    ): void {
        $submission->status = SubmissionStatus::Failed;
        $submission->errors = [$message];
        $submission->completed_at = now();
        $submission->save();

        $invoice->transitionTo(InvoiceStatus::Rejected);
        $invoice->save();
    }
}
