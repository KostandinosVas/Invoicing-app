<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Item;
use App\Models\Series;
use App\Models\User;

it('creates an item', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson('/api/items', [
            'company_id' => $company->id,
            'name' => 'Συμβουλευτικές υπηρεσίες',
            'unit_price_cents' => 5000,
            'vat_rate' => 24,
            'income_classification' => 'services_provision',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.unit_price_cents', 5000);
});

it('rejects an invalid vat rate', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson('/api/items', [
            'company_id' => $company->id,
            'name' => 'Κάτι',
            'unit_price_cents' => 5000,
            'vat_rate' => 99,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('vat_rate');
});

it('filters items by active status', function () {
    $company = Company::factory()->create();
    Item::factory()->create(['company_id' => $company->id, 'is_active' => true]);
    Item::factory()->create(['company_id' => $company->id, 'is_active' => false]);

    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->getJson('/api/items?active_only=1')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('creates a series', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson('/api/series', [
            'company_id' => $company->id,
            'code' => 'ΤΙΜ',
            'document_type' => 'invoice',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.last_number', 0);
});

it('rejects a duplicate series code within a company', function () {
    $company = Company::factory()->create();
    Series::factory()->create(['company_id' => $company->id, 'code' => 'ΤΙΜ']);

    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson('/api/series', [
            'company_id' => $company->id,
            'code' => 'ΤΙΜ',
            'document_type' => 'invoice',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('code');
});

it('allows the same series code in a different company', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    Series::factory()->create(['company_id' => $companyA->id, 'code' => 'ΤΙΜ']);

    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson('/api/series', [
            'company_id' => $companyB->id,
            'code' => 'ΤΙΜ',
            'document_type' => 'invoice',
        ])
        ->assertStatus(201);
});

it('rejects an invalid document type', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson('/api/series', [
            'company_id' => $company->id,
            'code' => 'ΧΧΧ',
            'document_type' => 'something_else',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('document_type');
});
