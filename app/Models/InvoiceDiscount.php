<?php

namespace App\Models;

class InvoiceDiscount extends CampusModel
{
    protected $casts = ['amount' => 'decimal:2', 'granted_at' => 'datetime'];

    public function invoice()
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    public function award()
    {
        return $this->belongsTo(ScholarshipAward::class, 'scholarship_award_id');
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
