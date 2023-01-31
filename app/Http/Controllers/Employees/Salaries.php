<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employees;
use App\Http\Controllers\Employees\Salaries\Salaries as SalariesSalaries;
use App\Http\Controllers\Expenses;
use App\Models\Employee;
use App\Models\EmployeeSalariesProcessing;
use App\Models\EmployeeSalary;
use App\Models\Log;
use Illuminate\Http\Request;

class Salaries extends Controller
{
    /**
     * Вывод получки сотрудника
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return SalariesSalaries::index($request);
    }

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

        $find = false;

        $salary = EmployeeSalary::where('employee_id', $row->id)
            ->where('start_date', $request->date)
            ->first();

        if ($salary)
            $find = true;

        if (!$salary)
            $salary = new EmployeeSalary;

        $salary->employee_id = $row->id;
        $salary->start_date = $request->date;
        $salary->salary = (float) $request->salary;
        $salary->is_one_day = (bool) $request->is_one_day;

        if (!$find) {
            $salary->salary_prev = $row->salary ?? 0;
            $salary->is_one_day_prev = (bool) ($row->salary_one_day ?? null);
        }

        $salary->save();

        Log::write($salary, $request);

        $row->salary = $salary->salary;
        $row->salary_one_day = $salary->is_one_day;
        $row->salary_date = $salary->start_date;

        return response()->json([
            'row' => $row,
            'salary' => $salary,
        ]);
    }

    /**
     * Типы оплат
     * 
     * @return array
     */
    public static function getPurposePays()
    {
        return [
            1 => "АВАНС",
            2 => "ЗП",
            3 => "ПРЕМИЯ",
        ];
    }

    /**
     * Типы оплат
     * 
     * @return array
     */
    public static function getPurposeSalariesOptions()
    {
        return [
            ['key' => 0, 'text' => "Аванс", 'value' => 1, 'name' => "АВАНС"],
            ['key' => 1, 'text' => "Зарплата", 'value' => 2, 'name' => "ЗП"],
            ['key' => 2, 'text' => "Премия", 'value' => 3, 'name' => "ПРЕМИЯ"],
        ];
    }

    /**
     * Типы расчетов зарплаты
     * 
     * @return array
     */
    public static function salaryCountPaysIds()
    {
        return [1, 2];
    }

    /**
     * Выдача получки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        $start = now()->create($request->period_start)->format("d");
        $stop = now()->create($request->period_stop)->format("d.m");

        $purpose = $this->getPurposePays()[$request->purpose_pay] ?? "АВАНС";

        $request->merge([
            'name' => "{$purpose} {$start}-{$stop}",
        ]);

        return (new Expenses)->save($request);
    }

    /**
     * Вывод текущих перератботок
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProcessing(Request $request, Employee $employee)
    {
        $month = now()->create($request->month ?: now());

        return response()->json([
            'processings' => $employee->getMonthProcessings($month)
        ]);
    }

    /**
     * Сохраняет данные переработки
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeProcessing(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'processings.*.id' => ["nullable", "exists:App\Models\EmployeeSalariesProcessing,id"],
            'processings.*.date' => ["required", "date:Y-m-d"],
            'processings.*.hour' => ["required", "numeric"],
            'deleted' => ["nullable"],
        ], [
            'processings.*.date.required' => "Необходимо указать дату",
            'processings.*.date.date' => "Дата указана в неверном формате",
            'processings.*.date.hour' => "Необходимо указать количество часов переработки",
            'processings.*.date.numeric' => "Количество часов должно быть числом",
        ]);

        foreach ($data['processings'] ?? [] as $processing) {

            $processing['user_id'] = optional($request->user())->id;
            $processing['employee_id'] = $employee->id;
            $processing['processing'] = ($employee->personal_data['processing_hour'] ?? 0) * ($processing['hour'] ?? 0);

            $row = EmployeeSalariesProcessing::firstOrNew(['id' => $processing['id'] ?? null]);
            $row->fill($processing)->save();
        }

        if (is_array($data['deleted'] ?? null)) {
            foreach ($data['deleted'] as $id) {
                if ($row = EmployeeSalariesProcessing::find($id)) {
                    $row->delete();
                }
            }
        }

        return response()->json([
            'message' => "Данные переработки обновлены",
        ]);
    }
}
