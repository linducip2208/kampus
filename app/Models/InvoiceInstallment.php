<?php

namespace App\Models;

class InvoiceInstallment extends CampusModel
{
    protected $casts = ['amount' => 'decimal:2', 'due_on' => 'date', 'paid_at' => 'datetime'];

    public function invoice()
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }
}
