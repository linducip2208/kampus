<?php

namespace App\Models;

class ThesisProposal extends CampusModel
{
    protected $casts = ['decided_at' => 'datetime'];

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function advisor()
    {
        return $this->belongsTo(LecturerProfile::class, 'advisor_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function defenses()
    {
        return $this->hasMany(ThesisDefense::class);
    }

    public function guidances()
    {
        return $this->hasMany(ThesisGuidance::class)->orderBy('created_at');
    }

    public function approvalRequests()
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }
}
