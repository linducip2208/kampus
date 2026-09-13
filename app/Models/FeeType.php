<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class FeeType extends CampusModel
{
    use SoftDeletes;
    public function university() { return $this->belongsTo(University::class); }
    public function invoiceItems() { return $this->hasMany(InvoiceItem::class); }
}
