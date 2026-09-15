<?php

namespace App\Models;

class ScholarshipAward extends CampusModel
{
    protected $casts = ['amount' => 'decimal:2', 'decided_at' => 'datetime'];

    public function scholarship()
    {
        return $this->belongsTo(Scholarship::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
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
