<?php

namespace App\Models;

class TracerResponse extends CampusModel
{
    public function survey()
    {
        return $this->belongsTo(TracerSurvey::class, 'tracer_survey_id');
    }

    public function question()
    {
        return $this->belongsTo(TracerQuestion::class, 'tracer_question_id');
    }
}
