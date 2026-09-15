<?php

namespace App\Models;

class JournalLine extends CampusModel
{
    protected $casts = ['debit' => 'decimal:2', 'credit' => 'decimal:2'];

    public function entry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
