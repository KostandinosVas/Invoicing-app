<?php

declare(strict_types=1);

use App\Actions\IssueInvoice;
use App\Actions\SendInvoiceEmail;
use App\Mail\InvoiceMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('queues the invoice email', function () {
    Mail::fake();

    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();
    (new IssueInvoice)->handle($invoice);

    app(SendInvoiceEmail::class)->handle($invoice, 'pelatis@example.gr');

    Mail::assertQueued(
        InvoiceMail::class,
        fn (InvoiceMail $mail): bool => $mail->hasTo('pelatis@example.gr'),
    );
});

it('refuses to email a draft', function () {
    Mail::fake();

    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();

    expect(fn () => app(SendInvoiceEmail::class)->handle($invoice, 'pelatis@example.gr'))
        ->toThrow(DomainException::class);

    Mail::assertNothingQueued();
});

it('attaches the pdf', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();
    (new IssueInvoice)->handle($invoice);

    $mail = new InvoiceMail($invoice);
    $attachments = $mail->attachments();

    expect($attachments)->toHaveCount(1);
});

it('sends through the api', function () {
    Mail::fake();

    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();
    (new IssueInvoice)->handle($invoice);

    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson("/api/invoices/{$invoice->id}/email", ['email' => 'pelatis@example.gr'])
        ->assertOk();

    Mail::assertQueued(InvoiceMail::class);
});

it('rejects an invalid email address', function () {
    Mail::fake();

    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();
    (new IssueInvoice)->handle($invoice);

    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson("/api/invoices/{$invoice->id}/email", ['email' => 'not-an-email'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');

    Mail::assertNothingQueued();
});

it('refuses to email a draft through the api', function () {
    Mail::fake();

    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();

    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson("/api/invoices/{$invoice->id}/email", ['email' => 'pelatis@example.gr'])
        ->assertStatus(403);

    Mail::assertNothingQueued();
});
