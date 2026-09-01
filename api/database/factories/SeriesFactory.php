<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Series;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Series>
 */
final class SeriesFactory extends Factory
{
    /** @var class-string<Series> */
    protected $model = Series::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => $this->faker->unique()->bothify('Σ##'),
            'document_type' => 'invoice',
            'is_active' => true,
        ];
    }
}
