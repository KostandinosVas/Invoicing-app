<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * @implements Scope<Model>
 */
final class CompanyScope implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $builder->whereIn(
            $model->qualifyColumn('company_id'),
            $user->accessibleCompanyIds(),
        );
    }
}
