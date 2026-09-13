<?php

namespace App\Models;

class Payment extends CampusModel
{
    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    public function enrollment() { return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id'); }
    public function allocations() { return $this->hasMany(PaymentAllocation::class); }
    public function verifiedBy() { return $this->belongsTo(User::class, 'verified_by'); }
}
