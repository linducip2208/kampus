<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends CampusModel
{
    use SoftDeletes;
    public function faculty() { return $this->belongsTo(Faculty::class); }
    public function studyPrograms() { return $this->hasMany(StudyProgram::class); }
}
