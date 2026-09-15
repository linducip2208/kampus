<?php

namespace App\Models;

class PrivateFile extends CampusModel
{
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }
}
