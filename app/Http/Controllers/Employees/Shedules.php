<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employees;
use App\Models\Employee;
use App\Models\EmployeeShedule;
use App\Models\Log;
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
     * Типы дней
     * 
     * @var array
     */
    protected $options = [
        ['value' => null, 'text' => "Г", 'comment' => "По графику"],
        ['value' => 1, 'text' => "Р", 'comment' => "Рабочий день", 'color' => "green"],
        ['value' => 6, 'text' => "Р+ПЧ", 'comment' => "Рабочий день + П/Ч", 'color' => "olive"],
        ['value' => 2, 'text' => "Р/2", 'comment' => "Неполный рабочий день", 'color' => "blue"],
        ['value' => 7, 'text' => "Р/2+ПЧ", 'comment' => "Неполный рабочий день + П/Ч", 'color' => "violet"],
        ['value' => 3, 'text' => "В", 'comment' => "Выходной", 'color' => "red"],
        ['value' => 4, 'text' => "В/2", 'comment' => "Неполный выходной", 'color' => "orange"],
        ['value' => 5, 'text' => "П", 'comment' => "Переработка", 'color' => "black"],
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

    /**
     * Выводит график сотрудника
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (!$row = Employee::find($request->id))
            return response()->json(['message' => "Данные сотрудника не найдены"], 400);

        $row = Employees::employee($row);

        $shedule = EmployeeShedule::whereEmployeeId($request->id)
            ->where('month', $request->month ?: now()->format("Y-m"))
            ->first();

        return response()->json([
            'employee' => $row,
            'shedule' => $shedule,
            'options' => $this->options,
        ]);
    }

    /**
     * Применяет значение за день
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function set(Request $request)
    {
        if (!$row = Employee::find($request->employee))
            return response()->json(['message' => "Данные сотрудника не найдены"], 400);

        $date = now()->create($request->date ?: now());

        $shedule = EmployeeShedule::firstOrNew([
            'employee_id' => $row->id,
            'month' => $date->copy()->format("Y-m"),
        ]);

        $day = $date->copy()->format("d");
        $data = [
            'type' => $request->value,
        ];

        $days = $shedule->days ?? [];
        $days[$day] = $data;

        $shedule->days = $days;
        $shedule->save();

        Log::write($shedule, $request);

        return response()->json([
            'employee' => $row,
            'shedule' => $shedule,
            'day' => $day,
            'dayData' => $data,
        ]);
    }
}
