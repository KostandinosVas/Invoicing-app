<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role() === Role::Admin;
    }

    public function changeRole(User $user, User $target): bool
    {
        return $user->role() === Role::Admin
            && $user->id !== $target->id;
    }
}
