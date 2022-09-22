<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
use App\Models\CashboxTransaction;
use App\Models\IncomeSource;
use App\Models\IncomeSourceParking;
use App\Models\Log;
use Illuminate\Http\Request;

class Parking extends Controller
{
    /**
     * Выводит парковочные данные
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $rows = IncomeSource::where('is_parking', true)
            ->orWhere('part_id', null)
            ->get()
            ->map(function ($row) {
                return $this->source($row);
            })
            ->toArray();

        return response()->json([
            'rows' => $rows,
        ]);
    }

    /**
     * Формиурет строку арендатора
     * 
     * @param  \App\Models\IncomeSource $row
     * @return \App\Models\IncomeSource
     */
    public function source(IncomeSource $row)
    {
        $row->parking = $this->getParkingList($row->id);

        return $row;
    }

    /**
     * Выводит строки арендуемых машиномест
     * 
     * @param  int $source_id
     * @return array
     */
    public function getParkingList($source_id)
    {
        return IncomeSourceParking::whereSourceId($source_id)
            ->get()
            ->map(function ($row) {
                return $this->parking($row);
            })
            ->sortBy('parking_place', SORT_NATURAL)
            ->values()
            ->all();
    }

    /**
     * Формиурет строку парковочного место
     * 
     * @param  \App\Models\IncomeSourceParking $row
     * @return \App\Models\IncomeSourceParking
     */
    public function parking(IncomeSourceParking $row)
    {
        $row->is_overdue = $this->isOverdue($row);

        return $row;
    }

    /**
     * Определение просроченного платежа парковки
     * 
     * @param  \App\Models\IncomeSourceParking $row
     * @return boolean
     */
    public function isOverdue(IncomeSourceParking &$row)
    {
        $date_start = now()->create($row->date_from);
        $date_end = now()->create($row->date_to ?? now());

        $pays = [];

        while ($date_start->copy()->format("Y-m") <= $date_end->copy()->format("Y-m")) {

            $month = $date_start->copy()->format("Y-m");

            $pays[$month] = [
                'month' => $month,
                'rows' => [],
            ];

            $date_start->addMonth();
        }

        CashboxTransaction::where('income_source_id', $row->source_id)
            ->where('income_source_parking_id', $row->id)
            ->orderBy('date', "DESC")
            ->get()
            ->each(function ($row) use (&$pays) {
                $pays[$row->month]['rows'][] = $row;
            });

        $row->pays = collect($pays)->sortKeysDesc()
            ->map(function ($row, $key) {
                return [
                    'month' => $key,
                    'rows' => $row['rows'] ?? [],
                ];
            })
            ->values()
            ->all();

        $overdue = false;
        $now_month = now()->format("Y-m");

        foreach ($row->pays as $pay) {

            if ($pay['month'] === $now_month and !count($pay['rows']))
                $overdue = true;
            else if (!count($pay['rows']))
                $overdue = true;
        }

        $row->last_pay = $row->pays[0]['rows'][0] ?? null;

        return $overdue;
    }

    /**
     * Выводит данные одной строки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        if ($request->id and !$row = IncomeSourceParking::find($request->id))
            return response()->json(['message' => "Информация о машиномете не найдена"], 400);

        if (!$request->source_id) {
            $sources = IncomeSource::where('is_parking', true)
                ->orWhere('part_id', null)
                ->get()
                ->map(function ($row) {
                    return [
                        'value' => $row->id,
                        'text' => $row->name,
                        'key' => $row->id,
                    ];
                })
                ->toArray();
        }

        return response()->json([
            'row' => $row ?? null,
            'sources' => $sources ?? null,
        ]);
    }

    /**
     * Сохранение данных машиноместа
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        $row = IncomeSourceParking::find($request->id);

        if ($request->id and !$row)
            return response()->json(['message' => "Информации о машиноместе не найдено"], 400);

        $request->validate([
            'source_id' => "required|integer",
            'parking_place' => "required",
            'date_from' => "required|date",
            'price' => "required|numeric",
        ]);

        if (!$row)
            $row = new IncomeSourceParking;

        $row->source_id = $request->source_id;
        $row->parking_place = $request->parking_place;
        $row->car = $request->car;
        $row->car_number = $request->car_number;
        $row->date_from = $request->date_from;
        $row->date_to = $request->date_to;
        $row->price = (float) $request->price;
        $row->owner_name = $request->owner_name;
        $row->owner_phone = $request->owner_phone;
        $row->comment = $request->comment;

        $row->save();

        Log::write($row, $request);

        return response()->json([
            'source_id' => $request->source_id,
            'row' => $this->parking($row),
        ]);
    }
}
