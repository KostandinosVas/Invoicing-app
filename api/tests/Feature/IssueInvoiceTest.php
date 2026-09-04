<?php

declare(strict_types=1);

use App\Actions\IssueInvoice;
use App\Enums\InvoiceStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Series;

it('assigns the next number in the series', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();

    (new IssueInvoice)->handle($invoice);

    expect($invoice->number)->toBe(1)
        ->and($invoice->status)->toBe(InvoiceStatus::Issued);
});

it('increments the series counter', function () {
    $company = Company::factory()->create();
    $series = Series::factory()->create(['company_id' => $company->id]);

    $first = Invoice::factory()->forCompany($company)->withLine()->create(['series_id' => $series->id]);
    $second = Invoice::factory()->forCompany($company)->withLine()->create(['series_id' => $series->id]);

    (new IssueInvoice)->handle($first);
    (new IssueInvoice)->handle($second);

    expect($first->number)->toBe(1)
        ->and($second->number)->toBe(2)
        ->and($series->fresh()?->last_number)->toBe(2);
});

it('does not consume a number when the transition is invalid', function () {
    $company = Company::factory()->create();
    $series = Series::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create(['series_id' => $series->id]);

    (new IssueInvoice)->handle($invoice);

    // Δεύτερη έκδοση του ίδιου παραστατικού
    expect(fn () => (new IssueInvoice)->handle($invoice))
        ->toThrow(InvalidStatusTransition::class);

    expect($series->fresh()?->last_number)->toBe(1);
});

it('keeps separate numbering per series', function () {
    $company = Company::factory()->create();

    $seriesA = Series::factory()->create(['company_id' => $company->id]);
    $seriesB = Series::factory()->create(['company_id' => $company->id]);

    $a = Invoice::factory()->forCompany($company)->withLine()->create(['series_id' => $seriesA->id]);
    $b = Invoice::factory()->forCompany($company)->withLine()->create(['series_id' => $seriesB->id]);

    (new IssueInvoice)->handle($a);
    (new IssueInvoice)->handle($b);

    expect($a->number)->toBe(1)
        ->and($b->number)->toBe(1);
});

it('does not issue duplicate numbers under concurrency', function () {
    $company = Company::factory()->create();
    $series = Series::factory()->create(['company_id' => $company->id]);

    $invoices = collect(range(1, 20))->map(
        fn (): Invoice => Invoice::factory()->forCompany($company)->withLine()->create(['series_id' => $series->id])
    );

    $action = new IssueInvoice;

    foreach ($invoices as $invoice) {
        $action->handle($invoice);
    }

    $numbers = Invoice::query()
        ->where('series_id', $series->id)
        ->pluck('number')
        ->sort()
        ->values()
        ->all();

    expect($numbers)->toBe(range(1, 20))
        ->and($series->fresh()?->last_number)->toBe(20);
});

it('refuses to issue an invoice without lines', function () {
    $company = Company::factory()->create();
    $series = Series::factory()->create(['company_id' => $company->id]);
    $invoice = Invoice::factory()->forCompany($company)->create(['series_id' => $series->id]);

    expect(fn () => (new IssueInvoice)->handle($invoice))
        ->toThrow(DomainException::class);

    expect($series->fresh()?->last_number)->toBe(0)
        ->and($invoice->fresh()?->number)->toBeNull();
});
