<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
use App\Models\CashboxTransaction;
use App\Models\IncomesFile;
use App\Models\IncomeSource;
use App\Models\IncomeSourceParking;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;

class Parking extends Controller
{
    /**
     * Выводит парковочные данные
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $toArray
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $toArray = false)
    {
        $rows = IncomeSource::where('is_parking', true)
            ->orWhere('part_id', null)
            ->get()
            ->map(function ($row) {
                return $this->source($row);
            });

        if ($toArray)
            return $rows;

        return response()->json([
            'rows' => $rows->toArray(),
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

        $row->files = IncomesFile::where('income_id', $row->id)->count();

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
        $date_end = $row->date_to ? now()->create($row->date_to) : now();

        $pays = [];
        $all_months = [];

        while ($date_start->copy()->format("Y-m") <= $date_end->copy()->format("Y-m")) {

            $month = $date_start->copy()->format("Y-m");

            $pays[$month] = [
                'month' => $month,
                'rows' => [],
            ];

            $all_months[] = $month;

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
        $last_pay = null;
        $date_x = now()->setDay($row->settings['pay_day'] ?? 20);

        foreach ($row->pays as $pay) {

            if (count($pay['rows']))
                $all_months[] = $pay['month'];

            $date_x_check = now()->create($pay['month'])->setDay($row->settings['pay_day'] ?? 20);

            if (now() > $date_x_check and $pay['month'] === $now_month and !count($pay['rows']))
                $overdue = true;
            else if (now() > $date_x_check and !count($pay['rows']))
                $overdue = true;

            if ((bool) $last_pay)
                continue;

            $last_pay = $pay['rows'][0] ?? null;
        }

        $this->all_months = array_values(array_unique($all_months));

        $row->last_pay = $last_pay;
        $row->next_pay = $this->getNextPay($row);

        if (!(int) $row->price) {
            $row->next_pay = null;
            $overdue = false;
        }

        /** Проверка просрочки до окончания аренды */
        if ($row->date_to and $row->next_pay) {

            if (now()->create($row->date_to) < now()->create($row->next_pay->date))
                $row->next_pay = null;
        }

        return $overdue;
    }

    /**
     * Определяет следующий платеж
     * 
     * @param  \App\Models\IncomeSourceParking $row
     * @return \App\Models\CashboxTransaction
     */
    public function getNextPay(&$row)
    {
        $day = (int) now()->format("d");
        $next_month = now()->create($row->last_pay->date ?? now())->addMonth()->format("Y-m");
        $now_month = now()->format("Y-m");

        if ($day > 20 and !in_array($now_month, $this->all_months))
            return $this->getNextPayModel($row, day_x: false);

        if ($day > 20 and !in_array($next_month, $this->all_months))
            return $this->getNextPayModel($row, now()->diffInMonths(now()->create($next_month)) + 1);

        if ($day > 20 and $next_month == now()->addMonth()->format("Y-m"))
            return $this->getNextPayModel($row, 1);

        if ($day > 20 and in_array($next_month, $this->all_months))
            return $this->getNextPayModel($row, 2);

        if ($day <= 20 and in_array($now_month, $this->all_months))
            return $this->getNextPayModel($row, 1);

        return $this->getNextPayModel($row);
    }

    /**
     * Формирует объект модели следующего платежа
     * 
     * @param  \App\Models\IncomeSourceParking $row
     * @param  int $add_month
     * @param  boolean $day_x
     * @return \App\Models\CashboxTransaction
     */
    public function getNextPayModel($row, $add_month = 0, $day_x = true)
    {
        $d = $day_x ? "20" : "d";

        $next = new CashboxTransaction;
        $next->month = now()->addMonths($add_month)->format("Y-m");
        $next->date = now()->addMonths($add_month)->format("Y-m-$d");
        $next->income_source_id = $row->source_id;
        $next->income_source_parking_id = $row->id;
        $next->pay_sum = $row->price;

        return $next;
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

    /**
     * Сохранение платежа парковки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pay(Request $request)
    {
        if (!$row = IncomeSourceParking::find($request->parking_id))
            return response()->json(['message' => "Информация о парковке не найдена"], 400);

        $source = IncomeSource::find($row->source_id);

        $pay = new CashboxTransaction;
        $pay->sum = $request->sum;
        $pay->type_pay = $request->type_pay;
        $pay->purpose_pay = 2;
        $pay->is_income = true;
        $pay->income_part_id = $source->part_id ?? null;
        $pay->income_source_id = $row->source_id;
        $pay->income_source_parking_id = $row->id;
        $pay->income_source_parking_id = $row->id;
        $pay->date = $request->date;
        $pay->month = now()->create($request->date)->format("Y-m");
        $pay->user_id = $request->user()->id;

        $pay->save();

        Log::write($pay, $request);
        Artisan::call("pays:overdue {$row->source_id}");

        return response()->json([
            'row' => $this->parking($row),
        ]);
    }

    /**
     * Список парковочных мест
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        return response()->json(
            IncomeSourceParking::where('source_id', $request->id)
                ->get()
                ->map(function ($row) {
                    return [
                        'text' => "Парковка №{$row->parking_place} ($row->car)",
                        'value' => $row->id,
                    ];
                }),
        );
    }

    /**
     * Формирование документа со списком автомобилей
     * 
     * @param  \Illuminate\Http\Request  $requerst
     * @return mixed
     */
    public function docx(Request $requerst)
    {
        return (new ParkingDocGenerate)->generate($requerst);
    }
}
