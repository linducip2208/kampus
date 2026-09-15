<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends CampusModel
{
    use SoftDeletes;

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function laboratories()
    {
        return $this->hasMany(Laboratory::class);
    }
}
