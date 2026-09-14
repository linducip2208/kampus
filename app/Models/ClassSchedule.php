<?php

namespace App\Models;

class ClassSchedule extends CampusModel
{
    protected $casts = [
        'starts_at' => 'datetime:H:i',
        'ends_at' => 'datetime:H:i',
    ];

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class);
    }
}
