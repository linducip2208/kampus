<?php

namespace App\Models;

class ResearchProject extends CampusModel
{
    protected $casts = ['budget' => 'decimal:2'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function lead()
    {
        return $this->belongsTo(LecturerProfile::class, 'lead_id');
    }

    public function members()
    {
        return $this->hasMany(ResearchMember::class);
    }
}
