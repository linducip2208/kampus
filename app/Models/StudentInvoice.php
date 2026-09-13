<?php

namespace App\Models;

class StudentInvoice extends CampusModel
{
    protected $casts = ['total_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'due_on' => 'date'];
    public function enrollment() { return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id'); }
    public function semester() { return $this->belongsTo(Semester::class); }
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function allocations() { return $this->hasMany(PaymentAllocation::class); }
    public function getOutstandingAmountAttribute(): float { return max(0, (float) $this->total_amount - (float) $this->paid_amount); }
}
