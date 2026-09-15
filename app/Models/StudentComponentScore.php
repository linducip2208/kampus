<?php

namespace App\Models;

class StudentComponentScore extends CampusModel
{
    protected $casts = ['score' => 'decimal:2'];

    public function studyPlanItem()
    {
        return $this->belongsTo(StudyPlanItem::class);
    }

    public function component()
    {
        return $this->belongsTo(GradingComponent::class, 'grading_component_id');
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
