<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Incomes;
use App\Models\CashboxTransaction;
use App\Models\IncomePart;
use App\Models\IncomesFile;
use App\Models\IncomeSource;
use App\Models\IncomeSourceLog;
use Illuminate\Http\Request;

class Sources extends Controller
{
    /**
     * Выводит список помещений для выбора источника дохода
     * 
     * @param  \Illuminate\Htpp\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json(
            $this->getSourcesListForPart($request->part_id)
        );
    }

    /**
     * Выводит список помещений по идентификатору раздела
     * 
     * @param  int $id
     * @return array
     */
    public static function getSourcesListForPart($id)
    {
        return IncomeSource::wherePartId($id)->lazy()->toArray();
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

        $row->files_count = IncomesFile::whereIncomeId($row->id)->count();

        $row->overdue = $this->checkOverdueAndFindNextPays($row);

        $row->to_sort = (int) $row->cabinet == trim($row->cabinet)
            ? (int) $row->cabinet : $row->cabinet;

        return $row;
    }

    /**
     * Проверяет просрочку оплаты и определяет даты следующих платежей
     * 
     * @param  \App\Models\IncomeSource $row
     * @return bool
     */
    public static function checkOverdueAndFindNextPays(&$row)
    {
        /** Ежемесячные платежи */
        $last_every_month = [];

        /** Следующие платежи */
        $next_pays = [];

        foreach (Purposes::getEveryMonthId() as $value) {

            $last = CashboxTransaction::whereIncomeSourceId($row->id)
                ->wherePurposePay($value)
                ->orderBy('date', "DESC")
                ->first();

            if (!$last)
                continue;

            $last_every_month[] = $last;
        }

        $row->last_every_month = $last_every_month;
        $row->pay_day = $pay_day = $row->settings['pay_day'] ?? false;

        if ($row->is_free)
            return false;

        $date = now()->setDay($pay_day ?: 20);
        $next_date = $date->copy();

        if ($date > now())
            $date->subMonth();

        if ($next_date < now())
            $next_date->addMonth();

        $next_pays[] = [
            'date' => $next_date,
            'price' => round(((float) $row->price * (float) $row->space), 2),
            'type' => 1,
            'icon' => "building",
        ];

        if ($row->is_parking) {
            $next_pays[] = [
                'date' => $next_date,
                'price' => $row->settings['parking_price'] ?? 0,
                'type' => 2,
                'icon' => "car",
                'title' => "Аренда парковки",
            ];
        }

        $row->next_pays = $next_pays;

        $overdue = false;

        foreach ($last_every_month as $pay) {

            if ($date > now()->create($pay->date)->addMonth())
                $overdue = true;
        }

        return $overdue;
    }

    /**
     * Данные источника дохода
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        $row = IncomeSource::find($request->id);

        if (!$request->getParts and !$row)
            return response()->json(['message' => "Данные не найдены"], 400);

        return response()->json([
            'row' => $row ?? [],
            'parts' => $this->getPartList($request),
        ]);
    }

    /**
     * Выводит список разделов для здания
     * 
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function getPartList(Request $request)
    {
        return $request->getParts ? IncomePart::when((bool) $request->building, function ($query) use ($request) {
            $query->whereBuildingId($request->building);
        })->lazy()->toArray() : [];
    }

    /**
     * Создание или изменение данных помещения
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        $row = IncomeSource::find($request->id);

        if ($request->id and !$row)
            return response()->json(['message' => "Данные о помещении не найдены"], 400);

        if ($row)
            IncomeSourceLog::log($request, $row);
        else
            $row = new IncomeSource;

        $row->part_id = $request->part_id;
        $row->name = $request->name;
        $row->inn = $request->inn;
        $row->contact_person = $request->contact_person;
        $row->contact_number = $request->contact_number;
        $row->space = $request->space;
        $row->cabinet = $request->cabinet;
        $row->price = $request->price;
        $row->date = $request->date;
        $row->date_to = $request->date_to;
        $row->is_free = (bool) $request->is_free;
        $row->is_parking = (bool) $request->is_parking;
        $row->settings = $request->settings ?? [];

        $row->save();

        return response()->json([
            'row' => $this->getIncomeSourceRow($row),
        ]);
    }
}
