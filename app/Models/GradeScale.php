<?php

namespace App\Models;

class GradeScale extends CampusModel
{
    public function university() { return $this->belongsTo(University::class); }
    public function grades() { return $this->hasMany(StudentGrade::class); }
}
