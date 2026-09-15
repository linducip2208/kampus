<?php

namespace App\Models;

class EmployeeLeave extends CampusModel
{
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'decided_at' => 'datetime'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
