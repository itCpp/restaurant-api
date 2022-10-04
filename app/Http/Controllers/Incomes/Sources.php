<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Incomes;
use App\Http\Controllers\Tenants\AdditionalServices;
use App\Models\CashboxTransaction;
use App\Models\IncomePart;
use App\Models\IncomesFile;
use App\Models\IncomeSource;
use App\Models\IncomeSourceLog;
use App\Models\Log;
use App\Models\PayFine;
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

        if ($row->last and $row->date) {
            if (now()->create($row->date)->startOfDay() > now()->create($row->last->date ?: now())->startOfDay()) {
                $row->last->is_prev = true;
            }
        }

        /** Дополнительные услуги */
        $row->services = $row->services;

        /** Количество прикрепленных файлов */
        $row->files_count = IncomesFile::whereIncomeId($row->id)->count();

        /** Оплаченный депозит */
        if (($row->is_deposit ?? null) === null)
            $row->is_deposit = $this->getFixedPay($row->id, 3);

        /** Оплаченный юридический адрес */
        if (($row->is_legal_address ?? null) === null)
            $row->is_legal_address = $this->getFixedPay($row->id, 4);

        /** Имеется просроченный платеж */
        $row->overdue = $this->checkOverdueAndFindNextPays($row);

        $row->to_sort = (int) $row->cabinet == trim($row->cabinet)
            ? (int) $row->cabinet : $row->cabinet;

        $row->fine = PayFine::where('source_id', $row->id)
            ->when((bool) $row->date, function ($query) use ($row) {
                $query->where('date', '>=', $row->date);
            })
            ->when((bool) $row->date_to, function ($query) use ($row) {
                $query->where('date', '<=', $row->date_to);
            })
            ->sum('sum');

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
        $last_every_month = $last_months = [];

        /** Следующие платежи */
        $next_pays = [];

        /** День оплаты по договору */
        $pay_day = $pay_day = $row->settings['pay_day'] ?? false;
        $row->pay_day = $pay_day ?: 20;

        /** Предельная дата оплаты по договору */
        $date_last_to = (int) now()->format('d') >= $row->pay_day
            ? now()->addMonth()->setDay($row->pay_day)->format("Y-m-d")
            : now()->setDay($row->pay_day)->format("Y-m-d");

        /** Последние платежи по ежемесячным расчетам */
        foreach (Purposes::getEveryMonthId() as $v) {

            $last = CashboxTransaction::whereIncomeSourceId($row->id)
                ->wherePurposePay($v)
                // ->where('date', '>=', $row->date ?? now())
                ->where('date', '<=', $date_last_to)
                ->orderBy('month', "DESC")
                ->first();

            if (!$last)
                continue;

            $last_every_month[] = $last;
            $last_months["p{$v}"][] = now()->create($last->month ?: $last->date)->format("Y-m");
        }

        $row->last_every_month = $last_every_month;
        $row->last_months = $last_months;

        if ($row->is_free)
            return false;

        $date = now()->setDay($row->pay_day);
        $next_date = $date->copy();

        if ($date > now())
            $date->subMonth();

        if ($next_date < now())
            $next_date->addMonth();

        $check_month = $next_date->copy()->format("Y-m");

        if (!in_array($check_month, $last_months["p1"] ?? [])) {
            $next_pays[] = [
                'date' => $next_date,
                'price' => round(((float) $row->price * (float) $row->space), 2),
                'type' => 1,
                'icon' => "building",
                'title' => "Аренда помещения",
            ];
        }

        if ($row->is_parking) {
            self::findNextPaysParking($row, $next_pays, $next_date);
        }

        if ($row->is_internet and !in_array($check_month, $last_months["p5"] ?? [])) {
            $next_pays[] = [
                'date' => $next_date,
                'price' => $row->settings['internet_price'] ?? 0,
                'type' => 5,
                'icon' => "internet explorer",
                'title' => "Интернет услуги",
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
     * Добавляет данные по следующим платежам аренды парковок
     * 
     * @param  \App\Models\IncomeSource $row
     * @param  array $data
     * @param  string $date
     * @return array
     */
    public static function findNextPaysParking(&$row, &$data, $date)
    {
        if (!($row->parking ?? null))
            $row->parking = (new Parking)->getParkingList($row->id);

        $parkings_id = collect($row->parking)->map(function ($row) {
            return $row->id;
        })->toArray();

        $paid = CashboxTransaction::whereIn('income_source_parking_id', $parkings_id)
            ->whereBetween('date', [
                now()->create($date)->subMonth(),
                now()->create($date),
            ])
            ->get()
            ->map(function ($row) {
                return $row->income_source_parking_id;
            })
            ->toArray();

        foreach ($row->parking as $parking) {

            if (in_array($parking->id, $paid))
                continue;

            $data[] = [
                'date' => $date,
                'price' => $parking->price,
                'type' => 2,
                'icon' => "car",
                'title' => "Аренда парковки " . $parking->parking_place,
                'income_source_parking_id' => $parking->id,
            ];
        }

        return $data;
    }

    /**
     * Получает наличие фиксированного платежа
     * 
     * @param  int  $source_id
     * @param  int  $purpose_id
     * @return bool
     */
    public static function getFixedPay($source_id, $purpose_id)
    {
        return (bool) CashboxTransaction::whereIncomeSourceId($source_id)
            ->wherePurposePay($purpose_id)
            ->count();
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

        /** Логирование до изменения! */
        if ($row)
            IncomeSourceLog::log($request, $row);
        else
            $row = new IncomeSource;

        $request->validate([
            'inn' => "nullable|string",
        ]);

        $row->part_id = $request->part_id;
        $row->name = $request->name;
        $row->inn = $request->inn;
        $row->contact_person = $request->contact_person;
        $row->contact_number = $request->contact_number;
        $row->space = (float) $request->space;
        $row->cabinet = $request->cabinet;
        $row->price = $request->price;
        $row->date = $request->date;
        $row->date_to = $request->date_to;
        $row->is_free = (bool) $request->is_free;
        $row->is_parking = (bool) $request->is_parking;
        $row->is_internet = (bool) $request->is_internet;
        $row->settings = $request->settings ?? [];
        $row->is_overdue = (bool) (new Incomes)->isOverdue($row);
        $row->is_rent = (bool) $request->is_rent;

        $row->save();

        Log::write($row, $request);

        $row = $request->toParking ? (new Parking)->source($row) : $this->getIncomeSourceRow($row);

        return response()->json([
            'row' => $row,
            'pays' => $request->toPays ? (new Incomes)->view($request, $row) : [],
        ]);
    }
}
