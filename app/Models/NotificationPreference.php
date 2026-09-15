<?php

namespace App\Models;

class NotificationPreference extends CampusModel
{
    protected $casts = ['is_enabled' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
