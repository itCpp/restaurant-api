<?php

namespace App\Http\Controllers;

use App\Models\CashboxTransaction;
use App\Models\ExpenseType;
use Illuminate\Http\Request;

class Expenses extends Controller
{
    /**
     * Вывод данных одного расхода
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        $row = CashboxTransaction::find($request->id);

        if ($request->id and !$row)
            return response()->json(['message' => "Данные о раcходе не найдены"], 400);

        $response['row'] = $row ?? [];

        if ($request->modalData) {

            $response['types'] = ExpenseType::orderBy('name')
                ->get()
                ->map(function ($row) {
                    return $row->only('name', 'id');
                })
                ->toArray();
        }

        return response()->json($response);
    }

    /**
     * Сохраняет или создает данные о расходе
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        if (!$row = CashboxTransaction::find($request->id))
            $row = new CashboxTransaction;

        if ($request->id != $row->id)
            return response()->json(['message' => "Данные о расходе не найдены"], 400);

        $request->validate([
            'sum' => "required|numeric",
            'name' => "required_without:expense_subtype_id",
            'expense_type_id' => "required|numeric",
        ]);

        $sum = (float) $request->sum;

        if ($sum > 0)
            $sum *= -1;

        $row->name = $request->name;
        $row->sum = $sum;
        $row->is_expense = true;
        $row->expense_type_id = $request->expense_type_id;
        $row->expense_subtype_id = $request->expense_subtype_id;
        $row->date = now()->format("Y-m-d");

        $row->save();

        return response()->json([
            'row' => $row,
        ]);
    }
}
