<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property InvoiceStatus $status
 * @property int|null $number
 * @property int $net_amount_cents
 * @property int $vat_amount_cents
 * @property int $total_cents
 */
final class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use BelongsToCompany, HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'customer_id',
        'series_id',
        'document_type',
        'issue_date',
        'customer_name',
        'customer_vat_number',
        'customer_tax_office',
        'customer_address',
        'customer_city',
        'customer_postal_code',
        'customer_country',
        'related_invoice_id',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'number' => 'integer',
            'net_amount_cents' => 'integer',
            'vat_amount_cents' => 'integer',
            'total_cents' => 'integer',
            'mydata_submitted_at' => 'datetime',
        ];
    }

    public function transitionTo(InvoiceStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidStatusTransition::between($this->status, $target);
        }

        $this->status = $target;
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Series, $this>
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }
}
