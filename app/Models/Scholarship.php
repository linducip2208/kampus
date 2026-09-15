<?php

namespace App\Models;

class Scholarship extends CampusModel
{
    protected $casts = ['amount' => 'decimal:2', 'is_active' => 'boolean'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function awards()
    {
        return $this->hasMany(ScholarshipAward::class);
    }
}
