<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Incomes\Purposes;
use App\Http\Controllers\Incomes\Sources;
use App\Models\CashboxTransaction;
use App\Models\IncomePart;
use App\Models\IncomeSource;
use App\Models\IncomeSourceLog;
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
        $rows = IncomePart::whereBuildingId($request->id)
            ->get()
            ->map(function ($row) {

                $row->rows = $this->getSourcesPart($row->id);

                return $row;
            })
            ->toArray();

        return response()->json([
            'rows' => $rows,
        ]);
    }

    /**
     * Выводит строки источников
     * 
     * @param  int $id
     * @return array
     */
    public function getSourcesPart($id)
    {
        return IncomeSource::wherePartId($id)
            ->orderBy('cabinet')
            ->get()
            ->map(function ($row) {
                return $this->getIncomeSourceRow($row);
            })
            ->sortBy('to_sort')
            ->values()
            ->all();
    }

    /**
     * Формирует данные помещения
     * 
     * @param  \App\Models\IncomeSource $row
     * @return \App\Models\IncomeSource
     */
    public function getIncomeSourceRow(IncomeSource $row)
    {
        return (new Sources)->getIncomeSourceRow($row);
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
            'purposes' => Purposes::getAll(),
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
            'purpose_pay' => "required|integer",
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
        $row->purpose_pay = $request->purpose_pay;
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

    /**
     * Вывод строк выплаты
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function view(Request $request)
    {
        $rows = CashboxTransaction::whereIsIncome(true)
            ->when((bool) $request->source_id, function ($query) use ($request) {
                $query->whereIncomeSourceId($request->source_id);
            })
            ->orderBy('id', 'DESC')
            ->get()
            ->map(function ($row) {

                $row->source = $this->getSourceInfo($row);

                return $row;
            });

        return response()->json([
            'rows' => $rows,
            $this->get_source_info ?? [],
        ]);
    }

    /**
     * Выводит информацию об источнике
     * 
     * @param  \App\Models\CashboxTransaction $row
     * @return \App\Models\IncomeSource|null
     */
    public function getSourceInfo($row)
    {
        $id = $row->income_source_id;

        if (empty($this->get_source_info))
            $this->get_source_info = [];

        if (empty($this->get_source_info[$id]))
            $this->get_source_info[$id] = [];

        if (!count($this->get_source_info[$id])) {

            $log = $this->getIncomeSourceLog($id, $row->date);

            if ($log)
                return $this->get_source_info[$id][$log->change_date->format("Y-m-d")] = $log->source_data;
        }

        foreach ($this->get_source_info[$id] as $date => $source) {

            if ($date <= $row->date)
                return $source;
        }

        if ($log = $this->getIncomeSourceLog($id, $row->date)) {
            return $this->get_source_info[$id][$log->change_date->format("Y-m-d")] = $log->source_data;
        }

        return $this->get_source_info[$id][now()->format("Y-m-d")] = IncomeSource::find($id);
    }

    /**
     * Возвращает строку источника дохода из истории изменений
     * 
     * @param  int $id
     * @param  string $date
     * @return \App\Models\IncomeSourceLog|null
     */
    public function getIncomeSourceLog($id, $date)
    {
        return IncomeSourceLog::whereSourceId($id)
            ->where('change_date', '>', $date)
            ->first();
    }
}
