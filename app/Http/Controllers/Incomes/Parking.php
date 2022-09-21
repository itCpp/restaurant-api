<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
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
        $row->parking = IncomeSourceParking::whereSourceId($row->id)
            ->get()
            ->map(function ($row) {
                return $this->parking($row);
            })
            ->toArray();

        return $row;
    }

    /**
     * Формиурет строку парковочного место
     * 
     * @param  \App\Models\IncomeSourceParking $row
     * @return \App\Models\IncomeSourceParking
     */
    public function parking(IncomeSourceParking $row)
    {
        return $row;
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
