<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TableMetaField extends Model
{
    use HasFactory;

    protected $fillable = [
        'managed_table_id',
        'meta_key',
        'meta_value',
    ];

    protected $casts = [
        'meta_value' => 'boolean',
    ];

    public function managedTable()
    {
        return $this->belongsTo(ManagedTable::class, 'managed_table_id');
    }
}
