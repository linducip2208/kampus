<?php

namespace App\Models;

class AcademicYear extends CampusModel
{
    public function university() { return $this->belongsTo(University::class); }
    public function semesters() { return $this->hasMany(Semester::class); }
}
