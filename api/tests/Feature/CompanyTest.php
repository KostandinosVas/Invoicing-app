<?php

declare(strict_types=1);

use App\Models\Company;
use Illuminate\Database\QueryException;

it('creates a company', function () {
    $company = Company::factory()->create(['name' => 'Τεχνομέταλ ΑΕ']);

    expect($company->name)->toBe('Τεχνομέταλ ΑΕ');

    expect(Company::where('name', 'Τεχνομέταλ ΑΕ')->exists())->toBeTrue();
});

it('does not allow duplicate vat numbers', function () {
    Company::factory()->create(['vat_number' => '123456789']);

    expect(fn () => Company::factory()->create(['vat_number' => '123456789']))
        ->toThrow(QueryException::class);
});
