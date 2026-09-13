<?php

declare(strict_types=1);

use App\Actions\IssueInvoice;
use App\Actions\SubmitInvoice;
use App\Enums\InvoiceStatus;
use App\Enums\SubmissionStatus;
use App\Jobs\SubmitInvoiceToMydata;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Support\Facades\Queue;

function issuedInvoice(): Invoice
{
    $company = Company::factory()->create();

    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();

    (new IssueInvoice)->handle($invoice);

    return $invoice;
}

it('creates a submission and dispatches the job', function () {
    Queue::fake();

    $invoice = issuedInvoice();

    $submission = app(SubmitInvoice::class)->handle($invoice);

    expect($submission->status)->toBe(SubmissionStatus::Pending)
        ->and($submission->attempt)->toBe(1)
        ->and($submission->idempotency_key)->not->toBeEmpty()
        ->and($submission->request_payload)->toContain('InvoicesDoc')
        ->and($invoice->fresh()?->status)->toBe(InvoiceStatus::Submitting);

    Queue::assertPushed(SubmitInvoiceToMydata::class);
});

it('refuses a second submission while one is in flight', function () {
    Queue::fake();

    $invoice = issuedInvoice();

    app(SubmitInvoice::class)->handle($invoice);

    expect(fn () => app(SubmitInvoice::class)->handle($invoice))
        ->toThrow(DomainException::class);

    Queue::assertPushed(SubmitInvoiceToMydata::class, 1);
});

it('refuses to submit an invoice that already has a mark', function () {
    Queue::fake();

    $invoice = issuedInvoice();
    $invoice->mydata_mark = '400001912345678';
    $invoice->save();

    expect(fn () => app(SubmitInvoice::class)->handle($invoice))
        ->toThrow(DomainException::class);

    Queue::assertNothingPushed();
});

it('stores the xml payload for diagnostics', function () {
    Queue::fake();

    $invoice = issuedInvoice();

    $submission = app(SubmitInvoice::class)->handle($invoice);

    expect($submission->request_payload)
        ->toContain('<invoiceHeader>')
        ->toContain('<totalGrossValue>');
});
