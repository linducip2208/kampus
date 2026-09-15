<?php

namespace App\Models;

class StudentActivity extends CampusModel
{
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function organization()
    {
        return $this->belongsTo(StudentOrganization::class, 'student_organization_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
