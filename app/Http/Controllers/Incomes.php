<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Incomes\Purposes;
use App\Http\Controllers\Incomes\Sources;
use App\Models\CashboxTransaction;
use App\Models\IncomePart;
use App\Models\IncomeSource;
use App\Models\IncomeSourceLog;
use App\Models\Log;
use Illuminate\Http\Request;

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
     * @return array
     */
    public function getSourcesPart($id)
    {
        return IncomeSource::wherePartId($id)
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
     * Созраняет данные строки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        $request->validate([
            'sum' => "required|numeric",
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

        $row->sum = $request->sum;
        $row->type_pay = $request->type_pay;
        $row->purpose_pay = $request->purpose_pay;
        $row->is_income = true;
        $row->income_part_id = $request->income_part_id;
        $row->income_source_id = $source->id;
        $row->date = $request->date ?: now()->format("Y-m-d");
        $row->month = now()->create($row->date)->format("Y-m");
        $row->user_id = $request->user()->id;

        $row->save();

        Log::write($row, $request);

        return response()->json([
            'row' => $row,
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

                if ($data['month'] < $month_end and ($pay->purpose_every_month ?? null) and !($pay->sum ?? 0))
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
        $toArray = false;

        if ($source instanceof IncomeSource) {
            $toArray = true;
        } else {
            if (!$source = IncomeSource::find($request->source_id))
                $source = new IncomeSource;
        }

        $source_id = $source->id ?? $request->source_id;

        $this->purposes = Purposes::collect();

        $rows = CashboxTransaction::whereIsIncome(true)
            ->when((bool) $source_id, function ($query) use ($source_id) {
                $query->whereIncomeSourceId($source_id);
            })
            ->when((bool) $source->date ?? null, function ($query) use ($source) {
                $query->whereBetween('date', [
                    now()->create($source->date)->startOfDay(),
                    now()->create($source->date_to ?: now())->endOfDay()
                ]);
            })
            ->orderBy('id', 'DESC')
            ->get()
            ->map(function ($row) use (&$data) {

                $row = $this->getRowCashboxTransaction($row);
                $data[$row->month][] = $row;

                return $row;
            });

        $date_check = now()->create($source->date ?? now());
        $month_end = (int) now()->format("d") >= 20 ? now()->format("Y-m") : now()->subMonth()->format("Y-m");

        while ($date_check->format("Y-m") <= $month_end) {

            $month = $date_check->format("Y-m");

            if (!isset($data[$month])) {
                $data[$month] = [];
            }

            $date_check->addMonth();
        }

        $response_data = collect($data ?? [])->sortKeysDesc()
            ->map(function ($row, $key) use ($source) {

                $is_arenda = false;
                $is_parking = false;
                $is_internet = false;

                foreach ($row as $value) {

                    if ($value->purpose_pay == 1)
                        $is_arenda = true;

                    if ($value->purpose_pay == 2)
                        $is_parking = true;

                    if ($value->purpose_pay == 5)
                        $is_internet = true;
                }

                if (!$is_arenda) {
                    $row[] = $this->getEmptyRow($source->id, 1, $key);
                }

                if (!$is_parking) {

                    $new_row = $this->getEmptyRow($source->id, 2, $key);

                    if ($source->is_parking ?? null)
                        $row[] = $new_row;
                }

                if (!$is_internet) {

                    $new_row = $this->getEmptyRow($source->id, 5, $key);

                    if ($source->is_internet ?? null)
                        $row[] = $new_row;
                }

                return [
                    'month' => $key,
                    'rows' => $row,
                ];
            })
            ->values()
            ->all();

        if ($toArray)
            return $response_data;

        return response()->json([
            'row' => $source,
            'rows' => $rows,
            'data' => $response_data,
        ]);
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

        return $row;
    }

    /**
     * Создает пустую строку платежа
     * 
     * @param  int $source_id
     * @param  int $purpose_pay
     * @param  string|null $date
     * @return \App\Models\CashboxTransaction
     */
    public function getEmptyRow($source_id, $purpose_pay, $date = null)
    {
        $row = new CashboxTransaction;
        $row->income_source_id = $source_id;
        $row->purpose_pay = $purpose_pay;
        $row->date = now()->create($date ?: now())->setDay(20);

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
}
