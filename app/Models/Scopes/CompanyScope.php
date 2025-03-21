<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Request;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $activeCompanyId = request()->attributes->get('activeCompanyId');

        if ($activeCompanyId) {
            $builder->where($model->getTable().'.company_id', $activeCompanyId);
        }
    }
}
