<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class StudentProfile extends CampusModel
{
    use SoftDeletes;
    protected $casts = ['birth_date' => 'date'];
    public function user() { return $this->belongsTo(User::class); }
    public function enrollments() { return $this->hasMany(StudentEnrollment::class); }
}
