<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Item;
use App\Models\Series;
use Illuminate\Database\QueryException;

it('stores item price as integer cents', function () {
    $item = Item::factory()->create(['unit_price_cents' => 1250]);

    expect($item->unit_price_cents)->toBe(1250)
        ->and($item->unit_price_cents)->toBeInt();
});

it('does not allow duplicate item codes within a company', function () {
    $company = Company::factory()->create();

    Item::factory()->create(['company_id' => $company->id, 'code' => 'ITM-001']);

    expect(fn () => Item::factory()->create([
        'company_id' => $company->id,
        'code' => 'ITM-001',
    ]))->toThrow(QueryException::class);
});

it('allows the same item code in different companies', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    Item::factory()->create(['company_id' => $companyA->id, 'code' => 'ITM-001']);
    Item::factory()->create(['company_id' => $companyB->id, 'code' => 'ITM-001']);

    expect(Item::query()->where('code', 'ITM-001')->count())->toBe(2);
});

it('does not allow last_number to be mass assigned', function () {
    $series = Series::factory()->create();

    $series->fill(['last_number' => 999]);
    $series->save();
    $series->refresh();

    expect($series->last_number)->toBe(0);
});
