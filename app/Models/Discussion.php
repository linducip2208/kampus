<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Discussion extends CampusModel
{
    use SoftDeletes;

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function posts()
    {
        return $this->hasMany(DiscussionPost::class);
    }
}
