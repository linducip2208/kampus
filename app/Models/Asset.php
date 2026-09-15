<?php

namespace App\Models;

class Asset extends CampusModel
{
    protected $casts = ['purchase_date' => 'date', 'purchase_price' => 'decimal:2'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function loans()
    {
        return $this->hasMany(AssetLoan::class);
    }
}
