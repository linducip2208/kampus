<?php

namespace App\Models;

class FeeStructureItem extends CampusModel
{
    public function feeStructure() { return $this->belongsTo(FeeStructure::class); }
    public function feeType() { return $this->belongsTo(FeeType::class); }
}
