<?php

namespace App\Models;

class ApplicantInterview extends CampusModel
{
    protected $casts = ['scheduled_at' => 'datetime', 'score' => 'decimal:2'];
    public function applicant() { return $this->belongsTo(Applicant::class); }
}
