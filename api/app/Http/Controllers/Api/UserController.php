<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class UserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        return UserResource::collection(User::query()->orderBy('name')->get());
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): UserResource
    {
        $this->authorize('changeRole', $user);

        // syncRoles, όχι assignRole: ο χρήστης έχει έναν ρόλο κάθε φορά.
        $user->syncRoles([$request->role()->value]);

        return new UserResource($user->fresh());
    }
}
