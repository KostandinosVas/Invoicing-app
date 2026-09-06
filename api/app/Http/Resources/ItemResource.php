<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Item
 */
final class ItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'name' => $this->name,
            'unit' => $this->unit,
            'unit_price_cents' => $this->unit_price_cents,
            'vat_rate' => $this->vat_rate,
            'is_active' => $this->is_active,
        ];
    }
}
