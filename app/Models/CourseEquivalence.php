<?php

namespace App\Models;

class CourseEquivalence extends CampusModel
{
    public function oldCourse()
    {
        return $this->belongsTo(Course::class, 'old_course_id');
    }

    public function newCourse()
    {
        return $this->belongsTo(Course::class, 'new_course_id');
    }
}
