<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Series
 */
final class SeriesResource extends JsonResource
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
            'document_type' => $this->document_type,
            'last_number' => $this->last_number,
            'is_active' => $this->is_active,
        ];
    }
}
