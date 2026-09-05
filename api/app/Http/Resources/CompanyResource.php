<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
final class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'vat_number' => $this->vat_number,
            'tax_office' => $this->tax_office,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
