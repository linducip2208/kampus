<?php

namespace App\Models;

class StudentTransfer extends CampusModel
{
    protected $casts = ['decided_at' => 'datetime'];

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function fromProgram()
    {
        return $this->belongsTo(StudyProgram::class, 'from_study_program_id');
    }

    public function toProgram()
    {
        return $this->belongsTo(StudyProgram::class, 'to_study_program_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function newEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'new_enrollment_id');
    }

    public function approvalRequests()
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }
}
