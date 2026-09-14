<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends CampusModel
{
    use SoftDeletes;

    protected $casts = ['points' => 'decimal:2', 'is_active' => 'boolean'];

    public function questionBank()
    {
        return $this->belongsTo(QuestionBank::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class, 'quiz_questions')->withPivot(['id', 'sort_order', 'points'])->withTimestamps();
    }
}
