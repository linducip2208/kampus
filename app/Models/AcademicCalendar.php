<?php

namespace App\Models;

class AcademicCalendar extends CampusModel
{
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
