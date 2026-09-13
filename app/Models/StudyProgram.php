<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class StudyProgram extends CampusModel
{
    use SoftDeletes;
    public function department() { return $this->belongsTo(Department::class); }
    public function curricula() { return $this->hasMany(Curriculum::class); }
    public function enrollments() { return $this->hasMany(StudentEnrollment::class); }
    public function feeStructures() { return $this->hasMany(FeeStructure::class); }
    public function applicantChoices() { return $this->hasMany(ApplicantProgramChoice::class); }
}
