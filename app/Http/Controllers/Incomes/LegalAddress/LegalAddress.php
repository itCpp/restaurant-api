<?php

namespace App\Http\Controllers\Incomes\LegalAddress;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Incomes\Sources;
use App\Models\CashboxTransaction;
use App\Models\IncomeSource;
use Illuminate\Http\Request;

class LegalAddress extends Controller
{
    /**
     * Выводит арендаторов с оплаченными юридическими адресами
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json(
            $this->getRows($request),
        );
    }

    /**
     * Формирует данные для вывода арендаторов с юридическими адресами
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function getRows(Request $request)
    {
        $rows = IncomeSource::withTrashed()
            ->select('income_sources.*')
            ->join('cashbox_transactions', function ($join) {
                $join->on('cashbox_transactions.income_source_id', '=', 'income_sources.id')
                    ->where('cashbox_transactions.purpose_pay', 4)
                    ->where('cashbox_transactions.deleted_at', null);
            })
            ->where('income_sources.deleted_at', null)
            ->orderBy('part_id')
            ->orderBy('cabinet')
            ->get()
            ->map(function ($row) {
                
                $row = (new Sources)->getIncomeSourceRow($row);
                $row->part = $row->load('part');

                return $row;
            })
            ->sortBy([
                ['part_id', 'asc'],
                ['cabinet', 'asc'],
            ], SORT_NATURAL)
            ->values()
            ->all();

        return [
            'rows' => $rows,
        ];
    }
}
