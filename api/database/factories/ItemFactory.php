<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\IncomeClassification;
use App\Models\Company;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
final class ItemFactory extends Factory
{
    /** @var class-string<Item> */
    protected $model = Item::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => $this->faker->unique()->bothify('ITM-####'),
            'name' => $this->faker->words(3, true),
            'unit' => 'τεμ',
            'unit_price_cents' => $this->faker->numberBetween(100, 100000),
            'vat_rate' => $this->faker->randomElement([24, 13, 6, 0]),
            'is_active' => true,
            'income_classification' => IncomeClassification::ServicesProvision,
        ];
    }
}
