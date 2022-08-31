<?php

namespace App\Http\Controllers;

use App\Models\IncomePart;
use App\Models\IncomeSource;
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
        $rows = IncomePart::get()
            ->map(function ($row) {

                $row->rows = IncomeSource::wherePartId($row->id)
                    ->orderBy('cabinet')
                    ->get();

                return $row;
            })
            ->toArray();

        return response()->json([
            'rows' => $rows,
        ]);
    }
}
