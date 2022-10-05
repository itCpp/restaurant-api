<?php

namespace App\Http\Controllers\Employees\Salaries;

use Illuminate\Http\Request;

class Salaries
{
    use Cashbox,
        Users,
        Result;

    /**
     * Данные на вывод
     * 
     * @var array
     */
    protected $data = [];

    /**
     * Инициализация объекта
     * 
     * @return void
     */
    public function __construct()
    {
        $this->getUsers()
            ->getPrepayments()
            ->getResult();
    }

    /**
     * Вывод данных получки
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function index(Request $request)
    {
        $request->merge([
            'start' => now()->create($request->month ?: now())->startOfMonth()->format("Y-m-d"),
            'stop' => now()->create($request->month ?: now())->endOfMonth()->format("Y-m-d"),
        ]);

        return response()->json(
            (new static)->get($request)
        );
    }

    /**
     * Расчет данных получки
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function get(Request $request)
    {
        return $this->data;
    }

    /**
     * Обработка несуществующего метода
     * 
     * @param mixed $name
     * @param mixed $arguments
     * @return $this
     */
    public function __call($name, $arguments)
    {
        return $this;
    }
}
