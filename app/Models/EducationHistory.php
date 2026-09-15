<?php

namespace App\Models;

class EducationHistory extends CampusModel
{
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
