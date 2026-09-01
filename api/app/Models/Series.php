<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\SeriesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Series extends Model
{
    /** @use HasFactory<SeriesFactory> */
    use BelongsToCompany, HasFactory;

    protected $table = 'series';

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'code',
        'document_type',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_number' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
