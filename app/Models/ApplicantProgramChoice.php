<?php

namespace App\Models;

class ApplicantProgramChoice extends CampusModel
{
    public function applicant() { return $this->belongsTo(Applicant::class); }
    public function studyProgram() { return $this->belongsTo(StudyProgram::class); }
}
