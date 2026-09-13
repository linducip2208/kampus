<?php

namespace App\Models;

class StudyPlan extends CampusModel
{
    protected $casts = ['submitted_at' => 'datetime', 'approved_at' => 'datetime'];
    public function enrollment() { return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id'); }
    public function semester() { return $this->belongsTo(Semester::class); }
    public function items() { return $this->hasMany(StudyPlanItem::class); }
}
