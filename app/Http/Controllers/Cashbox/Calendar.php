<?php

namespace App\Http\Controllers\Cashbox;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Calendar extends Controller
{
    use Statistics;

    /**
     * Данные для календаря
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $date = $request->month ? now()->create($request->month) : now();

        return response()->json([
            'calendar' => $this->getStatistics(
                $date->copy()->startOfMonth()->startOfWeek()->format("Y-m-d"),
                $date->copy()->endOfMonth()->endOfWeek()->format("Y-m-d"),
            ),
        ]);
    }
}
