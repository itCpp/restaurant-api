<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class Employees extends Controller
{
    /**
     * Вывод сотрудников
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json([
            'rows' => $rows ?? [],
        ]);
    }

    /**
     * Создание нового сотрудника
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $request->validate([
            'surname' => "required",
            'name' => "required",
            'phone' => "required",
        ]);

        $row = new Employee;

        $row->pin = $this->findNewPin();
        $row->employee_otdel_id = $request->employee_otdel_id;
        $row->surname = $request->surname;
        $row->name = $request->name;
        $row->middle_name = $request->middle_name;
        $row->job_title = $request->job_title;
        $row->phone = $request->phone;
        $row->telegram_id = $request->telegram_id;
        $row->email = $request->email;
        $row->personal_data = $request->personal_data;

        $row->save();

        return response()->json($row);
    }

    /**
     * Находит персональный идентификационный номер для нового сотрудника
     * 
     * @return int
     */
    public function findNewPin()
    {
        $max = (int) Employee::max('pin');

        return $max > 100 ? $max : 100;
    }
}
