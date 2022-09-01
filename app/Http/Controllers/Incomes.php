<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Incomes\Sources;
use App\Models\CashboxTransaction;
use App\Models\IncomePart;
use App\Models\IncomeSource;
use Illuminate\Http\Request;

class Incomes extends Controller
{
    /**
     * Вывод данных на главную страницу
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $rows = IncomePart::get()
            ->map(function ($row) {

                $row->rows = IncomeSource::wherePartId($row->id)
                    ->orderBy('cabinet')
                    ->get()
                    ->map(function ($row) {
                        return $this->getIncomeSourceRow($row);
                    });

                return $row;
            })
            ->toArray();

        return response()->json([
            'rows' => $rows,
        ]);
    }

    /**
     * Формирует данные помещения
     * 
     * @param  \App\Models\IncomeSource $row
     * @return \App\Models\IncomeSource
     */
    public function getIncomeSourceRow(IncomeSource $row)
    {
        $row->last = CashboxTransaction::whereIncomeSourceId($row->id)
            ->orderBy('date', "DESC")
            ->first();

        if ($row->last and $row->date) {
            $row->overdue = now() > now()->create($row->last->date)->addMonth();
        }

        return $row;
    }

    /**
     * Выводит данные для внесения строки дохода
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
        return response()->json([
            'parts' => IncomePart::lazy()->toArray(),
            'sources' => $request->income_part_id ? Sources::getSourcesListForPart($request->income_part_id) : [],
        ]);
    }

    /**
     * Созраняет данные строки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        $request->validate([
            'sum' => "required|numeric",
            'income_part_id' => "required|integer",
            'income_source_id' => "required|integer",
        ]);

        if (!$source = IncomeSource::find($request->income_source_id)) {

            return response()->json([
                'message' => "Информация о помещении не найдена",
                'errors' => [
                    'income_source_id' => ["Информация о помещении не найдена"],
                ],
            ], 400);
        }

        if ($source->part_id != $request->income_part_id)
            $request->income_part_id = $source->part_id;

        if (!$row = CashboxTransaction::find($request->id))
            $row = new CashboxTransaction;

        $row->sum = $request->sum;
        $row->type_pay = $request->type_pay;
        $row->is_income = true;
        $row->income_part_id = $request->income_part_id;
        $row->income_source_id = $source->id;
        $row->date = $request->date ?: now()->format("Y-m-d");
        $row->month = $request->month ?: now()->format("Y-m");
        $row->user_id = $request->user()->id;

        $row->save();

        return response()->json([
            'row' => $row,
            'source' => $this->getIncomeSourceRow($source),
        ]);
    }
}
