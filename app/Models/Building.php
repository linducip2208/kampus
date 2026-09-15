<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends CampusModel
{
    use SoftDeletes;

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
