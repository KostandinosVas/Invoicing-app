<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Database\QueryException;

it('starts as draft with no number', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->create();

    expect($invoice->status)->toBe(InvoiceStatus::Draft)
        ->and($invoice->number)->toBeNull();
});

it('allows a valid transition', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->create();

    $invoice->transitionTo(InvoiceStatus::Issued);

    expect($invoice->status)->toBe(InvoiceStatus::Issued);
});

it('rejects an invalid transition', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->create();

    expect(fn () => $invoice->transitionTo(InvoiceStatus::Submitted))
        ->toThrow(InvalidStatusTransition::class);
});

it('does not allow two invoices with the same number in a series', function () {
    $company = Company::factory()->create();

    $first = Invoice::factory()->forCompany($company)->create();
    $first->forceFill(['number' => 1])->save();

    $second = Invoice::factory()->forCompany($company)->create([
        'series_id' => $first->series_id,
    ]);

    expect(fn () => $second->forceFill(['number' => 1])->save())
        ->toThrow(QueryException::class);
});

it('keeps customer snapshot when the customer changes', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->create([
        'customer_name' => 'Αρχική Επωνυμία',
    ]);

    $invoice->customer->update(['name' => 'Νέα Επωνυμία']);
    $invoice->refresh();

    expect($invoice->customer_name)->toBe('Αρχική Επωνυμία')
        ->and($invoice->customer->name)->toBe('Νέα Επωνυμία');
});
