<?php

namespace App\Http\Controllers\Employees\Salaries;

use App\Models\CashboxTransaction;

trait Cashbox
{
    /**
     * Подсчитывает результаты
     * 
     * @return $this
     */
    public function getPrepayments()
    {
        CashboxTransaction::selectRaw('sum(sum) as sum, expense_subtype_id as user_id')
            ->whereIsExpense(true)
            ->whereExpenseTypeId(1)
            ->where(function ($query) {
                $query->where('month', now()->create(request()->start ?: now())->format("Y-m"));
            })
            ->groupBy('expense_subtype_id')
            ->get()
            ->each(function ($row) use (&$prepayments) {
                $prepayments[$row->user_id] = abs($row->sum);
            });

        $this->data['data']['prepayments'] = $prepayments ?? [];

        return $this;
    }
}
