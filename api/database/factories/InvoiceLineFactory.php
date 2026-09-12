<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\IncomeClassification;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceLine>
 */
final class InvoiceLineFactory extends Factory
{
    /** @var class-string<InvoiceLine> */
    protected $model = InvoiceLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'item_id' => null,
            'position' => 1,
            'description' => $this->faker->words(3, true),
            'unit' => 'τεμ',
            'quantity' => '1.000',
            'unit_price_cents' => $this->faker->numberBetween(100, 100000),
            'vat_rate' => 24,
            'net_amount_cents' => 0,
            'vat_amount_cents' => 0,
            'total_cents' => 0,
            'income_classification' => IncomeClassification::ServicesProvision,
        ];
    }
}
