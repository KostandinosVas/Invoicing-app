<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInvoiceRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $user = $this->user();
        $companyIds = $user instanceof User ? $user->accessibleCompanyIds() : [];
        $companyId = $this->integer('company_id');

        return [
            'company_id' => ['required', 'integer', Rule::in($companyIds)],

            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('company_id', $companyId),
            ],

            'series_id' => [
                'required',
                'integer',
                Rule::exists('series', 'id')->where('company_id', $companyId),
            ],

            'document_type' => ['required', 'string', Rule::in(['invoice', 'credit_note', 'cancellation'])],
            'issue_date' => ['required', 'date'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => [
                'nullable',
                'integer',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'lines.*.description' => ['required_without:lines.*.item_id', 'nullable', 'string', 'max:255'],
            'lines.*.unit' => ['nullable', 'string', 'max:20'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price_cents' => ['nullable', 'integer', 'min:0'],
            'lines.*.vat_rate' => ['nullable', 'integer', Rule::in([0, 6, 13, 24])],
        ];
    }
}
