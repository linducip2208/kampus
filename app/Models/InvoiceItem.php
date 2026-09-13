<?php

namespace App\Models;

class InvoiceItem extends CampusModel
{
    protected $casts = ['amount' => 'decimal:2'];
    public function invoice() { return $this->belongsTo(StudentInvoice::class, 'student_invoice_id'); }
    public function feeType() { return $this->belongsTo(FeeType::class); }
}
