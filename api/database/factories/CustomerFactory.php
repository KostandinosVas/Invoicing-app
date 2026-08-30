<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
final class CustomerFactory extends Factory
{
    /** @var class-string<Customer> */
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->company(),
            'vat_number' => (string) $this->faker->unique()->numberBetween(100000000, 999999999),
            'tax_office' => $this->faker->city(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'postal_code' => $this->faker->postcode(),
            'country' => 'GR',
        ];
    }
}
