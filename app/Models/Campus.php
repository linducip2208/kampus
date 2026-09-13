<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Campus extends CampusModel
{
    use SoftDeletes;
    public function university() { return $this->belongsTo(University::class); }
}
