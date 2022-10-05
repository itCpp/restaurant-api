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
        [
            'id' => 1,
            'name' => "Аренда",
            'every_month' => true,
            'icon' => "building"
        ],
        [
            'id' => 2,
            'name' => "Парковка",
            'every_month' => true,
            'icon' => "car"
        ],
        [
            'id' => 3,
            'name' => "Депозит",
            'every_month' => false,
            'icon' => "money bill alternate outline"
        ],
        [
            'id' => 4,
            'name' => "Юр. адрес",
            'every_month' => false,
            'icon' => "point"
        ],
        [
            'id' => 5,
            'name' => "Интернет",
            'every_month' => true,
            'icon' => "internet explorer"
        ],
        [
            'id' => 6,
            'name' => "Доп. услуги",
            'every_month' => true,
            'icon' => "add to cart"
        ],
    ];

    /**
     * Возвращает коллекцию данных
     * 
     * @return \Illuminate\Support\Collection<TKey, TValue>
     */
    public static function collect()
    {
        return collect((new static)->purposes);
    }

    /**
     * Возвращает массив типа назначения платежа по идентификатору
     * 
     * @param  int $id
     * @return array|null
     */
    public function getFromId($id)
    {
        foreach ($this->purposes as $row) {

            if ($row['id'] == $id)
                return $row;
        }

        return null;
    }

    /**
     * Выводит все типы назначений
     * 
     * @return array
     */
    public static function getAll()
    {
        return (new static)->purposes;
    }

    /**
     * Выводит идентификаторы ежемесячных платежей
     * 
     * @return array
     */
    public static function getEveryMonthId()
    {
        return collect((new static)->purposes)
            ->where('every_month', true)
            ->map(function ($row) {
                return $row['id'];
            })
            ->all();
    }
}
