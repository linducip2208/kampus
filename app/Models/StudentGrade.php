<?php

namespace App\Models;

class StudentGrade extends CampusModel
{
    protected $casts = ['final_score' => 'decimal:2', 'locked_at' => 'datetime'];
    public function studyPlanItem() { return $this->belongsTo(StudyPlanItem::class); }
    public function gradeScale() { return $this->belongsTo(GradeScale::class); }
}
