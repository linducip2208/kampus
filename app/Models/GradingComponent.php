<?php

namespace App\Models;

class GradingComponent extends CampusModel
{
    protected $casts = ['weight' => 'decimal:2'];

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function scores()
    {
        return $this->hasMany(StudentComponentScore::class);
    }
}
