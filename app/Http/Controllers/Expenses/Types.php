<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use App\Models\ExpenseSubtype;
use Illuminate\Http\Request;

class Types extends Controller
{
    /**
     * ВЫводит список фиксированых наименований для типа расхода
     * 
     * @param  \Illuminate\Htpp\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubTypesList(Request $request)
    {
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
     * Создает новую строку в список данных
     * 
     * @param  int $type_id
     * @param  string $name
     * @return int
     */
    public static function createSubType($type_id, $name)
    {
        return ExpenseSubtype::create([
            'expense_type_id' => $type_id,
            'name' => $name,
        ])->id;
    }
}
