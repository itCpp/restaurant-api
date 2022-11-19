<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Проверяет булевое значение
     * 
     * @param  mixed  $value
     * @return boolean|null
     */
    public static function isBoolean($value)
    {
        if (is_bool($value))
            return $value;

        if (is_string($value) and in_array($value, ["true", "yes", "Yes", "YES"]))
            return true;

        if (is_string($value) and in_array($value, ["false", "no", "No", "NO"]))
            return false;

        if (is_numeric($value))
            return $value > 0;

        return null;
    }
}
