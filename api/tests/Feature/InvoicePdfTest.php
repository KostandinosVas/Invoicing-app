<?php

declare(strict_types=1);

use App\Actions\IssueInvoice;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoicePdf;

it('renders a pdf for an issued invoice', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();
    (new IssueInvoice)->handle($invoice);

    $output = app(InvoicePdf::class)->render($invoice)->output();

    expect($output)->toStartWith('%PDF-')
        ->and(strlen($output))->toBeGreaterThan(100000);
});

it('embeds a font that supports greek', function () {
    $company = Company::factory()->create(['name' => 'Ελληνική Εταιρεία ΑΕ']);
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();
    (new IssueInvoice)->handle($invoice);

    $output = app(InvoicePdf::class)->render($invoice)->output();

    // Χωρίς ενσωματωμένη γραμματοσειρά το dompdf πέφτει σε Helvetica,
    // που δεν έχει ελληνικούς χαρακτήρες.
    expect($output)->not->toContain('/BaseFont /Helvetica');
});

it('names the file after the series and number', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();
    (new IssueInvoice)->handle($invoice);

    expect(app(InvoicePdf::class)->filename($invoice))->toEndWith('-1.pdf');
});

it('marks a draft filename as draft', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();

    expect(app(InvoicePdf::class)->filename($invoice))->toStartWith('draft-');
});

it('serves the pdf through the api', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();
    (new IssueInvoice)->handle($invoice);

    $user = User::factory()->admin()->create();

    test_case()->actingAs($user)
        ->get("/api/invoices/{$invoice->id}/pdf")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
