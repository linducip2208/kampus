<?php

namespace App\Models;

class Holiday extends CampusModel
{
    protected $casts = ['date' => 'date', 'is_national' => 'boolean'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }
}
