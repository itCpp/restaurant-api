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
        /** К выплате */
        $row->toPayoff = $row->salary;

        /** Выплаченные довольствия за период */
        $row->prepayment = $this->data['data']['prepayments'][$row->id] ?? 0;

        /** Остаток получки */
        $row->balance = $row->toPayoff - $row->prepayment;

        return $row;
    }
}
