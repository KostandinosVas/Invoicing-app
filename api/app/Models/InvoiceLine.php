<?php

declare(strict_types=1);

namespace App\Models;

use App\ValueObjects\Money;
use Database\Factories\InvoiceLineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $unit_price_cents
 * @property int $vat_rate
 * @property int $net_amount_cents
 * @property int $vat_amount_cents
 * @property int $total_cents
 * @property string $quantity
 */
final class InvoiceLine extends Model
{
    /** @use HasFactory<InvoiceLineFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'invoice_id',
        'item_id',
        'position',
        'description',
        'unit',
        'quantity',
        'unit_price_cents',
        'vat_rate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'unit_price_cents' => 'integer',
            'vat_rate' => 'integer',
            'net_amount_cents' => 'integer',
            'vat_amount_cents' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    public function calculateTotals(): void
    {
        $net = Money::fromCents($this->unit_price_cents)
            ->multipliedBy($this->quantity);

        $vat = $net->percentage($this->vat_rate);

        $this->net_amount_cents = $net->cents();
        $this->vat_amount_cents = $vat->cents();
        $this->total_cents = $net->plus($vat)->cents();
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
