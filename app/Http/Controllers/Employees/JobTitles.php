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
            $this->list($request->otdel_id)
        );
    }

    /**
     * Формирует список должностей по отделу
     * 
     * @param  int $otdel_id
     * @return array
     */
    public static function list($otdel_id = null)
    {
        return Employee::select('job_title')
            ->withTrashed()
            ->where('job_title', '!=', null)
            ->when((bool) $otdel_id, function ($query) use ($otdel_id) {
                $query->whereEmployeeOtdelId($otdel_id);
            })
            ->distinct()
            ->get()
            ->map(function ($row) {
                return $row->job_title;
            })
            ->toArray();
    }
}
