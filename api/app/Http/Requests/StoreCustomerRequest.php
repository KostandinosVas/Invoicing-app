<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCustomerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'digits:9'],
            'tax_office' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'size:2'],
        ];
    }
}
