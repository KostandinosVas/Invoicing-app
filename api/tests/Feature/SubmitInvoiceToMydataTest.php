<?php

declare(strict_types=1);

use App\Actions\IssueInvoice;
use App\Enums\InvoiceStatus;
use App\Enums\SubmissionStatus;
use App\Jobs\SubmitInvoiceToMydata;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Submission;
use App\Services\Mydata\MydataClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function pendingSubmission(): Submission
{
    $company = Company::factory()->create();
    $company->mydata_user_id = 'testuser';
    $company->mydata_subscription_key = 'testkey';
    $company->save();

    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();
    (new IssueInvoice)->handle($invoice);

    $invoice->transitionTo(InvoiceStatus::Submitting);
    $invoice->save();

    return Submission::create([
        'invoice_id' => $invoice->id,
        'idempotency_key' => (string) Str::uuid(),
        'status' => SubmissionStatus::Pending,
        'attempt' => 1,
        'request_payload' => '<InvoicesDoc/>',
    ]);
}

it('records the mark when aade accepts', function () {
    Http::fake([
        '*/SendInvoices' => Http::response(
            '<ResponseDoc><response><invoiceUid>UID1</invoiceUid><invoiceMark>400001912345678</invoiceMark><authenticationCode>AUTH1</authenticationCode><statusCode>Success</statusCode></response></ResponseDoc>',
            200,
        ),
    ]);

    $submission = pendingSubmission();

    (new SubmitInvoiceToMydata($submission))->handle(app(MydataClient::class));

    $submission->refresh();
    $invoice = $submission->invoice;

    expect($submission->status)->toBe(SubmissionStatus::Accepted)
        ->and($submission->mydata_mark)->toBe('400001912345678')
        ->and($submission->completed_at)->not->toBeNull()
        ->and($invoice?->mydata_mark)->toBe('400001912345678')
        ->and($invoice?->status)->toBe(InvoiceStatus::Submitted);
});

it('records the errors when aade rejects', function () {
    Http::fake([
        '*/SendInvoices' => Http::response(
            '<ResponseDoc><response><errors><error><message>Invalid VAT</message><code>202</code></error></errors><statusCode>ValidationError</statusCode></response></ResponseDoc>',
            200,
        ),
    ]);

    $submission = pendingSubmission();

    (new SubmitInvoiceToMydata($submission))->handle(app(MydataClient::class));

    $submission->refresh();
    $invoice = $submission->invoice;

    expect($submission->status)->toBe(SubmissionStatus::Rejected)
        ->and($submission->errors)->toHaveCount(1)
        ->and($submission->mydata_mark)->toBeNull()
        ->and($invoice?->status)->toBe(InvoiceStatus::Rejected)
        ->and($invoice?->mydata_mark)->toBeNull();
});

it('does nothing when the submission is already final', function () {
    Http::fake();

    $submission = pendingSubmission();
    $submission->status = SubmissionStatus::Accepted;
    $submission->save();

    (new SubmitInvoiceToMydata($submission))->handle(app(MydataClient::class));

    Http::assertNothingSent();
});

it('marks the submission as failed on a technical error', function () {
    $submission = pendingSubmission();

    $job = new SubmitInvoiceToMydata($submission);
    $job->failed(new RuntimeException('Connection timed out'));

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::Failed)
        ->and($submission->errors)->toHaveCount(1)
        ->and(($submission->errors ?? [])[0] ?? '')->toContain('Connection timed out');
});

it('uses exponential backoff', function () {
    $submission = pendingSubmission();

    expect((new SubmitInvoiceToMydata($submission))->backoff())
        ->toBe([30, 120, 600, 1800]);
});
