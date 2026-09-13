<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class University extends CampusModel
{
    use SoftDeletes;

    public function campuses() { return $this->hasMany(Campus::class); }
    public function faculties() { return $this->hasMany(Faculty::class); }
    public function academicYears() { return $this->hasMany(AcademicYear::class); }
    public function courses() { return $this->hasMany(Course::class); }
    public function employees() { return $this->hasMany(Employee::class); }
}
