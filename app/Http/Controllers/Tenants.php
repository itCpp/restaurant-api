<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Incomes\Files;
use App\Http\Controllers\Incomes\Parking;
use App\Http\Controllers\Incomes\Purposes;
use App\Http\Controllers\Incomes\Sources;
use App\Models\CashboxTransaction;
use App\Models\IncomePart;
use App\Models\IncomeSource;
use App\Models\Log;
use Illuminate\Http\Request;

class Tenants extends Controller
{
    /**
     * Основные данные арендатора
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        if (!$row = IncomeSource::find($request->id))
            return response()->json(['message' => "Данные по арендатору не найдены"], 400);

        $request->merge([
            'income' => true,
            // 'toLastMonth' => true,
        ]);

        $row = (new Sources)->getIncomeSourceRow($row);

        $row->is_deposit = $this->checkPurposeTypePay(3, $row);
        $row->is_legal_address = $this->checkPurposeTypePay(4, $row);

        $row->parking = (new Parking)->getParkingList($row->id);

        $pays = (new Incomes)->view($request, $row);

        $files = (new Files($request))->getFilesList($request);

        return response()->json([
            'pays' => $pays,
            'row' => $row,
            'files' => $files,
            'purposes' => Purposes::getAll(),
        ]);
    }

    /**
     * Определяет наличие платежа
     * 
     * @param  int $purpose_id
     * @param  \App\Models\CashboxTransaction $source
     * @return boolean
     */
    public function checkPurposeTypePay($purpose_id, $source)
    {
        return (bool) CashboxTransaction::whereIsIncome(true)
            ->whereIncomeSourceId($source->id)
            ->wherePurposePay($purpose_id)
            ->when((bool) $source->date, function ($query) use ($source) {
                $query->where('date', '>=', $source->date);
            })
            ->when((bool) $source->date_to, function ($query) use ($source) {
                $query->where('date', '>=', $source->date_to);
            })
            ->count();
    }

    /**
     * Отправка в архив
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function drop(Request $request)
    {
        if (!$row = IncomeSource::find($request->id))
            return response()->json(['message' => "Данные по арендатору не найдены"], 400);

        $row->delete();

        Log::write($row, $request);

        $part = IncomePart::find($row->part_id);

        return response()->json([
            'buildingId' => $part->building_id ?? null,
        ]);
    }
}
