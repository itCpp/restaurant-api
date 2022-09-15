<?php

namespace App\Http\Controllers\Incomes;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Incomes;
use App\Models\IncomePart;
use App\Models\Log;
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

        $row = IncomePart::find($request->id);

        if ($request->id and !$row)
            return response()->json(['message' => "Раздел не найден"], 400);

        if (!$request->id and !$row)
            $row = new IncomePart;

        $row->building_id = $request->building_id;
        $row->name = $request->name;
        $row->comment = $request->comment;

        $row->save();

        Log::write($row, $request);

        $row->rows = (new Incomes)->getSourcesPart($row->id);

        return response()->json([
            'row' => $row,
        ]);
    }
}
