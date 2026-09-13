<?php

namespace App\Models;

class AssignmentSubmission extends CampusModel
{
    protected $casts = ['submitted_at' => 'datetime', 'is_late' => 'boolean', 'score' => 'decimal:2'];
    public function assignment() { return $this->belongsTo(Assignment::class); }
    public function enrollment() { return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id'); }
}
