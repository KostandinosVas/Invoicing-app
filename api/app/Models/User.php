<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return list<int>
     */
    public function accessibleCompanyIds(): array
    {
        return array_values(
            array_map(
                static fn (mixed $id): int => (int) $id,
                Company::query()->pluck('id')->all(),
            )
        );
    }

    public function role(): Role
    {
        $name = $this->getRoleNames()->first();

        return $name !== null
            ? Role::from((string) $name)
            : Role::Viewer;
    }

    public function canWrite(): bool
    {
        return $this->role()->canWrite();
    }
}
