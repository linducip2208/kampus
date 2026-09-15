<?php

namespace App\Models;

class AlumniProfile extends CampusModel
{
    protected $casts = ['started_work_at' => 'datetime'];

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function graduation()
    {
        return $this->belongsTo(Graduation::class);
    }

    public function tracerSurveys()
    {
        return $this->hasMany(TracerSurvey::class);
    }
}
