<?php

namespace App\Models;

class TracerOption extends CampusModel
{
    public function question()
    {
        return $this->belongsTo(TracerQuestion::class, 'tracer_question_id');
    }
}
