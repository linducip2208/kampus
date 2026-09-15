<?php

namespace App\Models;

class LetterTemplate extends CampusModel
{
    protected $casts = ['variables' => 'array', 'is_active' => 'boolean'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function requests()
    {
        return $this->hasMany(LetterRequest::class);
    }
}
