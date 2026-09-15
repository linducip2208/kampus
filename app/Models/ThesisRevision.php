<?php

namespace App\Models;

class ThesisRevision extends CampusModel
{
    protected $casts = ['is_done' => 'boolean', 'checked_at' => 'datetime'];

    public function defense()
    {
        return $this->belongsTo(ThesisDefense::class, 'thesis_defense_id');
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
