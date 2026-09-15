<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\IncomeClassification;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCreditNoteRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $invoice = $this->route('invoice');
        $companyId = $invoice instanceof Invoice ? $invoice->company_id : null;

        return [
            'series_id' => [
                'required',
                'integer',
                Rule::exists('series', 'id')
                    ->where('company_id', $companyId)
                    ->where('document_type', 'credit_note'),
            ],
            'issue_date' => ['required', 'date'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.unit' => ['nullable', 'string', 'max:20'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price_cents' => ['required', 'integer', 'min:0'],
            'lines.*.vat_rate' => ['required', 'integer', Rule::in([0, 6, 13, 24])],
            'lines.*.income_classification' => ['required', Rule::enum(IncomeClassification::class)],
        ];
    }
}
