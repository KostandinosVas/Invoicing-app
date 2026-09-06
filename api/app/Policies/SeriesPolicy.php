<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Series;
use App\Models\User;

final class SeriesPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Series $series): bool
    {
        return in_array($series->company_id, $user->accessibleCompanyIds(), true);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Series $series): bool
    {
        return $this->view($user, $series);
    }

    public function delete(User $user, Series $series): bool
    {
        return false;
    }
}
