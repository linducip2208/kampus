<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class CourseModule extends CampusModel
{
    use SoftDeletes;
    public function classSection() { return $this->belongsTo(ClassSection::class); }
    public function contents() { return $this->hasMany(CourseContent::class)->orderBy('position'); }
}
