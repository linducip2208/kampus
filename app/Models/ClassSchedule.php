<?php

namespace App\Models;

class ClassSchedule extends CampusModel
{
    public function classSection() { return $this->belongsTo(ClassSection::class); }
}
