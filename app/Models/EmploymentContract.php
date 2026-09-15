<?php

namespace App\Models;

class EmploymentContract extends CampusModel
{
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'salary' => 'decimal:2'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
