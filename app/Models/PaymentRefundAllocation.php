<?php

namespace App\Models;

class PaymentRefundAllocation extends CampusModel
{
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Financial refund ledger tidak dapat diubah.'));
        static::deleting(fn () => throw new \LogicException('Financial refund ledger tidak dapat dihapus.'));
    }

    protected $casts = ['amount' => 'decimal:2'];

    public function refund()
    {
        return $this->belongsTo(PaymentRefund::class, 'payment_refund_id');
    }

    public function paymentAllocation()
    {
        return $this->belongsTo(PaymentAllocation::class);
    }
}
