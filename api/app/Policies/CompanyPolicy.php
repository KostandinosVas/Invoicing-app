<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

final class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Company $company): bool
    {
        return in_array($company->id, $user->accessibleCompanyIds(), true);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Company $company): bool
    {
        return $this->view($user, $company);
    }

    public function delete(User $user, Company $company): bool
    {
        return false;
    }
}
