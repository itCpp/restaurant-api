<?php

namespace App\Http\Controllers\Cashbox;

use App\Http\Controllers\Controller;
use App\Models\CashboxTransaction;
use Illuminate\Http\Request;

class Info extends Controller
{
    /**
     * Выводит информацию по кассе
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        CashboxTransaction::selectRaw('sum(sum) sum, IFNULL(type_pay, 1) type_pay')
            ->groupBy('type_pay')
            ->get()
            ->each(function ($row) use (&$data) {

                $key = (int) $row->type_pay;

                if (!isset($data[$key])) {
                    $data[$key] = [
                        'sum' => 0,
                        'type_pay' => $key,
                    ];
                }

                $data[$key]['sum'] += $row->sum;
            });

        return response()->json([
            'info' => collect($data ?? [])
                ->map(function ($row) {
                    $row['sum'] = round($row['sum'], 2);
                    return $row;
                })
                ->sortBy('type_pay')
                ->values()
                ->all()
        ]);
    }
}
