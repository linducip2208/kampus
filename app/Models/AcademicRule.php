<?php

namespace App\Models;

class AcademicRule extends CampusModel
{
    protected $casts = ['value' => 'array'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }
}
