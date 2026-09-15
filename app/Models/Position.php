<?php

namespace App\Models;

class Position extends CampusModel
{
    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function unit()
    {
        return $this->belongsTo(OrganizationalUnit::class, 'unit_id');
    }

    public function contracts()
    {
        return $this->hasMany(EmploymentContract::class);
    }
}
