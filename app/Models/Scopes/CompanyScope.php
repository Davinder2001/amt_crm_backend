<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        if ($user->hasRole('super-admin')) {
            return;
        }

        $token = $user->currentAccessToken();

        if (!$token || !$token->active_company_id) {
            throw new HttpException(422, 'Active company not selected.');
        }

        $activeCompanyId = $token->active_company_id;
        $userId = $user->id;

        if ($model->getTable() === 'users') {
            $builder->whereExists(function ($query) use ($activeCompanyId) {
                $query->selectRaw(1)
                    ->from('company_user')
                    ->whereColumn('company_user.user_id', 'users.id')
                    ->where('company_user.company_id', $activeCompanyId);
            });
        } else {
            if (in_array('company_id', $model->getFillable()) || $model->getTable() === 'companies') {
                $builder->where($model->getTable() . '.company_id', $activeCompanyId);

                $builder->whereExists(function ($query) use ($userId, $activeCompanyId) {
                    $query->selectRaw(1)
                        ->from('company_user')
                        ->where('company_user.user_id', $userId)
                        ->where('company_user.company_id', $activeCompanyId);
                });
            }
        }
    }
}

