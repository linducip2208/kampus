<?php

namespace App\Models;

class GradeRevisionRequest extends CampusModel
{
    protected $casts = [
        'old_score' => 'decimal:2',
        'new_score' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function grade()
    {
        return $this->belongsTo(StudentGrade::class, 'student_grade_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
