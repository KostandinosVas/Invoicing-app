<?php

declare(strict_types=1);

use App\Actions\IssueInvoice;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Series;
use App\Models\User;

it('creates an invoice with lines', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Πελάτης ΑΕ']);
    $series = Series::factory()->create(['company_id' => $company->id]);
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson('/api/invoices', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'series_id' => $series->id,
            'document_type' => 'invoice',
            'issue_date' => '2026-09-05',
            'lines' => [
                [
                    'description' => 'Συμβουλευτικές υπηρεσίες',
                    'quantity' => 10,
                    'unit_price_cents' => 5000,
                    'vat_rate' => 24,
                ],
            ],
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.number', null)
        ->assertJsonPath('data.customer.name', 'Πελάτης ΑΕ')
        ->assertJsonPath('data.totals.net_amount_cents', 50000)
        ->assertJsonPath('data.totals.vat_amount_cents', 12000)
        ->assertJsonPath('data.totals.total_cents', 62000)
        ->assertJsonCount(1, 'data.lines');
});

it('rejects an invoice without lines', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $series = Series::factory()->create(['company_id' => $company->id]);
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson('/api/invoices', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'series_id' => $series->id,
            'document_type' => 'invoice',
            'issue_date' => '2026-09-05',
            'lines' => [],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('lines');
});

it('rejects a customer from another company', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $foreignCustomer = Customer::factory()->create(['company_id' => $companyB->id]);
    $series = Series::factory()->create(['company_id' => $companyA->id]);
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson('/api/invoices', [
            'company_id' => $companyA->id,
            'customer_id' => $foreignCustomer->id,
            'series_id' => $series->id,
            'document_type' => 'invoice',
            'issue_date' => '2026-09-05',
            'lines' => [
                ['description' => 'Κάτι', 'quantity' => 1, 'unit_price_cents' => 1000, 'vat_rate' => 24],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('customer_id');
});

it('inherits line data from the item', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $series = Series::factory()->create(['company_id' => $company->id]);
    $item = Item::factory()->create([
        'company_id' => $company->id,
        'name' => 'Έντυπο υλικό',
        'unit_price_cents' => 10000,
        'vat_rate' => 6,
    ]);
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson('/api/invoices', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'series_id' => $series->id,
            'document_type' => 'invoice',
            'issue_date' => '2026-09-05',
            'lines' => [
                ['item_id' => $item->id, 'quantity' => 1],
            ],
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.lines.0.description', 'Έντυπο υλικό')
        ->assertJsonPath('data.lines.0.unit_price_cents', 10000)
        ->assertJsonPath('data.lines.0.vat_rate', 6);
});

it('issues an invoice', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $series = Series::factory()->create(['company_id' => $company->id]);
    $user = User::factory()->create();

    $created = test_case()->actingAs($user)
        ->postJson('/api/invoices', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'series_id' => $series->id,
            'document_type' => 'invoice',
            'issue_date' => '2026-09-05',
            'lines' => [
                ['description' => 'Κάτι', 'quantity' => 1, 'unit_price_cents' => 10000, 'vat_rate' => 24],
            ],
        ])->json('data.id');

    test_case()->actingAs($user)
        ->postJson("/api/invoices/{$created}/issue")
        ->assertOk()
        ->assertJsonPath('data.status', 'issued')
        ->assertJsonPath('data.number', 1);
});

it('refuses to issue an already issued invoice', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $invoice = Invoice::factory()
        ->forCompany($company)
        ->withLine()
        ->create();

    (new IssueInvoice)->handle($invoice);

    test_case()->actingAs($user)
        ->postJson("/api/invoices/{$invoice->id}/issue")
        ->assertStatus(403);
});

it('does not expose lines in the index listing', function () {
    $company = Company::factory()->create();
    Invoice::factory()->forCompany($company)->withLine()->create();
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->getJson('/api/invoices')
        ->assertOk()
        ->assertJsonMissingPath('data.0.lines');
});
