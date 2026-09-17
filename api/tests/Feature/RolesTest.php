<?php

declare(strict_types=1);

use App\Actions\IssueInvoice;
use App\Enums\Role;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;

it('assigns viewer as the default role for a user without one', function () {
    $user = User::factory()->create();

    expect($user->role())->toBe(Role::Viewer)
        ->and($user->canWrite())->toBeFalse();
});

it('lets a viewer read but not write', function () {
    $company = Company::factory()->create();
    $viewer = User::factory()->viewer()->create();

    test_case()->actingAs($viewer)
        ->getJson('/api/companies')
        ->assertOk();

    test_case()->actingAs($viewer)
        ->postJson('/api/companies', [
            'name' => 'Νέα ΑΕ',
            'vat_number' => '123456789',
        ])
        ->assertStatus(403);
});

it('blocks a viewer from issuing an invoice', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();

    $viewer = User::factory()->viewer()->create();

    test_case()->actingAs($viewer)
        ->postJson("/api/invoices/{$invoice->id}/issue")
        ->assertStatus(403);

    expect($invoice->fresh()?->number)->toBeNull();
});

it('lets an accountant issue an invoice', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();

    $accountant = User::factory()->accountant()->create();

    test_case()->actingAs($accountant)
        ->postJson("/api/invoices/{$invoice->id}/issue")
        ->assertOk();
});

it('blocks an accountant from managing mydata credentials', function () {
    $company = Company::factory()->create();
    $accountant = User::factory()->accountant()->create();

    test_case()->actingAs($accountant)
        ->putJson("/api/companies/{$company->id}/credentials", [
            'mydata_user_id' => 'testuser',
            'mydata_subscription_key' => 'testkey',
        ])
        ->assertStatus(403);

    expect($company->fresh()?->hasMydataCredentials())->toBeFalse();
});

it('lets an admin manage mydata credentials', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->admin()->create();

    test_case()->actingAs($admin)
        ->putJson("/api/companies/{$company->id}/credentials", [
            'mydata_user_id' => 'testuser',
            'mydata_subscription_key' => 'testkey',
        ])
        ->assertOk();

    expect($company->fresh()?->hasMydataCredentials())->toBeTrue();
});

it('lets a viewer download a pdf', function () {
    $company = Company::factory()->create();
    $invoice = Invoice::factory()->forCompany($company)->withLine()->create();
    (new IssueInvoice)->handle($invoice);

    $viewer = User::factory()->viewer()->create();

    test_case()->actingAs($viewer)
        ->get("/api/invoices/{$invoice->id}/pdf")
        ->assertOk();
});
