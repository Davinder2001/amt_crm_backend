<?php

namespace App\Helpers;

class ActiveCompany
{
    protected static $activeCompanyId = null;
    
    public static function set($companyId)
    {
        self::$activeCompanyId = $companyId;
    }
    
    public static function get()
    {
        return self::$activeCompanyId;
    }
}
