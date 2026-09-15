<?php

namespace App\Models;

class ClassSection extends CampusModel
{
    public function offering()
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    public function lecturers()
    {
        return $this->belongsToMany(LecturerProfile::class, 'class_lecturers');
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function meetings()
    {
        return $this->hasMany(LectureMeeting::class);
    }

    public function studyPlanItems()
    {
        return $this->hasMany(StudyPlanItem::class);
    }

    public function gradingComponents()
    {
        return $this->hasMany(GradingComponent::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function courseModules()
    {
        return $this->hasMany(CourseModule::class);
    }

    public function announcements()
    {
        return $this->hasMany(CourseAnnouncement::class);
    }

    public function discussions()
    {
        return $this->hasMany(Discussion::class);
    }
}
