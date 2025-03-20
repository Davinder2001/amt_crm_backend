<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $user = Auth::user();

        if ($user) {
            $activeCompany = CompanyUser::where('user_id', $user->id)
                                        ->where('status', 1)
                                        ->value('company_id');

            if ($activeCompany) {
                $builder->where($model->getTable() . '.company_id', $activeCompany);
            }
        }
    }
}
