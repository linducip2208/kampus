<?php

namespace App\Models;

class Certification extends CampusModel
{
    protected $casts = ['issued_on' => 'date', 'expires_on' => 'date'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
