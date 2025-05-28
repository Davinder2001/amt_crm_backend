<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TablesListed extends Model
{
    use HasFactory;

    protected $table = 'tables_listed';

    protected $fillable = [
        'name',
    ];

    public function columns()
    {
        return $this->hasMany(TableColumn::class, 'table_id');
    }
}
