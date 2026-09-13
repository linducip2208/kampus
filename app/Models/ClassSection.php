<?php

namespace App\Models;

class ClassSection extends CampusModel
{
    public function offering() { return $this->belongsTo(CourseOffering::class, 'course_offering_id'); }
    public function lecturers() { return $this->belongsToMany(LecturerProfile::class, 'class_lecturers'); }
    public function schedules() { return $this->hasMany(ClassSchedule::class); }
    public function meetings() { return $this->hasMany(LectureMeeting::class); }
    public function studyPlanItems() { return $this->hasMany(StudyPlanItem::class); }
}
