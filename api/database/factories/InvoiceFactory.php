<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Series;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
final class InvoiceFactory extends Factory
{
    /** @var class-string<Invoice> */
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'series_id' => Series::factory(),
            'document_type' => 'invoice',
            'issue_date' => now()->toDateString(),
            'customer_name' => $this->faker->company(),
            'customer_vat_number' => (string) $this->faker->numberBetween(100000000, 999999999),
            'customer_tax_office' => $this->faker->city(),
            'customer_address' => $this->faker->streetAddress(),
            'customer_city' => $this->faker->city(),
            'customer_postal_code' => $this->faker->postcode(),
            'customer_country' => 'GR',
        ];
    }

    public function forCompany(Company $company): self
    {
        return $this->state(fn (): array => [
            'company_id' => $company->id,
            'customer_id' => Customer::factory()->create(['company_id' => $company->id])->id,
            'series_id' => Series::factory()->create(['company_id' => $company->id])->id,
        ]);
    }

    public function withLine(): self
    {
        return $this->afterCreating(function (Invoice $invoice): void {
            $line = InvoiceLine::factory()->make([
                'invoice_id' => $invoice->id,
                'position' => 1,
                'quantity' => '1.000',
                'unit_price_cents' => 10000,
                'vat_rate' => 24,
            ]);

            $line->calculateTotals();
            $line->save();
        });
    }
}
