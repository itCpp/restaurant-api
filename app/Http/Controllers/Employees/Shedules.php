<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Shedules extends Controller
{
    /**
     * Типы графика
     * 
     * @var array
     */
    protected $types = [
        1 => '1/1',
        2 => '2/2',
        3 => '3/3',
        4 => '5/2',
        5 => '6/1',
        6 => '7/0',
    ];

    /**
     * Выводит тип графика
     * 
     * @param  null|int $type
     * @return string|null
     */
    public function getType($type = null)
    {
        return $this->types[$type] ?? null;
    }

    /**
     * Формиурет массив данных для выпадающего списка
     * 
     * @return array
     */
    public function getSheduleTypes()
    {
        foreach ($this->types as $key => $type) {
            $types[] = [
                'key' => $key,
                'value' => $key,
                'text' => $type,
            ];
        }

        return $types ?? [];
    }
}
