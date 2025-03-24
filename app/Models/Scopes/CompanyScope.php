<?php
namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (app()->has('active_company_id')) {
            $activeCompanyId = app('active_company_id');
            $userId = Auth::id();
            

            // $get = $builder->where($model->getTable() . '.company_id', $activeCompanyId);
            // dd($get);

            if (in_array('company_id', $model->getFillable()) || $model->getTable() === 'companies') {
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
