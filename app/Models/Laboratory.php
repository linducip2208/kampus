<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Laboratory extends CampusModel
{
    use SoftDeletes;

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function head()
    {
        return $this->belongsTo(LecturerProfile::class, 'head_id');
    }
}
