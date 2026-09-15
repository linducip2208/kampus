<?php

namespace App\Models;

class StudentGrade extends CampusModel
{
    protected $casts = [
        'final_score' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function studyPlanItem()
    {
        return $this->belongsTo(StudyPlanItem::class);
    }

    public function gradeScale()
    {
        return $this->belongsTo(GradeScale::class);
    }

    public function componentScores()
    {
        return $this->hasMany(StudentComponentScore::class, 'study_plan_item_id', 'study_plan_item_id');
    }

    public function revisionRequests()
    {
        return $this->hasMany(GradeRevisionRequest::class);
    }
}
