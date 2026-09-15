<?php

namespace App\Models;

class InvoicePenalty extends CampusModel
{
    protected $casts = ['amount' => 'decimal:2', 'applied_at' => 'datetime', 'waived_at' => 'datetime'];

    public function invoice()
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function waivedBy()
    {
        return $this->belongsTo(User::class, 'waived_by');
    }
}
