<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Purposes extends Controller
{
    /**
     * Типы назначений платежа
     * 
     * @var array
     */
    protected $purposes = [
        ['id' => 1, 'name' => "Аренда", 'every_month' => true],
        ['id' => 2, 'name' => "Парковка", 'every_month' => true],
        ['id' => 3, 'name' => "Депозит", 'every_month' => false],
        ['id' => 4, 'name' => "Юр. адрес", 'every_month' => false],
    ];

    /**
     * Выводит все типы назначений
     * 
     * @return array
     */
    public static function getAll()
    {
        return (new static)->purposes;
    }
}
