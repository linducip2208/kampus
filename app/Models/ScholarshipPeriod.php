<?php

namespace App\Models;

class ScholarshipPeriod extends CampusModel
{
    protected $casts = ['opens_on' => 'date', 'closes_on' => 'date'];

    public function scholarship()
    {
        return $this->belongsTo(Scholarship::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
