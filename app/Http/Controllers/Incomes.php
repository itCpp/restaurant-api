<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Incomes\Parking;
use App\Http\Controllers\Incomes\Pays;
use App\Http\Controllers\Incomes\Purposes;
use App\Http\Controllers\Incomes\Sources;
use App\Models\CashboxTransaction;
use App\Models\IncomePart;
use App\Models\IncomeSource;
use App\Models\IncomeSourceLog;
use App\Models\IncomeSourceParking;
use App\Models\Log;
use App\Models\OverdueException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class Incomes extends Controller
{
    /**
     * Вывод данных на главную страницу
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if ($request->id == "parking")
            return (new Parking)->index($request);

        $rows = IncomePart::whereBuildingId($request->id)
            ->get()
            ->map(function ($row) {
                $row->rows = $this->getSourcesPart($row->id);
                return $row;
            })
            ->toArray();

        return response()->json([
            'rows' => $rows,
        ]);
    }

    /**
     * Выводит строки источников
     * 
     * @param  int $id
     * @param  boolean $is_parking
     * @return array
     */
    public function getSourcesPart($id, $is_parking = false)
    {
        return IncomeSource::wherePartId($id)
            ->when($is_parking, function ($query) {
                $query->where('is_parking', true);
            })
            ->orderBy('cabinet')
            ->get()
            ->map(function ($row) {
                return $this->getIncomeSourceRow($row);
            })
            ->sortBy('cabinet', SORT_NATURAL)
            ->values()
            ->all();
    }

    /**
     * Формирует данные помещения
     * 
     * @param  \App\Models\IncomeSource $row
     * @return \App\Models\IncomeSource
     */
    public function getIncomeSourceRow(IncomeSource $row)
    {
        return (new Sources)->getIncomeSourceRow($row);
    }

    /**
     * Выводит данные для внесения строки дохода
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
        return response()->json([
            'parts' => IncomePart::lazy()->toArray(),
            'purposes' => Purposes::getAll(),
            'sources' => $request->income_part_id ? Sources::getSourcesListForPart($request->income_part_id) : [],
        ]);
    }

    /**
     * Сохраняет данные строки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        $request->validate([
            'sum' => "required|numeric|not_in:0",
            'income_part_id' => "required|integer",
            'income_source_id' => "required|integer",
            'purpose_pay' => "required|integer",
        ]);

        if (!$source = IncomeSource::find($request->income_source_id)) {

            return response()->json([
                'message' => "Информация о помещении не найдена",
                'errors' => [
                    'income_source_id' => ["Информация о помещении не найдена"],
                ],
            ], 400);
        }

        if ($source->part_id != $request->income_part_id)
            $request->income_part_id = $source->part_id;

        if (!$row = CashboxTransaction::find($request->id))
            $row = new CashboxTransaction;

        $row->sum = abs($request->sum);
        $row->type_pay = $request->type_pay;
        $row->purpose_pay = $request->purpose_pay;
        $row->is_income = true;
        $row->income_part_id = $request->income_part_id;
        $row->income_source_id = $source->id;
        $row->income_source_parking_id = $request->parking_id;
        $row->income_source_service_id = $request->service_id; 
        $row->date = $request->date ?: now()->format("Y-m-d");
        $row->month = now()->create($row->date)->format("Y-m");
        $row->period_start = $request->period_start ?: now()->create($row->month)->startOfMonth()->format("Y-m-d");
        $row->period_stop = $request->period_stop ?: now()->create($row->month)->endOfMonth()->format("Y-m-d");
        $row->user_id = $request->user()->id;

        if (now()->create($row->date)->startOfDay() < now()->create($source->date)->startOfDay()) {
            $date_format = now()->create($source->date)->format("d.m.Y");
            return response()->json(['message' => "Дата платежа раньше даты начала аренды ({$date_format})"], 400);
        }

        $row->save();

        Log::write($row, $request);

        Artisan::call("pays:overdue {$row->income_source_id}");

        return response()->json([
            'row' => $row,
            'pay' => $this->getRowCashboxTransaction($row),
            'source' => $this->getIncomeSourceRow($source),
        ]);
    }

    /**
     * Проверяет платежи и определяет хотябы один просроченный платеж
     * 
     * @param  \App\Models\IncomeSource $row
     * @return boolean
     */
    public function isOverdue($row)
    {
        $months = $this->view(new Request, $row);
        $month_end = now()->format("Y-m");

        foreach ($months as $data) {

            foreach ($data['rows'] as $pay) {

                if ($data['month'] < $month_end and ($pay->purpose_every_month ?? null) and !($pay->sum ?? 0) and !($pay->hide_overdue ?? null))
                    return true;
            }
        }

        return false;
    }

    /**
     * Вывод строк выплаты
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\IncomeSource|null $source
     * @return \Illuminate\Http\JsonResponse
     */
    public function view(Request $request, $source = null)
    {
        return (new Pays)->index($request, $source);
    }

    /**
     * Формирует строку платежа
     * 
     * @param  \App\Models\CashboxTransaction $row
     * @return \App\Models\CashboxTransaction
     */
    public function getRowCashboxTransaction($row)
    {
        if (empty($this->purposes))
            $this->purposes = Purposes::collect();

        $row->source = $this->getSourceInfo($row);

        $purpose = $this->purposes->search(function ($item) use ($row) {
            return $item['id'] == $row->purpose_pay;
        });
        $row->purpose = $this->purposes[$purpose] ?? null;

        if (is_array($row->purpose)) {
            foreach ($row->purpose as $key => $value) {
                $row->{"purpose_" . $key} = $value;
            }
        }

        if ($row->parking = $this->getParkingInfo($row->income_source_parking_id))
            $row->purpose_name = trim($row->purpose_name . " " . $row->parking->parking_place);

        return $row;
    }

    /**
     * Дополняет информацию о парковке
     * 
     * @param  int|null $parking_id
     * @return \App\Models\IncomeSourceParking|null
     */
    public function getParkingInfo($parking_id)
    {
        if (!$parking_id)
            return null;

        if (empty($this->get_parking_info))
            $this->get_parking_info = [];

        if (isset($this->get_parking_info[$parking_id]))
            return $this->get_parking_info[$parking_id];

        return $this->get_parking_info[$parking_id] = IncomeSourceParking::find($parking_id);
    }

    /**
     * Создает пустую строку платежа
     * 
     * @param  int $source_id
     * @param  int $purpose_pay
     * @param  string|null $date
     * @param  string|null $day_x
     * @return \App\Models\CashboxTransaction
     */
    public function getEmptyRow($source_id, $purpose_pay, $date = null, $day_x = null)
    {
        $row = new CashboxTransaction;
        $row->income_source_id = $source_id;
        $row->purpose_pay = $purpose_pay;
        $row->date = now()->create($date ?: now())->setDay($day_x ?: 20)->format("Y-m-d");

        return $this->getRowCashboxTransaction($row);
    }

    /**
     * Выводит информацию об источнике
     * 
     * @param  \App\Models\CashboxTransaction $row
     * @return \App\Models\IncomeSource|null
     */
    public function getSourceInfo($row)
    {
        $id = $row->income_source_id;

        if (empty($this->get_source_info))
            $this->get_source_info = [];

        if (empty($this->get_source_info[$id]))
            $this->get_source_info[$id] = [];

        if (!count($this->get_source_info[$id])) {

            $log = $this->getIncomeSourceLog($id, $row->date);

            if ($log)
                return $this->get_source_info[$id][$log->change_date->format("Y-m-d")] = $log->source_data;
        }

        foreach ($this->get_source_info[$id] as $date => $source) {

            if ($date <= $row->date)
                return $source;
        }

        if ($log = $this->getIncomeSourceLog($id, $row->date)) {
            return $this->get_source_info[$id][$log->change_date->format("Y-m-d")] = $log->source_data;
        }

        return $this->get_source_info[$id][now()->format("Y-m-d")] = IncomeSource::find($id);
    }

    /**
     * Возвращает строку источника дохода из истории изменений
     * 
     * @param  int $id
     * @param  string $date
     * @return \App\Models\IncomeSourceLog|null
     */
    public function getIncomeSourceLog($id, $date)
    {
        return IncomeSourceLog::whereSourceId($id)
            ->where('change_date', '>', $date)
            ->first();
    }

    /**
     * Скрывает просроченный платеж
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setHideOverdue(Request $request)
    {
        $row = OverdueException::where([
            ['source_id', $request->source_id],
            ['purpose_id', $request->purpose],
            ['parking_id', $request->parking_id],
            ['month', $request->month],
        ])->first();

        if ($row) {

            $row->delete();
            $hide_overdue = false;
        } else {

            $row = OverdueException::create([
                'source_id' => $request->source_id,
                'purpose_id' => $request->purpose,
                'parking_id' => $request->parking_id,
                'month' => $request->month,
            ]);

            $hide_overdue = true;
        }

        Artisan::call("pays:overdue {$request->source_id}");

        Log::write($row, $request);

        return response()->json([
            'month' => $request->month,
            'row' => [
                'purpose_id' => $request->purpose,
                'hide_overdue' => $hide_overdue,
                'income_source_parking_id' => $request->parking_id,
            ],
            'source' => (new Sources)->getIncomeSourceRow(
                IncomeSource::firstOrNew(['id' => $request->source_id])
            ),
        ]);
    }

    /**
     * Удаление платежа
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function drop(Request $request)
    {
        if (!$row = CashboxTransaction::find($request->id))
            return response()->json(['message' => "Платеж не найден или уже удалён"], 400);

        $row->delete();

        Artisan::call("pays:overdue {$row->income_source_id}");

        Log::write($row, $request);

        return response()->json([
            'month' => $row->month,
            'row' => $row,
        ]);
    }
}
