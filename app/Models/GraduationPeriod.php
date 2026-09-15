<?php

namespace App\Models;

class GraduationPeriod extends CampusModel
{
    protected $casts = ['opens_on' => 'date', 'closes_on' => 'date', 'ceremony_on' => 'date'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
