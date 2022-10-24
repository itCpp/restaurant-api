<?php

namespace App\Http\Controllers\Incomes;

use App\Models\IncomeSource;
use Illuminate\Http\Request;

class ParkingRepository
{
    /**
     * Выводит данные
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return 
     */
    public function index(Request $request)
    {
        return IncomeSource::where('is_parking', true)
            ->orWhere('part_id', null)
            ->get()
            ->map(function ($row) {
                return $this->source($row);
            });
    }

    /**
     * Формиурет строку арендатора
     * 
     * @param  \App\Models\IncomeSource $row
     * @return \App\Models\IncomeSource
     */
    public function source(IncomeSource $row)
    {
        $row->parking = $this->getParkingList($row->id);

        $row->files = IncomesFile::where('income_id', $row->id)->count();

        return $row;
    }
}
