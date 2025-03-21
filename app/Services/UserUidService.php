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
            // Lock the table to avoid concurrent modifications
            $lastUid = DB::table('users')->lockForUpdate()->max('uid');

            if ($lastUid) {
                $numericPart = (int) preg_replace('/\D/', '', $lastUid);
                $newNumber   = $numericPart + 1;
            } else {
                $newNumber = 1;
            }

            return 'AMT' . str_pad($newNumber, 10, '0', STR_PAD_LEFT);
        });
    }
}
