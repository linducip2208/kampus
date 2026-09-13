<?php

namespace App\Models;

class StudentLeaveRequest extends CampusModel
{
    protected $casts = ['submitted_at' => 'datetime', 'approved_at' => 'datetime'];
    public function enrollment() { return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id'); }
    public function semester() { return $this->belongsTo(Semester::class); }
    public function requestedBy() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approvalRequests() { return $this->morphMany(ApprovalRequest::class, 'approvable'); }
}
