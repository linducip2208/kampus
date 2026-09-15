<?php

namespace App\Models;

use App\Support\Money;

class StudentInvoice extends CampusModel
{
    protected $casts = ['total_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'due_on' => 'date'];

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function installments()
    {
        return $this->hasMany(InvoiceInstallment::class)->orderBy('sequence');
    }

    public function getOutstandingAmountAttribute(): string
    {
        $total = Money::toMinorUnits($this->total_amount);
        $paid = Money::toMinorUnits($this->paid_amount);

        return Money::fromMinorUnits(max(0, $total - $paid));
    }
}
