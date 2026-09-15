<?php

namespace App\Models;

class EmployeeAttendance extends CampusModel
{
    protected $casts = ['date' => 'date'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
