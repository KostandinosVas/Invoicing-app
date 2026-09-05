<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;

it('creates a customer in an accessible company', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson('/api/customers', [
            'company_id' => $company->id,
            'name' => 'Πελάτης ΑΕ',
            'vat_number' => '123456789',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'Πελάτης ΑΕ');
});

it('rejects a customer in an inaccessible company', function () {
    $accessible = Company::factory()->create();
    $forbidden = Company::factory()->create();

    $user = User::factory()->create();

    $mock = Mockery::mock($user)->makePartial();
    $mock->shouldReceive('accessibleCompanyIds')->andReturn([$accessible->id]);

    /** @var User $mock */
    test_case()->actingAs($mock)
        ->postJson('/api/customers', [
            'company_id' => $forbidden->id,
            'name' => 'Πελάτης ΑΕ',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('company_id');
});

it('filters customers by company', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    Customer::factory()->count(2)->create(['company_id' => $companyA->id]);
    Customer::factory()->create(['company_id' => $companyB->id]);

    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->getJson("/api/customers?company_id={$companyA->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('requires authentication', function () {
    test_case()->getJson('/api/customers')->assertStatus(401);
});
