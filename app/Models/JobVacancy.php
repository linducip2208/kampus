<?php

namespace App\Models;

class JobVacancy extends CampusModel
{
    protected $casts = ['closes_on' => 'date'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }
}
