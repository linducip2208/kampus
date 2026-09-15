<?php

namespace App\Models;

class TracerSection extends CampusModel
{
    protected $casts = ['is_active' => 'boolean'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function questions()
    {
        return $this->hasMany(TracerQuestion::class)->orderBy('sort_order');
    }
}
