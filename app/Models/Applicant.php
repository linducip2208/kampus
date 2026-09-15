<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Applicant extends CampusModel
{
    use SoftDeletes;
    protected $casts = ['birth_date' => 'date', 'passed_at' => 'datetime', 'school_score' => 'decimal:2', 'selection_score' => 'decimal:2'];
    public function university() { return $this->belongsTo(University::class); }
    public function admissionPath() { return $this->belongsTo(AdmissionPath::class); }
    public function convertedStudent() { return $this->belongsTo(StudentProfile::class, 'converted_student_profile_id'); }
    public function programChoices() { return $this->hasMany(ApplicantProgramChoice::class); }
    public function statusHistories() { return $this->hasMany(ApplicantStatusHistory::class); }
    public function exams() { return $this->hasMany(AdmissionExam::class); }
    public function interviews() { return $this->hasMany(ApplicantInterview::class); }
    public function reRegistration() { return $this->hasOne(ApplicantReRegistration::class); }
    public function documents() { return $this->hasMany(ApplicantDocument::class); }
    public function payments() { return $this->hasMany(ApplicantPayment::class); }
}
