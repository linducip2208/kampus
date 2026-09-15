<?php

namespace App\Models;

class CommunityService extends CampusModel
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
}
