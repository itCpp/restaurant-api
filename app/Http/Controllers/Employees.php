<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Employees extends Controller
{
    /**
     * Вывод сотрудников
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json([
            'rows' => $rows ?? [],
        ]);
    }
}
