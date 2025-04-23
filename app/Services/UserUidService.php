<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserUidService
{
    /**
     * Generate a new, sequential UID in the format: AMT0000000001, AMT0000000002, etc.
     *
     * @return string
     */
    public static function generateNewUid(): string
    {
        return DB::transaction(function () {

            $lastUser = DB::table('users')->select('uid')
                ->orderByDesc('uid')->lockForUpdate()->first();
    
            if ($lastUser && preg_match('/\d+/', $lastUser->uid, $matches)) {
                $numericPart = (int) $matches[0];
                $newNumber = $numericPart + 1;
            } else {
                $newNumber = 1;
            }
    
            return 'AMT' . str_pad($newNumber, 10, '0', STR_PAD_LEFT);
        });
    }
    
}
