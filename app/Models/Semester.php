<?php

namespace App\Models;

class Semester extends CampusModel
{
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'is_active' => 'boolean'];
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function offerings() { return $this->hasMany(CourseOffering::class); }
    public function studyPlans() { return $this->hasMany(StudyPlan::class); }
    public function invoices() { return $this->hasMany(StudentInvoice::class); }
}
