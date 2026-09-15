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

            'last_submission' => $this->whenLoaded('submissions', function () {
                $submission = $this->submissions->first();

                return $submission === null ? null : [
                    'status' => $submission->status->value,
                    'attempt' => $submission->attempt,
                    'errors' => $submission->errors ?? [],
                    'completed_at' => $submission->completed_at?->toIso8601String(),
                ];
            }),

            'lines' => InvoiceLineResource::collection($this->whenLoaded('lines')),

            'related_invoice_id' => $this->related_invoice_id,

            'corrections' => $this->whenLoaded('corrections', fn () => $this->corrections->map(
                fn (Invoice $correction): array => [
                    'id' => $correction->id,
                    'document_type' => $correction->document_type,
                    'number' => $correction->number,
                    'total_cents' => $correction->total_cents,
                    'status' => $correction->status->value,
                ],
            )),

            'mydata_cancellation_mark' => $this->mydata_cancellation_mark,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
        ];
    }
}
