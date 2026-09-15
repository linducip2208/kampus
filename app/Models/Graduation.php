<?php

namespace App\Models;

class Graduation extends CampusModel
{
    protected $casts = ['gpa' => 'decimal:3', 'decided_at' => 'datetime', 'graduated_at' => 'datetime'];

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

    public function clearances()
    {
        return $this->hasMany(GraduationClearance::class);
    }
}
