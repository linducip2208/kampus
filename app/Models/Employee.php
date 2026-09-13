<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends CampusModel
{
    use SoftDeletes;
    protected $casts = ['joined_on' => 'date'];
    public function user() { return $this->belongsTo(User::class); }
    public function university() { return $this->belongsTo(University::class); }
    public function lecturerProfile() { return $this->hasOne(LecturerProfile::class); }
}
