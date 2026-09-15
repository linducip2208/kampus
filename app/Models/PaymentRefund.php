<?php

namespace App\Models;

class PaymentRefund extends CampusModel
{
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Financial refund ledger tidak dapat diubah.'));
        static::deleting(fn () => throw new \LogicException('Financial refund ledger tidak dapat dihapus.'));
    }

    protected $casts = [
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentRefundAllocation::class);
    }
}
