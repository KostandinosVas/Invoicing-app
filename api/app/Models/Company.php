<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'vat_number',
        'tax_office',
        'address',
        'city',
        'postal_code',
    ];
}
