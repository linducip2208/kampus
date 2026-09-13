<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends CampusModel
{
    use SoftDeletes;
    protected $appends = ['credits'];
    public function university() { return $this->belongsTo(University::class); }
    public function curricula() { return $this->belongsToMany(Curriculum::class, 'curriculum_courses')->withPivot(['term', 'is_mandatory'])->withTimestamps(); }
    public function prerequisites() { return $this->belongsToMany(Course::class, 'course_prerequisites', 'course_id', 'prerequisite_course_id'); }
    public function offerings() { return $this->hasMany(CourseOffering::class); }
    public function getCreditsAttribute(): int { return (int) $this->theory_credits + (int) $this->practical_credits; }
}
