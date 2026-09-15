<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class CourseAnnouncement extends CampusModel
{
    use SoftDeletes;

    protected $casts = [
        'is_pinned' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
