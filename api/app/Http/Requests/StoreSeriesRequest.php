<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSeriesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $user = $this->user();
        $companyIds = $user instanceof User ? $user->accessibleCompanyIds() : [];

        return [
            'company_id' => ['required', 'integer', Rule::in($companyIds)],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('series', 'code')
                    ->where(fn ($query) => $query->where('company_id', $this->integer('company_id'))),
            ],
            'document_type' => ['required', 'string', Rule::in(['invoice', 'credit_note', 'cancellation'])],
            'is_active' => ['boolean'],
        ];
    }
}
