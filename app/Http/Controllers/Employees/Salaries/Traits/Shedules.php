<?php

namespace App\Http\Controllers\Employees\Salaries\Traits;

use App\Models\EmployeeShedule;
use App\Models\EmployeeSheduleStory;

trait Shedules
{
    /**
     * Подсчитывает результаты
     * 
     * @return $this
     */
    public function getShedules()
    {
        EmployeeShedule::where('month', request()->month ?: now()->format("Y-m"))
            ->each(function ($row) use (&$shedule) {
                $shedule[$row->employee_id] = $row->days ?: [];
            });

        $this->data['data']['shedule'] = $shedule ?? [];

        $stop = request()->month ? now()->create(request()->month) : now();

        EmployeeSheduleStory::orderBy('shedule_start')
            ->orderBy('id')
            ->where('shedule_start', '<=', $stop->format("Y-m-t"))
            ->lazy()
            ->each(function ($row) {
                $this->data['data']['shedule_story'][$row->employee_id][] = $row->shedule_type;
            });

        return $this;
    }
}
