<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class CourseContent extends CampusModel
{
    use SoftDeletes;
    protected $casts = ['published_at' => 'datetime'];
    public function module() { return $this->belongsTo(CourseModule::class, 'course_module_id'); }
}
