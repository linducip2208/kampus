<?php

namespace App\Models;

class StudyPlanItem extends CampusModel
{
    public function studyPlan() { return $this->belongsTo(StudyPlan::class); }
    public function classSection() { return $this->belongsTo(ClassSection::class); }
    public function grade() { return $this->hasOne(StudentGrade::class); }
}
