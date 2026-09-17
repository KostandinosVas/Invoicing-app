<?php

declare(strict_types=1);

use App\Actions\IssueInvoice;
use App\Enums\IncomeClassification;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Series;
use App\Models\User;

/**
 * @return array{Company, Invoice, Series, Customer}
 */
function invoiceWithCreditSeries(): array
{
    $company = Company::factory()->create();

    $customer = Customer::factory()->create([
        'company_id' => $company->id,
        'name' => 'Αρχική Επωνυμία',
    ]);

    $invoiceSeries = Series::factory()->create([
        'company_id' => $company->id,
        'code' => 'ΤΙΜ',
        'document_type' => 'invoice',
    ]);

    $creditSeries = Series::factory()->create([
        'company_id' => $company->id,
        'code' => 'ΠΙΣ',
        'document_type' => 'credit_note',
    ]);

    $invoice = Invoice::factory()->forCompany($company)->withLine()->create([
        'customer_id' => $customer->id,
        'series_id' => $invoiceSeries->id,
        'customer_name' => 'Αρχική Επωνυμία',
    ]);

    (new IssueInvoice)->handle($invoice);

    return [$company, $invoice, $creditSeries, $customer];
}

/**
 * @return array<string, mixed>
 */
function creditNotePayload(int $seriesId): array
{
    return [
        'series_id' => $seriesId,
        'issue_date' => '2026-09-14',
        'lines' => [
            [
                'description' => 'Επιστροφή εμπορευμάτων',
                'quantity' => 1,
                'unit_price_cents' => 31000,
                'vat_rate' => 24,
                'income_classification' => IncomeClassification::GoodsSale->value,
            ],
        ],
    ];
}

it('creates a credit note linked to the original', function () {
    [, $invoice, $creditSeries] = invoiceWithCreditSeries();
    $user = User::factory()->admin()->create();

    $response = test_case()->actingAs($user)
        ->postJson("/api/invoices/{$invoice->id}/credit-notes", creditNotePayload($creditSeries->id))
        ->assertStatus(201)
        ->assertJsonPath('data.document_type', 'credit_note')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.totals.total_cents', 38440);

    /** @var Invoice $creditNote */
    $creditNote = Invoice::query()->findOrFail($response->json('data.id'));

    expect($creditNote->related_invoice_id)->toBe($invoice->id);
});

it('copies the snapshot from the original, not from the customer', function () {
    [, $invoice, $creditSeries, $customer] = invoiceWithCreditSeries();
    $user = User::factory()->admin()->create();

    // Ο πελάτης αλλάζει επωνυμία μετά την έκδοση.
    $customer->update(['name' => 'Νέα Επωνυμία']);

    $response = test_case()->actingAs($user)
        ->postJson("/api/invoices/{$invoice->id}/credit-notes", creditNotePayload($creditSeries->id))
        ->assertStatus(201);

    // Το πιστωτικό δείχνει ό,τι και το τιμολόγιο που διορθώνει.
    expect($response->json('data.customer.name'))->toBe('Αρχική Επωνυμία');
});

it('rejects a credit note for a draft invoice', function () {
    $company = Company::factory()->create();

    $creditSeries = Series::factory()->create([
        'company_id' => $company->id,
        'document_type' => 'credit_note',
    ]);

    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();

    $user = User::factory()->admin()->create();

    test_case()->actingAs($user)
        ->postJson("/api/invoices/{$invoice->id}/credit-notes", creditNotePayload($creditSeries->id))
        ->assertStatus(403);
});

it('rejects a series of the wrong document type', function () {
    [$company, $invoice] = invoiceWithCreditSeries();

    $wrongSeries = Series::factory()->create([
        'company_id' => $company->id,
        'code' => 'ΑΛΛΗ',
        'document_type' => 'invoice',
    ]);

    $user = User::factory()->admin()->create();

    test_case()->actingAs($user)
        ->postJson("/api/invoices/{$invoice->id}/credit-notes", creditNotePayload($wrongSeries->id))
        ->assertStatus(422)
        ->assertJsonValidationErrors('series_id');
});

it('issues a credit note from its own series', function () {
    [, $invoice, $creditSeries] = invoiceWithCreditSeries();
    $user = User::factory()->admin()->create();

    $creditNoteId = test_case()->actingAs($user)
        ->postJson("/api/invoices/{$invoice->id}/credit-notes", creditNotePayload($creditSeries->id))
        ->json('data.id');

    test_case()->actingAs($user)
        ->postJson("/api/invoices/{$creditNoteId}/issue")
        ->assertOk()
        ->assertJsonPath('data.status', 'issued')
        ->assertJsonPath('data.number', 1);

    // Η αρίθμηση του τιμολογίου δεν επηρεάστηκε.
    expect($invoice->fresh()?->number)->toBe(1)
        ->and($creditSeries->fresh()?->last_number)->toBe(1);
});
