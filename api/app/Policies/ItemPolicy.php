<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

final class ItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Item $item): bool
    {
        return in_array($item->company_id, $user->accessibleCompanyIds(), true);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Item $item): bool
    {
        return $this->view($user, $item);
    }

    public function delete(User $user, Item $item): bool
    {
        return false;
    }
}
