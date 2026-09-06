<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreItemRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:20'],
            'unit_price_cents' => ['required', 'integer', 'min:0'],
            'vat_rate' => ['required', 'integer', Rule::in([0, 6, 13, 24])],
            'is_active' => ['boolean'],
        ];
    }
}
