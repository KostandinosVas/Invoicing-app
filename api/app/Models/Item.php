<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use BelongsToCompany, HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'unit',
        'unit_price_cents',
        'vat_rate',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'vat_rate' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
