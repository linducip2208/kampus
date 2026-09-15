<?php

namespace App\Models;

class JobApplication extends CampusModel
{
    public function vacancy()
    {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }

    public function alumni()
    {
        return $this->belongsTo(AlumniProfile::class, 'alumni_profile_id');
    }
}
