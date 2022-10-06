<?php

namespace App\Http\Controllers\Employees\Salaries;

use App\Models\Employee;

trait Result
{
    /**
     * Подсчитывает результаты
     * 
     * @return $this
     */
    public function getResult()
    {
        $this->data['rows'] = collect($this->data['users'] ?? [])->sortBy("fullname")
            ->map(function ($row) {
                return $this->getRowResult($row);
            })
            ->values()
            ->all();

        return $this;
    }

    /**
     * Формирование итоговой строки
     * 
     * @param  \App\Models\Employee  $row
     * @return \App\Models\Employee
     */
    public function getRowResult(Employee $row)
    {
        $this->addedOtherData($row);

        /** К выплате */
        $row->toPayoff = $this->getToPayoff($row);

        /** Остаток получки */
        $row->balance = $row->toPayoff - $row->prepayment;

        return $row;
    }

    /**
     * Дополнение всяческими данными
     * 
     * @param  \App\Models\Employee  $row
     * @return \App\Models\Employee
     */
    public function addedOtherData(Employee &$row)
    {
        /** График работы */
        $row->shedule = $this->data['data']['shedule'][$row->id] ?? [];

        $row->parts = $this->getSalaryParts($row);

        /** Выплаченные довольствия за период */
        $row->prepayment = $this->data['data']['prepayments'][$row->id] ?? 0;

        return $row;
    }

    /**
     * Расчет ежедневной части оклада
     * 
     * @param  \App\Models\Employee  $row
     * @return array
     */
    public function getSalaryParts(Employee $row)
    {
        $days = request()->days ?: 30;
        $part = $row->salary_one_day ? $row->salary : $row->salary / $days;

        for ($i = 1; $i <= $days; $i++) {
            $parts[$i] = $row->salary_one_day ? 0 : $part;
        }

        foreach ($row->shedule ?? [] as $day => $data) {

            $day = (int) $day;

            /** Рабочий день */
            if ($data['type'] == 1) {
                $parts[$day] = $part;
            }
            /** Неполный рабочий день */
            else if ($data['type'] == 2) {
                $parts[$day] = $part / 2;
            }
            /** Выходной */
            else if ($data['type'] == 3) {
                $parts[$day] = 0;
            }
            /** Неполный выходной */
            else if ($data['type'] == 4) {
                $parts[$day] = $part / 2;
            }
            /** Переработка */
            else if ($data['type'] == 5) {
                $parts[$day] = $part * 2;
            }
            /** По графику */
            else if (!$row->salary_one_day) {
                $parts[$day] = $part;
            }
        }

        $start_day = $row->work_start ? now()->create($row->work_start) : null;
        $stop_day = $row->work_stop ? now()->create($row->work_stop) : null;

        /** Корректировка по дате начала и окончания работы */
        foreach ($parts ?? [] as $day => $part) {

            if ($start_day and $start_day > now()->create(request()->month)->setDay($day))
                $parts[$day] = 0;

            if ($stop_day and $stop_day < now()->create(request()->month)->setDay($day))
                $parts[$day] = 0;
        }

        return $parts ?? [];
    }

    /**
     * Расчет ежедневной части оклада
     * 
     * @param  \App\Models\Employee  $row
     * @return int
     */
    public function getToPayoff(Employee $row)
    {
        $toPayoff = 0;

        foreach ($row->parts ?? [] as $part) {
            $toPayoff += $part;
        }

        return round($toPayoff, 2);
    }
}
