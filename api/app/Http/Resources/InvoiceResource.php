<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invoice
 */
final class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'series_id' => $this->series_id,
            'document_type' => $this->document_type,
            'status' => $this->status->value,
            'number' => $this->number,
            'issue_date' => $this->issue_date?->toDateString(),

            'customer' => [
                'id' => $this->customer_id,
                'name' => $this->customer_name,
                'vat_number' => $this->customer_vat_number,
                'tax_office' => $this->customer_tax_office,
                'address' => $this->customer_address,
                'city' => $this->customer_city,
                'postal_code' => $this->customer_postal_code,
                'country' => $this->customer_country,
            ],

            'totals' => [
                'net_amount_cents' => $this->net_amount_cents,
                'vat_amount_cents' => $this->vat_amount_cents,
                'total_cents' => $this->total_cents,
            ],

            'mydata_mark' => $this->mydata_mark,

            'lines' => InvoiceLineResource::collection($this->whenLoaded('lines')),
        ];
    }
}
