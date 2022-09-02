<?php

namespace App\Http\Controllers;

use App\Models\CashboxTransaction;
use Illuminate\Http\Request;

class Main extends Controller
{
    /**
     * Наименование месяцев
     * 
     * @var array
     */
    protected $months = [
        1 => "Январь",
        2 => "Февраль",
        3 => "Март",
        4 => "Апрель",
        5 => "Май",
        6 => "Июнь",
        7 => "Июль",
        8 => "Август",
        9 => "Сентябрь",
        10 => "Октябрь",
        11 => "Ноябрь",
        12 => "Декабрь",
    ];

    /**
     * Данные для главной страницы
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $data = CashboxTransaction::selectRaw('SUM(sum) as sum, is_income, month')
            ->where('date', '>', now()->startOfMonth()->subMonths(12))
            ->groupBy(['is_income', 'month'])
            ->orderBy('month')
            ->get()
            ->map(function ($row) {
                return [
                    'month' => $this->getMonthName($row->month) . " " . now()->create($row->month)->format("Y"),
                    'type' => $row->is_income ? "Доход" : "Расход",
                    'sum' => abs($row->sum),
                ];
            });

        return response()->json([
            'chart' => $data,
        ]);
    }

    /**
     * Выводит наименование месяца
     * 
     * @param  string
     * @return string|null
     */
    public function getMonthName($month)
    {
        $key = (int) now()->create($month)->format("m");

        return $this->months[$key] ?? null;
    }
}
