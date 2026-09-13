<?php

namespace App\Models;

class NumberSequence extends CampusModel
{
    public function university() { return $this->belongsTo(University::class); }
}
