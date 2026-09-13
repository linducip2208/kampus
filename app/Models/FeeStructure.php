<?php

namespace App\Models;

class FeeStructure extends CampusModel
{
    public function studyProgram() { return $this->belongsTo(StudyProgram::class); }
    public function semester() { return $this->belongsTo(Semester::class); }
    public function items() { return $this->hasMany(FeeStructureItem::class); }
}
