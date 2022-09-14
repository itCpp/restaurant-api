<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class JobTitles extends Controller
{
    /**
     * Выводит список должностей
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json(
            Employee::select('job_title')
                ->withTrashed()
                ->when((bool) $request->otdel_id, function ($query) use ($request) {
                    $query->whereEmployeeOtdelId($request->otdel_id);
                })
                ->distinct()
                ->get()
                ->map(function ($row) {
                    return $row->job_title;
                })
                ->toArray()
        );
    }
}
