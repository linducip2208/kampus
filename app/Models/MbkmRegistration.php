<?php

namespace App\Models;

class MbkmRegistration extends CampusModel
{
    protected $casts = ['decided_at' => 'datetime'];

    public function program()
    {
        return $this->belongsTo(MbkmProgram::class, 'mbkm_program_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function approvalRequests()
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }
}
