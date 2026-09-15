<?php

namespace App\Models;

class ApplicantPayment extends CampusModel
{
    protected $casts = ['amount' => 'decimal:2', 'verified_at' => 'datetime'];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
