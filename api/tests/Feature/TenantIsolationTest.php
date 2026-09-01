<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;

it('scopes customers to accessible companies', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    Customer::factory()->create(['company_id' => $companyA->id, 'name' => 'Πελάτης Α']);
    Customer::factory()->create(['company_id' => $companyB->id, 'name' => 'Πελάτης Β']);

    $user = User::factory()->create();
    test_case()->actingAs($user);

    expect(Customer::query()->count())->toBe(2);
});

it('does not scope when there is no authenticated user', function () {
    $company = Company::factory()->create();
    Customer::factory()->create(['company_id' => $company->id]);

    expect(Customer::query()->count())->toBe(1);
});

it('hides customers of companies the user cannot access', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    Customer::factory()->create(['company_id' => $companyA->id, 'name' => 'Ορατός']);
    Customer::factory()->create(['company_id' => $companyB->id, 'name' => 'Κρυμμένος']);

    $user = User::factory()->create();

    $mock = Mockery::mock($user)->makePartial();
    $mock->shouldReceive('accessibleCompanyIds')->andReturn([$companyA->id]);

    /** @var User $mock */
    test_case()->actingAs($mock);

    expect(Customer::query()->count())->toBe(1)
        ->and(Customer::query()->first()?->name)->toBe('Ορατός');
});
