<?php

namespace App\Models;

class QuestionOption extends CampusModel
{
    protected $casts = ['is_correct' => 'boolean'];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
