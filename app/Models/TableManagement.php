<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableManagement extends Model
{
    protected $fillable = ['company_id', 'user_id', 'tables_listed_id'];

    public function metas()
    {
        return $this->hasMany(TableMeta::class, 'table_id');
    }

    public function tableManagements()
    {
        return $this->hasMany(TableManagement::class, 'tables_listed_id');
    }
}