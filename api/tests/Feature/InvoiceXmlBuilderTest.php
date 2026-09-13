<?php

declare(strict_types=1);

use App\Enums\IncomeClassification;
use App\Enums\MydataInvoiceType;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Series;
use App\Services\Mydata\InvoiceXmlBuilder;

function buildIssuedInvoice(): Invoice
{
    $company = Company::factory()->create(['vat_number' => '999999999']);

    $customer = Customer::factory()->create([
        'company_id' => $company->id,
        'vat_number' => '888888888',
        'postal_code' => '11526',
        'city' => 'ΑΘΗΝΑ',
    ]);

    $series = Series::factory()->create(['company_id' => $company->id, 'code' => 'A']);

    $invoice = Invoice::factory()->forCompany($company)->create([
        'customer_id' => $customer->id,
        'series_id' => $series->id,
        'customer_vat_number' => '888888888',
        'customer_postal_code' => '11526',
        'customer_city' => 'ΑΘΗΝΑ',
        'issue_date' => '2026-09-13',
    ]);

    $line = InvoiceLine::factory()->make([
        'invoice_id' => $invoice->id,
        'position' => 1,
        'quantity' => '10.000',
        'unit_price_cents' => 5000,
        'vat_rate' => 24,
        'income_classification' => IncomeClassification::ServicesProvision,
    ]);
    $line->calculateTotals();
    $line->save();

    $invoice->recalculateTotals();
    $invoice->number = 101;
    $invoice->mydata_invoice_type = MydataInvoiceType::ServicesInvoice;
    $invoice->save();

    /** @var Invoice $refreshed */
    $refreshed = $invoice->fresh(['lines', 'company', 'series']);

    return $refreshed;
}

it('produces xml that validates against the official xsd', function () {
    $invoice = buildIssuedInvoice();

    $xml = (new InvoiceXmlBuilder)->build($invoice);

    $doc = new DOMDocument;
    $doc->loadXML($xml);

    libxml_use_internal_errors(true);

    $isValid = $doc->schemaValidate(
        resource_path('mydata/xsd/InvoicesDoc-v2.0.2.xsd')
    );

    $errors = array_map(
        fn (LibXMLError $e): string => trim($e->message),
        libxml_get_errors(),
    );

    libxml_clear_errors();

    expect($isValid)->toBeTrue(implode("\n", $errors));
});

it('groups classifications in the summary', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $series = Series::factory()->create(['company_id' => $company->id]);

    $invoice = Invoice::factory()->forCompany($company)->create([
        'customer_id' => $customer->id,
        'series_id' => $series->id,
    ]);

    // Δύο γραμμές υπηρεσιών, μία εμπορευμάτων.
    foreach ([
        [IncomeClassification::ServicesProvision, 5000],
        [IncomeClassification::ServicesProvision, 3000],
        [IncomeClassification::GoodsSale, 10000],
    ] as $index => [$classification, $price]) {
        $line = InvoiceLine::factory()->make([
            'invoice_id' => $invoice->id,
            'position' => $index + 1,
            'quantity' => '1.000',
            'unit_price_cents' => $price,
            'vat_rate' => 24,
            'income_classification' => $classification,
        ]);
        $line->calculateTotals();
        $line->save();
    }

    $invoice->recalculateTotals();
    $invoice->number = 1;
    $invoice->mydata_invoice_type = MydataInvoiceType::SalesInvoice;
    $invoice->save();

    /** @var Invoice $refreshed */
    $refreshed = $invoice->fresh(['lines', 'company', 'series']);

    $xml = (new InvoiceXmlBuilder)->build($refreshed);

    // Τρεις γραμμές, αλλά δύο χαρακτηρισμοί στη σύνοψη.
    $doc = new DOMDocument;
    $doc->loadXML($xml);

    $summary = $doc->getElementsByTagName('invoiceSummary')->item(0);

    expect($summary?->getElementsByTagName('incomeClassification')->count())->toBe(2);
});
