<?php

namespace App\Models;

class LibraryBook extends CampusModel
{
    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function loans()
    {
        return $this->hasMany(LibraryLoan::class);
    }
}
