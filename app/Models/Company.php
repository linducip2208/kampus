<?php

namespace App\Models;

class Company extends CampusModel
{
    protected $casts = ['is_partner' => 'boolean'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function vacancies()
    {
        return $this->hasMany(JobVacancy::class);
    }
}
