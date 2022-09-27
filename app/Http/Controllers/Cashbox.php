<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Incomes\Purposes;
use App\Models\CashboxTransaction;
use App\Models\IncomeSource;
use App\Models\IncomeSourceParking;
use Illuminate\Http\Request;

class Cashbox extends Controller
{
    /**
     * Вывод данных на главной странице
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $data = CashboxTransaction::orderBy('date', "DESC")
            ->paginate(50);

        $rows = $data->map(function ($row) {
            return $this->row($row);
        });

        return response()->json([
            'rows' => $rows ?? [],
        ]);
    }

    /**
     * Формирование данных одной строки
     * 
     * @param  \App\Models\CashboxTransaction $row
     * @return \App\Models\CashboxTransaction
     */
    public function row(CashboxTransaction $row)
    {
        if ($row->is_income)
            return $this->incomeRow($row);
        else if ($row->is_expense)
            return $this->expenseRow($row);

        return $row;
    }

    /**
     * Формирование данных строки прихода
     * 
     * @param  \App\Models\CashboxTransaction $row
     * @return \App\Models\CashboxTransaction
     */
    public function incomeRow(CashboxTransaction $row)
    {
        $row->source = $this->getSource($row->income_source_id);

        $row->name = $row->source->name ?? null;

        $purpose = Purposes::collect()->where('id', $row->purpose_pay)->values()->all()[0] ?? null;

        $row->parking = $this->getParking($row->income_source_parking_id);

        if ($row->parking and $purpose) {
            $purpose['name'] = $purpose['name'] . " " . $row->parking->parking_place;
        }

        $row->purpose = $purpose;

        return $row;
    }

    /**
     * Формирование данных строки расхода
     * 
     * @param  \App\Models\CashboxTransaction $row
     * @return \App\Models\CashboxTransaction
     */
    public function expenseRow(CashboxTransaction $row)
    {
        return $row;
    }

    /**
     * Поиск источников дохода
     * 
     * @param  int $id
     * @return \App\Models\IncomeSource|null
     */
    public function getSource($id)
    {
        if (empty($this->get_source))
            $this->get_source = [];

        if (isset($this->get_source[$id]))
            return $this->get_source[$id];

        return $this->get_source[$id] = IncomeSource::find($id);
    }

    /**
     * Поиск источников дохода
     * 
     * @param  int $id
     * @return \App\Models\IncomeSourceParking|null
     */
    public function getParking($id)
    {
        if (!$id)
            return null;

        if (empty($this->get_parking))
            $this->get_parking = [];

        if (isset($this->get_parking[$id]))
            return $this->get_parking[$id];

        return $this->get_parking[$id] = IncomeSourceParking::find($id);
    }
}
