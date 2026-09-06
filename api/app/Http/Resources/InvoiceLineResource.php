<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InvoiceLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InvoiceLine
 */
final class InvoiceLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'item_id' => $this->item_id,
            'description' => $this->description,
            'unit' => $this->unit,
            'quantity' => $this->quantity,
            'unit_price_cents' => $this->unit_price_cents,
            'vat_rate' => $this->vat_rate,
            'net_amount_cents' => $this->net_amount_cents,
            'vat_amount_cents' => $this->vat_amount_cents,
            'total_cents' => $this->total_cents,
        ];
    }
}
