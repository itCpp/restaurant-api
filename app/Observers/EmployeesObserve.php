<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\EmployeeProcessing;
use Illuminate\Support\Facades\Auth;

class EmployeesObserve
{
    /**
     * Handle the User "created" event.
     *
     * @param  \App\Models\Employee  $row
     * @return void
     */
    public function created(Employee $row)
    {
        if ($row->personal_data['processing_hour'] ?? null) {
            EmployeeProcessing::create([
                'employee_id' => $row->id,
                'to' => (float) $row->personal_data['processing_hour'],
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Handle the User "created" event.
     *
     * @param  \App\Models\Employee  $row
     * @return void
     */
    public function updated(Employee $row)
    {
        $original = $row->getOriginal();
        // $changes = $row->getChanges();

        $processing_hour_from = $original['personal_data']['processing_hour'] ?? null;
        $processing_hour_to = $row->personal_data['processing_hour'] ?? null;

        if ($processing_hour_from != $processing_hour_to) {
            EmployeeProcessing::create([
                'employee_id' => $row->id,
                'from' => !is_null($processing_hour_from) ? (float) $processing_hour_from : null,
                'to' => !is_null($processing_hour_to) ? (float) $processing_hour_to : null,
                'user_id' => Auth::id(),
            ]);
        }
    }
}
