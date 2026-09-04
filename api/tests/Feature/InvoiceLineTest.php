<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceLine;

it('calculates line totals with vat', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->create();

    $line = InvoiceLine::factory()->make([
        'invoice_id' => $invoice->id,
        'quantity' => '10.000',
        'unit_price_cents' => 5000,   // 50,00 €
        'vat_rate' => 24,
    ]);

    $line->calculateTotals();

    expect($line->net_amount_cents)->toBe(50000)   // 500,00 €
        ->and($line->vat_amount_cents)->toBe(12000) // 120,00 €
        ->and($line->total_cents)->toBe(62000);     // 620,00 €
});

it('handles fractional quantities', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->create();

    $line = InvoiceLine::factory()->make([
        'invoice_id' => $invoice->id,
        'quantity' => '2.500',
        'unit_price_cents' => 1333,   // 13,33 €
        'vat_rate' => 24,
    ]);

    $line->calculateTotals();

    // 1333 × 2.5 = 3332,5 → 3333 (half up)
    expect($line->net_amount_cents)->toBe(3333);
});

it('handles zero vat rate', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->create();

    $line = InvoiceLine::factory()->make([
        'invoice_id' => $invoice->id,
        'quantity' => '1.000',
        'unit_price_cents' => 10000,
        'vat_rate' => 0,
    ]);

    $line->calculateTotals();

    expect($line->vat_amount_cents)->toBe(0)
        ->and($line->total_cents)->toBe(10000);
});

it('sums line totals into the invoice', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->create();

    // Γραμμή 1: 10 × 50,00 € με ΦΠΑ 24%
    $first = InvoiceLine::factory()->make([
        'invoice_id' => $invoice->id,
        'position' => 1,
        'quantity' => '10.000',
        'unit_price_cents' => 5000,
        'vat_rate' => 24,
    ]);
    $first->calculateTotals();
    $first->save();

    // Γραμμή 2: 1 × 100,00 € με ΦΠΑ 6%
    $second = InvoiceLine::factory()->make([
        'invoice_id' => $invoice->id,
        'position' => 2,
        'quantity' => '1.000',
        'unit_price_cents' => 10000,
        'vat_rate' => 6,
    ]);
    $second->calculateTotals();
    $second->save();

    $invoice->recalculateTotals();

    expect($invoice->net_amount_cents)->toBe(60000)   // 500 + 100
        ->and($invoice->vat_amount_cents)->toBe(12600) // 120 + 6
        ->and($invoice->total_cents)->toBe(72600);
});
