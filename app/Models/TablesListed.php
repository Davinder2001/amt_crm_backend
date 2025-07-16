<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TableColumn extends Model
{
    use HasFactory;

    protected $table = 'table_columns';

    protected $fillable = [
        'table_id',
        'column_name',
    ];

    public function table()
    {
        return $this->belongsTo(TablesListed::class, 'table_id');
    }
}
