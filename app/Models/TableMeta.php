<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableMeta extends Model
{
    protected $table = 'table_meta';

    protected $fillable = ['table_id', 'col_name', 'value'];

    public function table()
    {
        return $this->belongsTo(TableManagement::class, 'table_id');
    }
}
