<?php

namespace App\Models;

class NotificationTemplate extends CampusModel
{
    protected $casts = ['is_active' => 'boolean'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }
}
