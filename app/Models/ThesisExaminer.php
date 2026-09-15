<?php

namespace App\Models;

class ThesisExaminer extends CampusModel
{
    protected $casts = ['score' => 'decimal:2'];

    public function defense()
    {
        return $this->belongsTo(ThesisDefense::class, 'thesis_defense_id');
    }

    public function lecturer()
    {
        return $this->belongsTo(LecturerProfile::class, 'lecturer_profile_id');
    }
}
