<?php

namespace App\Models;

class LetterRequest extends CampusModel
{
    protected $casts = ['payload' => 'array', 'issued_at' => 'datetime'];

    public function template()
    {
        return $this->belongsTo(LetterTemplate::class, 'letter_template_id');
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
