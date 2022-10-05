<?php

namespace App\Http\Controllers\Employees\Salaries;

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

                /** Выплаченные довольствия за период */
                $row->prepayment = $this->data['data']['prepayments'][$row->id] ?? 0;

                /** Остаток получки */
                $row->balance = $row->salary - $row->prepayment;

                return $row;
            })
            ->values()
            ->all();

        return $this;
    }
}
