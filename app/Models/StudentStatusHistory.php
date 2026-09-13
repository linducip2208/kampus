<?php

namespace App\Models;

class StudentStatusHistory extends CampusModel
{
    protected $casts = ['changed_at' => 'datetime'];
    public function enrollment() { return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id'); }
    public function changedBy() { return $this->belongsTo(User::class, 'changed_by'); }
}
