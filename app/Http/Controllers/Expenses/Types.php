<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ExpenseSubtype;
use App\Models\ExpenseType;
use Illuminate\Http\Request;

class Types extends Controller
{
    /**
     * Выводит список фиксированых наименований для типа расхода
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubTypesList(Request $request)
    {
        $type = ExpenseType::find($request->id);

        if (($type->type_subtypes ?? null) == "users")
            return $this->getSubTypesListEmployees($request);

        $rows = ExpenseSubtype::whereExpenseTypeId($request->id)
            ->orderBy('name')
            ->get()
            ->map(function ($row) {
                return [
                    'text' => $row->name,
                    'value' => $row->id,
                ];
            })
            ->toArray();

        return response()->json($rows);
    }

    /**
     * Выводит список сотрудников
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubTypesListEmployees(Request $request)
    {
        return response()->json(
            Employee::withTrashed()
                ->orderBy('surname')
                ->get()
                ->map(function ($row) {

                    $name = ((string) $row->surname) . " ";
                    $name .= ((string) $row->name) . " ";
                    $name .= (string) $row->middle_name;

                    return [
                        'text' => trim($name),
                        'value' => $row->id,
                    ];
                })
                ->toArray()
        );
    }

    /**
     * Создает новую строку в список данных
     * 
     * @param  int $type_id
     * @param  string $name
     * @return int
     */
    public static function createSubType($type_id, $name)
    {
        $type = ExpenseType::find($type_id);

        if (($type->type_subtypes ?? null) == "users")
            return self::createSubTypeEmployee($name);

        return ExpenseSubtype::create([
            'expense_type_id' => $type_id,
            'name' => $name,
        ])->id;
    }

    /**
     * Создаёт запись сотрудника при расходе
     * 
     * @param  string $name
     * @return int
     */
    public static function createSubTypeEmployee($name)
    {
        $name = trim(preg_replace('/\s/', ' ', $name));
        $name = explode(" ", $name);

        $row = new Employee;
        $row->surname = $name[0] ?? null;
        $row->name = $name[1] ?? null;
        $row->middle_name = $name[2] ?? null;

        $row->save();

        return $row->id;
    }
}
