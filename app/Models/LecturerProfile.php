<?php

namespace App\Models;

class LecturerProfile extends CampusModel
{
    public function employee() { return $this->belongsTo(Employee::class); }
    public function enrollmentsAsAdvisor() { return $this->hasMany(StudentEnrollment::class, 'advisor_id'); }
    public function classSections() { return $this->belongsToMany(ClassSection::class, 'class_lecturers'); }
}
