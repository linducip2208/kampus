<?php

namespace App\Models;

class MbkmProgram extends CampusModel
{
    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function registrations()
    {
        return $this->hasMany(MbkmRegistration::class);
    }
}
