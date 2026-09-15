<?php

namespace App\Models;

class AssetLoan extends CampusModel
{
    protected $casts = ['borrowed_at' => 'datetime', 'due_at' => 'datetime', 'returned_at' => 'datetime'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrowed_by');
    }
}
