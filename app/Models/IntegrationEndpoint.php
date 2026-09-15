<?php

namespace App\Models;

class IntegrationEndpoint extends CampusModel
{
    protected $casts = ['settings' => 'array', 'is_active' => 'boolean'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function logs()
    {
        return $this->hasMany(IntegrationLog::class);
    }
}
