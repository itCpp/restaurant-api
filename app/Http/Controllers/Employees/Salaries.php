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

        $salary = EmployeeSalary::create([
            'employee_id' => $row->id,
            'salary' => (float) $request->salary,
            'salary_prev' => $row->salary ?? 0,
            'start_date' => $request->date,
        ]);

        Log::write($salary, $request);

        $row->salary = $salary->salary;
        $row->salary_date = $salary->start_date;

        return response()->json([
            'row' => $row,
            'salary' => $salary,
        ]);
    }
}
