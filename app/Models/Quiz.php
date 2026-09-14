<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends CampusModel
{
    use SoftDeletes;

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'randomize_questions' => 'boolean',
        'randomize_options' => 'boolean',
    ];

    public function classSection()
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'quiz_questions')->withPivot(['id', 'sort_order', 'points'])->withTimestamps();
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
