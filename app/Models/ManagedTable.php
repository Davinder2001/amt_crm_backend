<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ManagedTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'table_name',
    ];

    public function metaFields()
    {
        return $this->hasMany(TableMetaField::class, 'managed_table_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
