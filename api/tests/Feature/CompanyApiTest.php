<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;

it('requires authentication', function () {
    test_case()->getJson('/api/companies')->assertStatus(401);
});

it('lists companies', function () {
    Company::factory()->count(3)->create();
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->getJson('/api/companies')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('creates a company', function () {
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson('/api/companies', [
            'name' => 'Τεχνομέταλ ΑΕ',
            'vat_number' => '123456789',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'Τεχνομέταλ ΑΕ');

    expect(Company::query()->where('vat_number', '123456789')->exists())->toBeTrue();
});

it('rejects an invalid vat number', function () {
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson('/api/companies', [
            'name' => 'Τεχνομέταλ ΑΕ',
            'vat_number' => '123',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('vat_number');
});

it('rejects a duplicate vat number', function () {
    Company::factory()->create(['vat_number' => '123456789']);
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->postJson('/api/companies', [
            'name' => 'Άλλη ΑΕ',
            'vat_number' => '123456789',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('vat_number');
});

it('does not expose unlisted fields', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    test_case()->actingAs($user)
        ->getJson("/api/companies/{$company->id}")
        ->assertOk()
        ->assertJsonMissingPath('data.updated_at');
});
