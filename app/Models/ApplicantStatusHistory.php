<?php

namespace App\Models;

class ApplicantStatusHistory extends CampusModel
{
    protected $casts = ['changed_at' => 'datetime'];
    public function applicant() { return $this->belongsTo(Applicant::class); }
    public function changedBy() { return $this->belongsTo(User::class, 'changed_by'); }
}
