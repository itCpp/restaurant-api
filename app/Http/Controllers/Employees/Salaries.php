<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employees;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Log;
use Illuminate\Http\Request;

class Salaries extends Controller
{
    /**
     * Применение оклада
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function set(Request $request)
    {
        if (!$row = Employee::find($request->id))
            return response()->json(['message' => "Данные сотрудника не найдены"], 400);

        $row = (new Employees)->employee($row);

        $salary = EmployeeSalary::firstOrNew([
            'employee_id' => $row->id,
            'start_date' => $request->date,
        ]);

        $salary->salary = (float) $request->salary;
        $salary->is_one_day = (bool) $request->is_one_day;
        $salary->salary_prev = $row->salary ?? 0;

        $salary->save();

        Log::write($salary, $request);

        $row->salary = $salary->salary;
        $row->salary_date = $salary->start_date;

        return response()->json([
            'row' => $row,
            'salary' => $salary,
        ]);
    }
}
