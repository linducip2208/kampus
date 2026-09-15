<?php

namespace App\Models;

class ChartOfAccount extends CampusModel
{
    protected $casts = ['is_active' => 'boolean'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }
}
