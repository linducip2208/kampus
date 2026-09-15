<?php

namespace App\Models;

class TracerQuestion extends CampusModel
{
    protected $casts = ['is_required' => 'boolean'];

    public function section()
    {
        return $this->belongsTo(TracerSection::class, 'tracer_section_id');
    }

    public function options()
    {
        return $this->hasMany(TracerOption::class)->orderBy('sort_order');
    }
}
