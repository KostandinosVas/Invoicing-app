<?php

declare(strict_types=1);

use App\Actions\IssueInvoice;
use App\Enums\InvoiceStatus;
use App\Jobs\CancelInvoiceAtMydata;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Mydata\MydataClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/**
 * @return array{Company, Invoice}
 */
function submittedInvoice(): array
{
    $company = Company::factory()->create();
    $company->mydata_user_id = 'testuser';
    $company->mydata_subscription_key = 'testkey';
    $company->save();

    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();
    (new IssueInvoice)->handle($invoice);

    $invoice->transitionTo(InvoiceStatus::Submitting);
    $invoice->transitionTo(InvoiceStatus::Submitted);
    $invoice->mydata_mark = '400001912345678';
    $invoice->save();

    return [$company, $invoice];
}

it('dispatches a cancellation job', function () {
    Queue::fake();

    [, $invoice] = submittedInvoice();
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson("/api/invoices/{$invoice->id}/cancel")
        ->assertOk();

    Queue::assertPushed(CancelInvoiceAtMydata::class);
});

it('refuses to cancel an invoice without a mark', function () {
    Queue::fake();

    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();
    (new IssueInvoice)->handle($invoice);

    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson("/api/invoices/{$invoice->id}/cancel")
        ->assertStatus(403);

    Queue::assertNothingPushed();
});

it('records the cancellation mark when aade accepts', function () {
    Http::fake([
        '*/CancelInvoice*' => Http::response(
            '<ResponseDoc><response><cancellationMark>400009988776655</cancellationMark><statusCode>Success</statusCode></response></ResponseDoc>',
            200,
        ),
    ]);

    [, $invoice] = submittedInvoice();

    (new CancelInvoiceAtMydata($invoice))->handle(app(MydataClient::class));

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Cancelled)
        ->and($invoice->mydata_cancellation_mark)->toBe('400009988776655')
        ->and($invoice->cancelled_at)->not->toBeNull();
});

it('does nothing when the invoice is already cancelled', function () {
    Http::fake();

    [, $invoice] = submittedInvoice();
    $invoice->transitionTo(InvoiceStatus::Cancelled);
    $invoice->save();

    (new CancelInvoiceAtMydata($invoice))->handle(app(MydataClient::class));

    Http::assertNothingSent();
});

it('leaves the invoice untouched when aade rejects the cancellation', function () {
    Http::fake([
        '*/CancelInvoice*' => Http::response(
            '<ResponseDoc><response><errors><error><message>Already cancelled</message><code>301</code></error></errors><statusCode>ValidationError</statusCode></response></ResponseDoc>',
            200,
        ),
    ]);

    [, $invoice] = submittedInvoice();

    (new CancelInvoiceAtMydata($invoice))->handle(app(MydataClient::class));

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Submitted)
        ->and($invoice->mydata_cancellation_mark)->toBeNull();
});

it('sends the mark as a query parameter', function () {
    Http::fake([
        '*/CancelInvoice*' => Http::response(
            '<ResponseDoc><response><cancellationMark>1</cancellationMark><statusCode>Success</statusCode></response></ResponseDoc>',
            200,
        ),
    ]);

    [$company] = submittedInvoice();

    (new MydataClient)->cancelInvoice($company, '400001912345678');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'mark=400001912345678'));
});
