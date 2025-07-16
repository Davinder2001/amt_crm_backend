<?php
namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class SuperAdminScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // $user = Auth::user();

        return
        // if ($user && $user->hasRole('super-admin')) {
        //     return;
        // }

        $builder->whereRaw('0 = 1'); 
    }
}
