<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCompanyCredentialsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'mydata_user_id' => ['required', 'string', 'max:255'],
            'mydata_subscription_key' => ['required', 'string', 'max:255'],
        ];
    }
}
