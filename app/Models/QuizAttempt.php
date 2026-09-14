<?php

namespace App\Models;

class QuizAttempt extends CampusModel
{
    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'score' => 'decimal:2',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function answers()
    {
        return $this->hasMany(QuizAnswer::class);
    }
}
