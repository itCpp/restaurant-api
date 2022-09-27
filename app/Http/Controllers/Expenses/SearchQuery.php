<?php

namespace App\Http\Controllers\Expenses;

use App\Models\Employee;
use App\Models\ExpenseSubtype;
use App\Models\ExpenseType;

trait SearchQuery
{
    /**
     * Применяет поисковой запрос
     * 
     * @param  \App\Models\CashboxTransaction $data
     * @param  array $search
     * @return \App\Models\CashboxTransaction
     */
    public function setSearchQuery($data, $search)
    {
        if ((bool) ($search['name'] ?? null)) {

            $expense_types = [];

            ExpenseType::get()
                ->each(function ($row) use (&$expense_types, $search) {

                    if ($row->type_subtypes == "users") {

                        $names = trim(preg_replace('/\s/', ' ', $search['name']));
                        $names = explode(" ", $names);

                        $expense_types[$row->id] = Employee::where(function ($query) use ($names) {

                            $surname = $names[0] ?? null;
                            $name = $names[1] ?? null;
                            $middle_name = $names[2] ?? null;

                            foreach ($names as $name) {

                                $query->orWhere(function ($query) use ($surname, $name, $middle_name) {

                                    $query->when((bool) $surname, function ($query) use ($surname) {
                                        $query->orWhere('surname', 'LIKE', "%{$surname}%");
                                    })
                                        ->when((bool) $name, function ($query) use ($name) {
                                            $query->orWhere('name', 'LIKE', "%{$name}%");
                                        })
                                        ->when((bool) $middle_name, function ($query) use ($middle_name) {
                                            $query->orWhere('middle_name', 'LIKE', "%{$middle_name}%");
                                        });
                                });
                            }
                        })
                            ->get()
                            ->map(function ($row) {
                                return $row->id;
                            })
                            ->toArray();
                    } else {

                        $expense_types[$row->id] = ExpenseSubtype::where('expense_type_id', $row->id)
                            ->where('name', 'LIKE', "%{$search['name']}%")
                            ->get()
                            ->map(function ($row) {
                                return $row->id;
                            })
                            ->toArray();
                    }
                });

            $data = $data->where(function ($query) use ($expense_types) {

                foreach ($expense_types as $expense_type_id => $expense_subtype_id) {

                    $query->orWhere(function ($query) use ($expense_type_id, $expense_subtype_id) {

                        $query->where('expense_type_id', $expense_type_id)
                            ->whereIn('expense_subtype_id', $expense_subtype_id);
                    });
                }
            });
        }

        if ((bool) ($search['type'] ?? null)) {
            $data = $data->where('expense_type_id', $search['type']);
        }

        if ((bool) ($search['date'] ?? null)) {
            $data = $data->where('date', $search['date']);
        }

        if ((bool) ($search['month'] ?? null)) {
            $data = $data->where('month', $search['month']);
        }

        return $data;
    }
}
