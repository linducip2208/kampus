<?php

namespace App\Models;

class GraduationClearance extends CampusModel
{
    protected $casts = ['checked_at' => 'datetime'];

    public function graduation()
    {
        return $this->belongsTo(Graduation::class);
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
