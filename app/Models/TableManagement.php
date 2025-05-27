<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableManagement extends Model
{
    protected $fillable = ['company_id', 'user_id', 'table_name'];

    public function metas()
    {
        return $this->hasMany(TableMeta::class, 'table_id');
    }
}
