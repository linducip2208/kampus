<?php

namespace App\Models;

class CourseOffering extends CampusModel
{
    public function semester() { return $this->belongsTo(Semester::class); }
    public function course() { return $this->belongsTo(Course::class); }
    public function classSections() { return $this->hasMany(ClassSection::class); }
}
