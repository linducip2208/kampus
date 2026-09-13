<?php

namespace App\Models;

class Curriculum extends CampusModel
{
    public function studyProgram() { return $this->belongsTo(StudyProgram::class); }
    public function courses() { return $this->belongsToMany(Course::class, 'curriculum_courses')->withPivot(['term', 'is_mandatory'])->withTimestamps(); }
    public function enrollments() { return $this->hasMany(StudentEnrollment::class); }
}
