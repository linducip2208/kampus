<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends CampusModel
{
    use SoftDeletes;
    public function university() { return $this->belongsTo(University::class); }
    public function dean() { return $this->belongsTo(LecturerProfile::class, 'dean_id'); }
    public function departments() { return $this->hasMany(Department::class); }
}
