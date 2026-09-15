<?php

namespace App\Models;

class StudentOrganizationMember extends CampusModel
{
    protected $casts = ['joined_at' => 'datetime', 'left_at' => 'datetime'];

    public function organization()
    {
        return $this->belongsTo(StudentOrganization::class, 'student_organization_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }
}
