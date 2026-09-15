<?php

namespace App\Models;

class PaymentAllocation extends CampusModel
{
    protected $casts = ['amount' => 'decimal:2'];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    public function refundAllocations()
    {
        return $this->hasMany(PaymentRefundAllocation::class);
    }
}
