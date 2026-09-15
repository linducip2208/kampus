<?php

namespace App\Models;

class CourseCategory extends CampusModel
{
    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
