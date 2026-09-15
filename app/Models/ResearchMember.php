<?php

namespace App\Models;

class ResearchMember extends CampusModel
{
    public function project()
    {
        return $this->belongsTo(ResearchProject::class, 'research_project_id');
    }

    public function lecturer()
    {
        return $this->belongsTo(LecturerProfile::class, 'lecturer_profile_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }
}
