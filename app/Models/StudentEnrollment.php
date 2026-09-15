<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class StudentEnrollment extends CampusModel
{
    use SoftDeletes;

    public function studentProfile()
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function advisor()
    {
        return $this->belongsTo(LecturerProfile::class, 'advisor_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(StudentStatusHistory::class);
    }

    public function studyPlans()
    {
        return $this->hasMany(StudyPlan::class);
    }

    public function invoices()
    {
        return $this->hasMany(StudentInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function academicDocuments()
    {
        return $this->hasMany(AcademicDocument::class);
    }

    public function quizAttempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
