<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use BelongsToCompany, HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'name',
        'vat_number',
        'tax_office',
        'address',
        'city',
        'postal_code',
        'country',
    ];
}
