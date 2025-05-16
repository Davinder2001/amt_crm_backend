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
            // Extract numeric part of UID and order by it numerically
            $lastUser = DB::table('users')
                ->selectRaw("CAST(SUBSTRING(uid, 4) AS UNSIGNED) AS uid_number")
                ->orderByDesc('uid_number')
                ->lockForUpdate()
                ->first();

            $newNumber = $lastUser ? ((int) $lastUser->uid_number + 1) : 1;

            return 'AMT' . str_pad($newNumber, 10, '0', STR_PAD_LEFT);
        });
    }
}
