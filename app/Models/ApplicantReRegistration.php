<?php

namespace App\Models;

class ApplicantReRegistration extends CampusModel
{
    protected $casts = ['verified_at' => 'datetime'];
    public function applicant() { return $this->belongsTo(Applicant::class); }
    public function verifiedBy() { return $this->belongsTo(User::class, 'verified_by'); }
}
