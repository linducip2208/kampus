<?php

namespace App\Models;

class TracerSurvey extends CampusModel
{
    protected $casts = ['filled_at' => 'datetime'];

    public function alumni()
    {
        return $this->belongsTo(AlumniProfile::class, 'alumni_profile_id');
    }
}
