<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Incomes;
use App\Models\CashboxTransaction;
use App\Models\IncomeSource;
use App\Models\OverdueException;
use Illuminate\Http\Request;

class Pays extends Controller
{
    /**
     * Идентификатор вывода данных в виде массива
     * 
     * @var boolean
     */
    protected $toArray = false;

    /**
     * Выводит данные о выплатах
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\IncomeSource|null $source
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $source = null)
    {
        if ($source instanceof IncomeSource) {
            $this->toArray = true;
        } else if (!$source) {
            $source = IncomeSource::find($request->source_id);
        }

        if (!($source instanceof IncomeSource))
            $source = new IncomeSource;

        $this->source = $source;
        $this->source->parking = (new Parking)->getParkingList($source->id);
        $this->incomes = new Incomes;
        $this->purposes = Purposes::collect();

        /** Идентификатор арендатора */
        $source_id = $source->id ?? $request->source_id;

        $this->data = [];

        /** Формирование таблицы месяцев */
        $this->getMonthsTable();

        /** Поиск скрытых просроченных оплат */
        $this->getHiddenOverdues($source_id);

        /** Существующая оплата в кассе */
        $rows = CashboxTransaction::whereIsIncome(true)
            ->when((bool) $source_id, function ($query) use ($source_id) {
                $query->whereIncomeSourceId($source_id);
            })
            ->when((bool) $source->date ?? null, function ($query) use ($source) {
                $query->where('date', '>=', $source->date);
            })
            ->when((bool) $source->date_to ?? null, function ($query) use ($source) {
                $query->where('date', '<=', $source->date_to);
            })
            ->orderBy('id', 'DESC')
            ->get()
            ->map(function ($row) {

                $row = $this->incomes->getRowCashboxTransaction($row);

                $this->data[$row->month][] = $row;

                return $row;
            });

        $response_data = $this->getMonthsPaysData($this->data);

        if ($this->toArray)
            return $response_data ?? [];

        return response()->json([
            'row' => $source ?? [],
            'rows' => $rows ?? [],
            'data' => $response_data ?? [],
            'months' => $this->months ?? [],
        ]);
    }

    /**
     * Формирование итоговой таблицы выплат
     * 
     * @param  array $data
     * @return array
     */
    public function getMonthsPaysData($data = [])
    {
        return collect($data ?? [])->sortKeysDesc()
            ->map(function ($rows, $key) {

                $day_x = $this->day_x;

                /** Смещение даты оплаты, если аренда началась позже */
                if (now()->create($key)->setDay($day_x) < now()->create($this->source->date))
                    $day_x = (int) now()->create($this->source->date)->format("d");

                /** Дата оплаты */
                $date_x = now()->create($key)->setDay($day_x)->format("Y-m-d");

                $is_rent = false; # Оплата аренды
                $is_parking = false; # Оплата парковки
                $parking_id = []; # Идентификаторы оплаченных парковок
                $is_internet = false; # Оплата интернета

                /** Проверка существующих типов оплаты */
                foreach ($rows as $row) {

                    if ($row->purpose_pay == 1)
                        $is_rent = true;

                    if ($row->purpose_pay == 2) {
                        $is_parking = true;
                        $parking_id[] = $row->income_source_parking_id;
                    }

                    if ($row->purpose_pay == 5)
                        $is_internet = true;
                }

                /** Проверка необходимости оплаты аренды */
                if ($this->source->is_rent and !$is_rent) {
                    $pay_sum = (float) $this->source->space * (float) $this->source->price;
                    $rows[] = $this->getEmptyRow($this->source->id, 1, $key, $day_x, $pay_sum);
                }

                /** Проверка оплаченных парковок */
                if ($this->source->is_parking and !$is_parking) {
                    $this->appendNotPayParkingList($rows, $key, $day_x);
                }

                return [
                    'month' => $key,
                    'rows' => $rows,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Формирует таблицу оплат
     * 
     * @return array
     */
    public function getMonthsTable()
    {
        /** Начальная дата */
        $this->date_check = now()->create($this->source->date ?? now());

        /** День оплаты */
        $this->day_x = $this->source->settings['pay_day'] ?? 20;

        /** Крайняя дата определения оплаты */
        $this->month_end = request()->toLastMonth
            ? now()->format("Y-m")
            : ((int) now()->format("d") >= $this->day_x
                ? now()->format("Y-m")
                : now()->subMonth()->format("Y-m"));

        $this->months = [];

        while ($this->date_check->format("Y-m") <= $this->month_end) {

            $month = $this->date_check->format("Y-m");

            if (!isset($this->data[$month])) {
                $this->data[$month] = [];
            }

            $this->months[] = $month;
            $this->date_check->addMonth();
        }

        return $this->months;
    }

    /**
     * Выводит скрытые просроченные выплаты
     * 
     * @param  int $source_id
     * @return array
     */
    public function getHiddenOverdues($source_id)
    {
        $this->hide_overdues = [];

        OverdueException::where('source_id', $source_id)
            ->whereIn('month', $this->months ?? [])
            ->get()
            ->each(function ($row) {
                $this->hide_overdues[$row->month][$row->purpose_id] = true;
            });

        if ((int) now()->format("d") < ($this->day_x ?? null)) {

            $month = now()->format("Y-m");

            foreach ($this->purposes as $p)
                $this->hide_overdues[$month][$p['id']] = true;
        }

        return $this->hide_overdues;
    }

    /**
     * Формирует строку несуществующей оплаты
     * 
     * @param  int $source_id
     * @param  int $purpose_pay
     * @param  string|null $month
     * @param  string|null $day_x
     * @param  int $pay_sum
     * @return \App\Models\CashboxTransaction
     */
    public function getEmptyPayRow($source_id, $purpose_pay, $month = null, $day_x = null, $pay_sum = 0)
    {
        $row = new CashboxTransaction;

        $row->income_source_id = $source_id;
        $row->purpose_pay = $purpose_pay;
        $row->pay_sum = $pay_sum;
        $row->date = now()->create($month ?: now())->setDay($day_x ?: 20)->format("Y-m-d");

        return $this->incomes->getRowCashboxTransaction($row);
    }

    /**
     * Добавляет список неплаченных парковок
     * 
     * @param  array $rows
     * @param  string|null $month
     * @param  string|null $day_x
     * @return array
     */
    public function appendNotPayParkingList(&$rows, $month, $day_x)
    {
        return $rows;
    }
}
