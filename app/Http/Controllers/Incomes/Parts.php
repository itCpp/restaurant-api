<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Incomes;
use App\Models\IncomePart;
use Illuminate\Http\Request;

class Parts extends Controller
{
    /**
     * Создание раздела
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        $request->validate([
            'building_id' => "required|numeric",
            'name' => "required",
        ]);

        $row = IncomePart::create([
            'building_id' => $request->building_id,
            'name' => $request->name,
            'comment' => $request->comment,
        ]);

        $row->rows = (new Incomes)->getSourcesPart($row->id);

        return response()->json([
            'row' => $row,
        ]);
    }
}
