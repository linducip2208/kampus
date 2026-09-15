<?php

namespace App\Models;

class PaymentTransaction extends CampusModel
{
    protected $casts = [
        'amount' => 'decimal:2', 'request_payload' => 'array', 'callback_payload' => 'array', 'paid_at' => 'datetime',
    ];

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function endpoint()
    {
        return $this->belongsTo(IntegrationEndpoint::class, 'integration_endpoint_id');
    }

    public function invoice()
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
