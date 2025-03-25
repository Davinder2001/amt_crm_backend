<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryHistory extends Model
{
    protected $fillable = [
        'user_id', 'previous_salary', 'new_salary', 'increment_date', 'reason'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function salaryHistories()
    {
        return $this->hasMany(SalaryHistory::class, 'user_id', 'id')->latest();
    }

}
