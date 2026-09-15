<?php

namespace App\Models;

class LecturerWorkload extends CampusModel
{
    protected $casts = ['target_sks' => 'decimal:2', 'submitted_at' => 'datetime', 'decided_at' => 'datetime'];

    public function lecturer()
    {
        return $this->belongsTo(LecturerProfile::class, 'lecturer_profile_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function activities()
    {
        return $this->hasMany(WorkloadActivity::class);
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
